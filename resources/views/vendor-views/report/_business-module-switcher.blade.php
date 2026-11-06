@php
    $reportSwitchAvailable = businessModuleManager()->availableInPanel();
    $reportSwitchOptions = [];

    foreach (reportModuleSwitchOptions($switchPage, isset($date_type) ? ['date_type' => $date_type] : []) as $reportSwitchOption) {
        $reportSwitchOptions[] = $reportSwitchOption + [
            'icon' => $reportSwitchAvailable[$reportSwitchOption['key']]->icon,
        ];
    }
@endphp

@include('system-partials._business-module-scope', ['moduleScopeOptions' => $reportSwitchOptions])
