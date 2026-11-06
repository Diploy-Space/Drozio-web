@if(isset($folderItem))
    @php($folderCount = ($folderItem['totalFolders'] ?? 0) + ($folderItem['totalFiles'] ?? 0))
    <a class="fm-tile d-flex flex-column align-items-center text-center text-reset text-decoration-none bg-white border rounded p-2 p-sm-3 h-100"
       href="{{ route('admin.settings.system-setup.file-manager.index', ['storage' => $storageType ?? 'public', 'targetFolder' => $folderItem['encodePath']]) }}">
        <div class="d-flex align-items-center justify-content-center flex-grow-1 py-2">
            <img loading="lazy" decoding="async" width="60" height="60" alt=""
                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/' . ($folderCount > 0 ? 'folder.png' : 'folder-empty.png')) }}">
        </div>
        <h4 class="fm-tile__name fs-13 fw-bold text-capitalize mb-0 text-truncate w-100 px-1"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $folderItem['name'] }}">
            {{ Str::limit($folderItem['name'], 30) }}
        </h4>
        @if(isset($folderItem['totalFiles']))
            <p class="fs-12 mb-0 text-body-secondary">
                {{ $folderCount }} {{ $folderCount == 1 ? translate('Item') : translate('Items') }}
            </p>
            @if(!empty($folderItem['size']) && $folderItem['size'] !== '0 B')
                <p class="fs-10 mb-0 text-body-secondary text-nowrap">{{ $folderItem['size'] }}</p>
            @endif
        @endif
    </a>
@endif
