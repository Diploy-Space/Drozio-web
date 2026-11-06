<?php

namespace Modules\Blog\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Blog\app\Enums\PrioritySetting;
use Modules\Blog\app\Http\Requests\Admin\PrioritySetup\UpdateRequest;

class BlogPrioritySetupController extends Controller
{
    private const SORT_MOST_CLICKED = 'most_clicked';

    private const SORT_A_TO_Z = 'a_to_z';

    private const SORT_Z_TO_A = 'z_to_a';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $priorities = $this->getPrioritySetup();

        return view('blog::admin-views.blog._priority-setup', [
            'prioritySections' => [
                $this->prioritySection(
                    type: PrioritySetting::CATEGORY_LIST,
                    parentClass: 'blog-category-priority',
                    icon: 'fi-sr-apps',
                    title: translate('Blog categories'),
                    description: translate('The order category filters appear in on the blog page.'),
                    defaultHint: translate('Newest category first.'),
                    mostClickedLabel: translate('Most clicked first'),
                    priority: $priorities['blogCategoryPriority'],
                ),
                $this->prioritySection(
                    type: PrioritySetting::LIST,
                    parentClass: 'blog-list-priority',
                    icon: 'fi-sr-document',
                    title: translate('Blog posts'),
                    description: translate('The order posts appear in on the blog page.'),
                    defaultHint: translate('Oldest post first.'),
                    mostClickedLabel: translate('Most read first'),
                    priority: $priorities['blogPriority'],
                ),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function prioritySection(
        string $type,
        string $parentClass,
        string $icon,
        string $title,
        string $description,
        string $defaultHint,
        string $mostClickedLabel,
        array $priority,
    ): array {
        $customSelected = ($priority['custom_sorting_status'] ?? 0) == 1;

        return [
            'type' => $type,
            'parent_class' => $parentClass,
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
            'default_hint' => $defaultHint,
            'saved' => $priority !== [],
            'default_selected' => ! isset($priority['default_sorting_status']) || $priority['default_sorting_status'] == 1,
            'custom_selected' => $customSelected,
            'sort_by' => $priority['sort_by'] ?? self::SORT_MOST_CLICKED,
            'options' => [
                ['value' => self::SORT_MOST_CLICKED, 'id' => $parentClass.'-most-clicked', 'label' => $mostClickedLabel],
                ['value' => self::SORT_A_TO_Z, 'id' => $parentClass.'-a-to-z', 'label' => translate('Alphabetical').' (A - Z)'],
                ['value' => self::SORT_Z_TO_A, 'id' => $parentClass.'-z-to-a', 'label' => translate('Alphabetical').' (Z - A)'],
            ],
        ];
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $this->storePrioritySetup(setting: $request['type'], data: $request->all())
            ? ToastMagic::success($this->updatedMessage(setting: $request['type']))
            : ToastMagic::error(translate('Not found'));

        return back();
    }

    private function updatedMessage(string $setting): string
    {
        return $setting === PrioritySetting::CATEGORY_LIST
            ? translate(':item updated successfully', ['item' => translate('Blog category priority setup')])
            : translate(':item updated successfully', ['item' => translate('Blog priority setup')]);
    }

    /** @return array<string, array<string, mixed>> */
    private function getPrioritySetup(): array
    {
        $settings = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => PrioritySetting::ALL])
            ->pluck('value', 'type');

        return [
            'blogCategoryPriority' => json_decode((string) $settings->get(PrioritySetting::CATEGORY_LIST), true) ?? [],
            'blogPriority' => json_decode((string) $settings->get(PrioritySetting::LIST), true) ?? [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function storePrioritySetup(string $setting, array $data): bool
    {
        if (! in_array($setting, PrioritySetting::ALL, true)) {
            return false;
        }

        return $this->businessSettingService->updateOrInsert(params: ['type' => $setting], data: ['value' => json_encode([
            'default_sorting_status' => $data['default_sorting_status'] ?? 0,
            'custom_sorting_status' => $data['custom_sorting_status'] ?? 0,
            'sort_by' => $data['sort_by'] ?? null,
        ])]);
    }
}
