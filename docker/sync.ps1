# Pushes source changes from this Windows checkout into the app container.
#
# Why: the application runs from a Docker volume (see docker-compose.yml),
# because Docker Desktop serves Windows paths at ~10 ms per file stat — a
# Laravel request touches ~1000 files, so running PHP straight from a bind
# mount takes ~20s per page instead of ~0.6s.
#
# Usage:
#   .\docker\sync.ps1              # push changes once
#   .\docker\sync.ps1 -Watch       # push on every save (Ctrl+C to stop)
#   .\docker\sync.ps1 -Full        # re-seed the whole checkout (slow, ~5 min)
#
# Paths that live in their own volumes (vendor, node_modules) or that hold
# runtime state (storage, public/build) are intentionally left alone.

[CmdletBinding()]
param(
    [switch]$Watch,
    [switch]$Full,
    [int]$IntervalSeconds = 2
)

$ErrorActionPreference = 'Stop'

$repo = Split-Path -Parent $PSScriptRoot
$container = 'drozio-web-app'
$root = '/var/www/html'

$excludedPrefixes = @(
    '.git/',
    'vendor/',
    'node_modules/',
    'storage/',
    'bootstrap/cache/',
    'public/build/',
    'public/storage/',
    'public/hot'
)
$excludedExact = @('.git', 'vendor', 'node_modules', 'storage', 'bootstrap/cache', 'public/build', 'public/storage', 'public/hot')

function Test-Excluded([string]$relativePath) {
    $normalized = $relativePath -replace '\\', '/'
    if ($excludedExact -contains $normalized) { return $true }
    foreach ($prefix in $excludedPrefixes) {
        if ($normalized.StartsWith($prefix, [StringComparison]::OrdinalIgnoreCase)) { return $true }
    }
    return $false
}

function Assert-ContainerRunning {
    $running = docker inspect --format '{{.State.Running}}' $container 2>$null
    if ($running -ne 'true') {
        throw "Container '$container' is not running. Start it with: docker compose up -d app"
    }
}

function Get-ContainerManifest {
    $find = "cd $root && find . -type f " +
        "-not -path './vendor/*' " +
        "-not -path './node_modules/*' " +
        "-not -path './storage/*' " +
        "-not -path './bootstrap/cache/*' " +
        "-not -path './public/build/*' " +
        "-not -path './public/storage*' " +
        "-not -path './public/hot' " +
        "-not -path './.git/*' " +
        "-printf '%P\t%s\t%T@\n'"
    $lines = docker exec $container sh -c $find
    $manifest = @{}
    foreach ($line in $lines) {
        if (-not $line) { continue }
        $parts = $line -split "`t"
        if ($parts.Count -lt 3) { continue }
        $manifest[$parts[0]] = [pscustomobject]@{
            Size       = [int64]$parts[1]
            ModifiedAt = [double]$parts[2]
        }
    }
    return $manifest
}

function Get-HostManifest {
    $manifest = @{}
    Get-ChildItem -LiteralPath $repo -Recurse -File -Force -ErrorAction SilentlyContinue | ForEach-Object {
        $relative = $_.FullName.Substring($repo.Length + 1) -replace '\\', '/'
        if (Test-Excluded $relative) { return }
        $manifest[$relative] = [pscustomobject]@{
            FullName   = $_.FullName
            Size       = $_.Length
            ModifiedAt = [double]([DateTimeOffset]$_.LastWriteTimeUtc).ToUnixTimeSeconds()
        }
    }
    return $manifest
}

function Test-Seeded {
    $result = docker exec $container sh -c "test -f $root/artisan && echo yes"
    return ($result -contains 'yes')
}

function Invoke-FullSeed {
    Write-Host 'Seeding the application volume from this checkout (one-time, a few minutes)...' -ForegroundColor Cyan
    # Runtime state (storage, bootstrap/cache) is deliberately left alone: it is
    # owned by the container and copying the host copy over it breaks
    # permissions and can throw away uploads and compiled views.
    $skip = @('.git', 'vendor', 'node_modules', 'storage', 'bootstrap')
    $entries = Get-ChildItem -LiteralPath $repo -Force | Where-Object { $skip -notcontains $_.Name }
    foreach ($entry in $entries) {
        Write-Verbose "seed   $($entry.Name)"
        docker cp $entry.FullName "${container}:$root" | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "Seeding failed on $($entry.Name)." }
    }
    # bootstrap/ is source code except for the generated caches.
    docker cp (Join-Path $repo 'bootstrap') "${container}:$root" | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Seeding failed on bootstrap/.' }
    docker exec $container sh -c "rm -f $root/bootstrap/cache/*.php; chmod -R 777 $root/storage $root/bootstrap/cache" | Out-Null
    Write-Host 'Seed complete.' -ForegroundColor Green
}

function Sync-Once([switch]$ForceFull) {
    Assert-ContainerRunning

    if ($ForceFull -or -not (Test-Seeded)) {
        Invoke-FullSeed
        return
    }

    $containerFiles = Get-ContainerManifest
    $hostFiles = Get-HostManifest

    $toCopy = New-Object System.Collections.Generic.List[string]
    foreach ($relative in $hostFiles.Keys) {
        $hostFile = $hostFiles[$relative]
        if (-not $containerFiles.ContainsKey($relative)) {
            $toCopy.Add($relative)
            continue
        }
        $containerFile = $containerFiles[$relative]
        if ($containerFile.Size -ne $hostFile.Size) {
            $toCopy.Add($relative)
            continue
        }
        if ([Math]::Abs($containerFile.ModifiedAt - $hostFile.ModifiedAt) -gt 1) {
            $toCopy.Add($relative)
        }
    }

    $toDelete = @($containerFiles.Keys | Where-Object { -not $hostFiles.ContainsKey($_) })

    if ($toCopy.Count -eq 0 -and $toDelete.Count -eq 0) {
        Write-Host 'Already in sync.' -ForegroundColor DarkGray
        return
    }

    foreach ($relative in $toCopy) {
        Write-Verbose "copy   $relative"
        $source = $hostFiles[$relative].FullName
        $segments = $relative -split '/'
        if ($segments.Count -gt 1) {
            $directory = ($segments[0..($segments.Count - 2)] -join '/')
            docker exec $container sh -c "mkdir -p '$root/$directory'" | Out-Null
        }
        docker cp $source "${container}:$root/$relative" | Out-Null
        if ($LASTEXITCODE -ne 0) { Write-Warning "Failed to copy $relative" }
    }

    if ($toDelete.Count -gt 0) {
        # Windows PowerShell does not reliably forward piped stdin to
        # `docker exec -i`, so hand the list over as a file instead.
        $toDelete | ForEach-Object { Write-Verbose "delete $_" }
        $listFile = Join-Path ([IO.Path]::GetTempPath()) ("drozio-delete-{0}.txt" -f [guid]::NewGuid())
        try {
            # LF only: the container's rm would treat a trailing CR as part of
            # the file name and silently skip it.
            [IO.File]::WriteAllText($listFile, (($toDelete -join "`n") + "`n"), (New-Object System.Text.UTF8Encoding($false)))
            docker cp $listFile "${container}:/tmp/drozio-delete.txt" | Out-Null
            docker exec $container sh -c "cd $root && tr -d '\r' < /tmp/drozio-delete.txt | xargs -d '\n' -r rm -f --; rm -f /tmp/drozio-delete.txt"
        } finally {
            Remove-Item -LiteralPath $listFile -Force -ErrorAction SilentlyContinue
        }
    }

    $deletedText = if ($toDelete.Count -gt 0) { ", removed $($toDelete.Count)" } else { '' }
    Write-Host "Synced $($toCopy.Count) file(s)$deletedText." -ForegroundColor Green
}

if ($Watch) {
    Write-Host "Watching $repo for changes (Ctrl+C to stop)..." -ForegroundColor Cyan
    Sync-Once -ForceFull:$Full
    $lastManifest = Get-HostManifest
    while ($true) {
        Start-Sleep -Seconds $IntervalSeconds
        $current = Get-HostManifest
        $changed = $current.Count -ne $lastManifest.Count
        if (-not $changed) {
            foreach ($relative in $current.Keys) {
                if (-not $lastManifest.ContainsKey($relative) -or
                    $lastManifest[$relative].Size -ne $current[$relative].Size -or
                    [Math]::Abs($lastManifest[$relative].ModifiedAt - $current[$relative].ModifiedAt) -gt 1) {
                    $changed = $true
                    break
                }
            }
        }
        if ($changed) {
            Start-Sleep -Milliseconds 300  # let the editor finish writing
            Sync-Once
            $lastManifest = Get-HostManifest
        }
    }
} else {
    Sync-Once -ForceFull:$Full
}
