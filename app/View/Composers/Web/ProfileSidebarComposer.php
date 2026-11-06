<?php

namespace App\View\Composers\Web;

use App\Services\Web\CustomerProfileMenuBuilder;
use Illuminate\View\View;

class ProfileSidebarComposer
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly CustomerProfileMenuBuilder $menuBuilder,
    ) {}

    public function compose(View $view): void
    {
        $view->with(['profileMenu' => $this->resolved ??= $this->menuBuilder->build()]);
    }
}
