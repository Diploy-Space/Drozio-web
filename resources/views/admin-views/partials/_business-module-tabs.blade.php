<ul class="nav nav-pills nav--tab p-0">
    @foreach ($businessModuleTabs as $moduleKey => $businessModuleTab)
        <li class="nav-item">
            <a class="nav-link border {{ $activeBusinessModule === $moduleKey ? 'active border-primary' : 'bg-white' }}"
               href="{{ $businessModuleTab['url'] }}">
                {{ $businessModuleTab['label'] }}
                @if(!is_null($businessModuleTab['count']))
                    <span class="badge rounded-pill fs-12 border {{ $activeBusinessModule === $moduleKey ? 'bg-white text-primary border-white' : 'bg-white text-body' }}">{{ $businessModuleTab['count'] }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
