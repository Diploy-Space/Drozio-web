<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Page\ShowRequest;
use App\Services\New\BusinessPageService;
use App\Services\New\BusinessSettingService;
use App\Services\New\HelpTopicService;
use App\Services\New\RobotsMetaContentService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    private const BUSINESS_PAGE_RELATIONS = ['banner'];

    public function __construct(
        private readonly BusinessPageService $businessPageService,
        private readonly HelpTopicService $helpTopicService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function show(ShowRequest $request, string $slug): View|RedirectResponse
    {
        $businessPage = $this->businessPageService->findBy(
            params: $this->businessPageFilters(slug: $slug, isAdminPreview: $request->isAdminPreview()),
            relations: self::BUSINESS_PAGE_RELATIONS,
        );

        if ($businessPage === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('home');
        }

        return view(themeViewName(key: 'business_page'), [
            'businessPage' => $businessPage,
            'robotsMetaContentData' => $this->robotsMetaContentService->findByPageNameOrDefault(pageName: $slug),
        ]);
    }

    public function showContactPage(): View
    {
        return view(themeViewName(key: 'contacts'), [
            'recaptcha' => getWebConfig(name: 'recaptcha'),
            'robotsMetaContentData' => $this->robotsMetaContentService->findByPageNameOrDefault(
                pageName: RobotsMetaContentService::PAGE_NAME_CONTACTS,
            ),
        ]);
    }

    public function getHelpTopics(): View
    {
        $helps = $this->helpTopicService->getList(
            dataLimit: 'all',
            filters: ['status' => 1, 'type' => HelpTopicService::TYPE_DEFAULT],
        );

        $pageTitleBanner = $this->businessSettingService->findByJsonValue(
            params: ['type' => BusinessSettingService::BANNER_FAQ_PAGE],
            value: ['status' => BusinessSettingService::JSON_STATUS_ACTIVE],
        );

        return view(themeViewName(key: 'faq'), [
            'helps' => $helps,
            'pageTitleBanner' => $pageTitleBanner,
            'robotsMetaContentData' => $this->robotsMetaContentService->findByPageNameOrDefault(
                pageName: RobotsMetaContentService::PAGE_NAME_HELP_TOPIC,
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function businessPageFilters(string $slug, bool $isAdminPreview): array
    {
        $filters = ['slug' => $slug];

        return $isAdminPreview ? $filters : $filters + ['status' => 1];
    }
}
