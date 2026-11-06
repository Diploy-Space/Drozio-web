<?php

namespace App\View\Composers\Vendor;

use App\Services\New\AdvancedSearchService;
use Illuminate\View\View;

class AdvancedSearchComposer
{
    public function __construct(
        private readonly AdvancedSearchService $advancedSearchService,
    ) {}

    public function compose(View $view): void
    {
        $view->with('isAdvancedSearchAvailable', $this->advancedSearchService->forPanel(panel: AdvancedSearchService::PANEL_VENDOR)->isAvailable());
    }
}
