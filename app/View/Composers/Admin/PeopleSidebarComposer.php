<?php

namespace App\View\Composers\Admin;

use App\Services\New\ContactService;
use Illuminate\View\View;

class PeopleSidebarComposer
{
    private const UNSEEN = 0;

    private ?int $resolved = null;

    public function __construct(private readonly ContactService $contactService) {}

    public function compose(View $view): void
    {
        $view->with('sidebarUnseenMessages', $this->resolved ??= $this->contactService->count(['seen' => self::UNSEEN]));
    }
}
