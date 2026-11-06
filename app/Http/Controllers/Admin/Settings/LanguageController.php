<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\LanguageAddRequest;
use App\Http\Requests\Admin\Setting\System\LanguageUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\LanguageService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends BaseController
{
    private const LANGUAGE_SETTING = 'language';

    private const SUPPORTED_CODES_SETTING = 'pnc_language';

    private const SETUP_GUIDE_KEY = 'language_setup';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly LanguageService $languageService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $languageList = $this->languageService->getList(searchValue: $request['search']);
        $languages = $languageList->all();

        return view('admin-views.system-setup.language.index', [
            'languageList' => $languageList,
            'languageLocaleList' => $this->languageService->getLocaleOptions(),
            'languageStats' => $this->getLanguageStats(languages: $languages),
            'overview' => $this->getLanguageOverview(languages: $languages),
        ]);
    }

    public function add(LanguageAddRequest $request): RedirectResponse
    {
        $added = $this->languageService->create(data: $request->all(), languages: $this->getStoredLanguages());

        $this->storeLanguages(languages: $added['languages']);
        $this->businessSettingService->updateOrInsert(params: ['type' => self::SUPPORTED_CODES_SETTING], data: ['value' => json_encode($added['codes'])]);
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');
        ToastMagic::success(translate('Language Added'));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->storeLanguages(languages: $this->languageService->updateStatus(
            id: $request['code'],
            status: (bool) $request['status'],
            languages: $this->getStoredLanguages(),
        ));
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');

        return response()->json([
            'status' => 1,
            'message' => translate('Language Status Updated'),
        ]);
    }

    public function updateDefaultStatus(Request $request): RedirectResponse
    {
        $this->storeLanguages(languages: $this->languageService->markAsDefault(
            id: $request['code'],
            languages: $this->getStoredLanguages(),
        ));
        ToastMagic::success(translate('Default Language Changed'));

        return back();
    }

    public function update(LanguageUpdateRequest $request): RedirectResponse
    {
        $this->storeLanguages(languages: $this->languageService->update(
            id: $request['code'],
            data: $request->all(),
            languages: $this->getStoredLanguages(),
        ));
        updateSetupGuideCacheKey(key: self::SETUP_GUIDE_KEY, panel: 'admin');
        ToastMagic::success(translate('Language updated'));

        return back();
    }

    public function getTranslateView(Request $request, string $lang): View
    {
        return view('admin-views.system-setup.language.translate', [
            'lang' => $lang,
            'languageName' => $this->languageService->find(id: $lang)['name'] ?? '',
            'totalMessages' => $this->languageService->getPendingMessageCount(language: $lang),
            'messageGroup' => LanguageService::TRANSLATE_BATCH_SIZE,
            'stats' => $this->languageService->getTranslationStats(language: $lang),
            'translations' => $this->languageService->getTranslatePaginatedList(
                language: $lang,
                searchValue: $request['searchValue'],
                status: $request['status'],
                dataLimit: (int) ($this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value ?: DEFAULT_DATA_LIMIT),
                page: (int) $request->input('page', 1),
            ),
        ]);
    }

    public function getUnusedKeys(string $lang): JsonResponse
    {
        return response()->json($this->languageService->getUnusedTranslationKeys(language: $lang));
    }

    public function removeUnusedKeys(Request $request, string $lang): JsonResponse|RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $request->validate(['keys' => 'required|array|min:1', 'keys.*' => 'required|string']);
        $removed = $this->languageService->removeUnusedTranslationKeysAcrossLocales(keys: $request['keys']);

        return response()->json([
            'removed' => $removed,
            'message' => $removed > 0
                ? $removed.' '.translate(':item removed successfully', ['item' => translate('Unused keys')])
                : translate('No unused keys found'),
            'stats' => $this->languageService->getTranslationStats(language: $lang),
        ]);
    }

    public function syncLanguages(Request $request, string $lang): JsonResponse|RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $summary = $this->languageService->syncAllLanguagesWithMaster();

        return response()->json([
            'languages' => $summary['languages'],
            'added' => $summary['added'],
            'removed' => $summary['removed'],
            'message' => translate('Languages synced with english').': '.
                $summary['added'].' '.translate('Added').', '.
                $summary['removed'].' '.translate('Removed'),
        ]);
    }

    public function deleteTranslateKey(Request $request, string $lang): void
    {
        $this->languageService->deleteTranslationKey(language: $lang, key: $request['key']);
    }

    public function updateTranslate(Request $request, string $lang): JsonResponse
    {
        $promoted = $this->languageService->saveTranslation(
            language: $lang,
            key: $this->decodedKey($request),
            value: $request['value'],
        );

        return response()->json([
            'message' => translate('Message Updated'),
            'promoted' => $promoted,
            'stats' => $this->languageService->getTranslationStats(language: $lang),
        ]);
    }

    public function getAutoTranslate(Request $request, string $lang): JsonResponse
    {
        $translation = $this->languageService->translateKey(language: $lang, key: $this->decodedKey($request));

        if ($translation['reason'] === LanguageService::REASON_PROVIDER_UNAVAILABLE) {
            return response()->json([
                'message' => translate('Translation service is unavailable. Please try again in a few minutes.'),
            ], 503);
        }

        if ($translation['reason'] !== null) {
            return response()->json(['message' => 'empty_data']);
        }

        return response()->json([
            'translated_data' => $translation['value'],
            'promoted' => $translation['promoted'],
            'stats' => $this->languageService->getTranslationStats(language: $lang),
        ]);
    }

    public function getAutoTranslateAllMessages(Request $request, string $lang): JsonResponse|RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $response = $this->languageService->translateAllMessages(language: $lang);
        $message = $this->translateAllMessagesMessage(response: $response);

        return response()->json([
            'status' => $response['status'],
            'message' => $message,
            'due_message' => $response['due'],
            'translate_success_message' => $response['translated'] > 0
                ? translate('Your').' '.$response['translated'].' '.translate('Messages translated successfully')
                : $message,
        ]);
    }

    public function delete(string $lang): RedirectResponse
    {
        $this->storeLanguages(languages: $this->languageService->delete(
            id: $lang,
            languages: $this->getStoredLanguages(),
        ));

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SUPPORTED_CODES_SETTING],
            data: ['value' => json_encode($this->supportedCodes(
                codes: $this->getStoredSetting(type: self::SUPPORTED_CODES_SETTING),
                excludedCode: $lang,
            ))],
        );

        ToastMagic::success(translate('Removed successfully'));

        return back();
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<string, array<string, int>>
     */
    private function getLanguageStats(array $languages): array
    {
        $stats = [];

        foreach ($languages as $language) {
            $code = $language['code'] ?? '';

            if ($code === '') {
                continue;
            }

            $stat = $this->languageService->getTranslationStats(language: $code);
            $stats[$code] = $stat + [
                'completion' => $stat['total'] > 0 ? (int) round(($stat['translated'] / $stat['total']) * 100) : 0,
            ];
        }

        return $stats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $languages
     * @return array<string, mixed>
     */
    private function getLanguageOverview(array $languages): array
    {
        $active = 0;
        $rightToLeft = 0;
        $default = '';

        foreach ($languages as $language) {
            $code = $language['code'] ?? '';

            if ($code === '') {
                continue;
            }

            if (($language['status'] ?? 0) == 1) {
                $active++;
            }

            if (($language['direction'] ?? LanguageService::DIRECTION_LTR) === LanguageService::DIRECTION_RTL) {
                $rightToLeft++;
            }

            if ($language['default'] ?? $code === LanguageService::SOURCE_LANGUAGE_CODE) {
                $default = $language['name'] ?? $code;
            }
        }

        $total = count($languages);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'ltr' => $total - $rightToLeft,
            'rtl' => $rightToLeft,
            'default' => $default,
        ];
    }

    /** @param  array{status: int, reason: ?string, due: int, translated: int}  $response */
    private function translateAllMessagesMessage(array $response): string
    {
        if ($response['reason'] === LanguageService::REASON_PROVIDER_UNAVAILABLE) {
            return $response['translated'] > 0
                ? translate('Translation service stopped responding. Some messages are untranslated; try again shortly.')
                : translate('Translation service is unavailable. Please try again in a few minutes.');
        }

        return $response['due'] > 0 ? translate('Translate successful') : translate('All messages are translated');
    }

    private function decodedKey(Request $request): string
    {
        return empty($request['key']) ? '' : base64_decode($request['key']);
    }

    /** @return array<int, array<string, mixed>> */
    private function getStoredLanguages(): array
    {
        return $this->getStoredSetting(type: self::LANGUAGE_SETTING);
    }

    /** @param  array<int, array<string, mixed>>  $languages */
    private function storeLanguages(array $languages): void
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => self::LANGUAGE_SETTING], data: ['value' => json_encode($languages)]);
    }

    /** @return array<int, mixed> */
    private function getStoredSetting(string $type): array
    {
        return json_decode($this->businessSettingService->findBy(params: ['type' => $type])?->value ?? '[]', true) ?? [];
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function supportedCodes(array $codes, ?string $excludedCode = null): array
    {
        $supported = array_values(array_filter(
            $codes,
            fn (string $code) => $code !== $excludedCode && $code !== LanguageService::SOURCE_LANGUAGE_CODE,
        ));

        array_unshift($supported, LanguageService::SOURCE_LANGUAGE_CODE);

        return $supported;
    }
}
