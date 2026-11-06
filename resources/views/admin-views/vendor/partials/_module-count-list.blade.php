@if (count($modules) === 1)
    <a href="{{ route($modules[0]['route'], ['id' => $sellerId, 'tab' => $modules[0]['tab']]) }}"
       class="badge badge-info text-bg-info">
        {{ $modules[0]['counts'][$sellerId] ?? 0 }}
    </a>
@else
    <div class="d-inline-flex flex-column gap-1 text-start">
        @foreach ($modules as $module)
            <a href="{{ route($module['route'], ['id' => $sellerId, 'tab' => $module['tab']]) }}"
               class="d-flex align-items-center justify-content-between gap-3 text-body text-hover-primary">
                <span class="fs-12">{{ $module['label'] }}</span>
                <span class="badge badge-info text-bg-info">{{ $module['counts'][$sellerId] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
@endif
