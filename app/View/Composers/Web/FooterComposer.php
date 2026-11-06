<?php

namespace App\View\Composers\Web;

use App\Services\New\BusinessPageService;
use App\Services\New\BusinessSettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class FooterComposer
{
    private const ACTIVE = 1;

    private const DEFAULT_PAGE = 1;

    private const CUSTOM_PAGE = 0;

    private const BLOG_ROUTE = 'frontend.blog.index';

    private const BLOG_SETTING = 'blog_feature_active_status';

    private const COOKIE_SETTING = 'cookie_setting';

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly BusinessPageService $businessPageService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->resolved ??= $this->build());
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $pages = $this->businessPageService->getList(dataLimit: 'all', filters: ['status' => self::ACTIVE]);

        return [
            'footerDefaultPages' => $pages->where('default_status', self::DEFAULT_PAGE),
            'footerCustomPages' => $pages->where('default_status', self::CUSTOM_PAGE),
            'footerBlogEnabled' => Route::has(self::BLOG_ROUTE) && (bool) getWebConfig(name: self::BLOG_SETTING),
            'footerCookieSetting' => $this->cookieSetting(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function cookieSetting(): ?array
    {
        $setting = $this->businessSettingService->findBy(['type' => self::COOKIE_SETTING]);

        return $setting === null ? null : json_decode($setting->value, true);
    }
}
