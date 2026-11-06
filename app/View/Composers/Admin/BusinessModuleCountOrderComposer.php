<?php

namespace App\View\Composers\Admin;

use App\Enums\BusinessModule;
use Illuminate\View\View;

class BusinessModuleCountOrderComposer
{
    public function compose(View $view): void
    {
        $modules = $view->getData()['modules'] ?? null;

        if (! is_array($modules)) {
            return;
        }

        $order = array_flip(BusinessModule::values());

        usort(
            $modules,
            fn (array $first, array $second): int => ($order[$first['key']] ?? PHP_INT_MAX) <=> ($order[$second['key']] ?? PHP_INT_MAX),
        );

        $view->with('modules', $modules);
    }
}
