@if(count($addonInfos) > 0)
    @include('admin-views.system-setup.addons.partials._addons-list', ['addonInfos' => $addonInfos])
@else
    @include('layouts.admin.partials._empty-state', ['text' => 'no_addon_found'], ['image' => 'default'])
@endif
