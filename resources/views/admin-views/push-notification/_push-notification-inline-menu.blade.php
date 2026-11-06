<div class="position-relative nav--tab-wrapper">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        @foreach ($availableModules as $module)
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $businessModule === $module->key ? 'active' : '' }}"
                   href="{{ route('admin.settings.system-setup.push-notification.index', ['module' => $module->key, 'user_type' => $userType]) }}"
                   role="tab" aria-selected="{{ $businessModule === $module->key ? 'true' : 'false' }}">
                    {{ translate($module->label) }}
                </a>
            </li>
        @endforeach
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $businessModule === $commonModule ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.push-notification.index', ['module' => $commonModule, 'user_type' => $userType]) }}"
               role="tab" aria-selected="{{ $businessModule === $commonModule ? 'true' : 'false' }}">
                {{ translate('Common') }}
            </a>
        </li>
    </ul>
    <div class="nav--tab__prev">
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-left"></i>
        </button>
    </div>
    <div class="nav--tab__next">
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>
</div>
