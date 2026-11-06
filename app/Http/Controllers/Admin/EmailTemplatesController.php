<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\EmailTemplateUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\EmailTemplateService;
use App\Services\New\SocialMediaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailTemplatesController extends BaseController
{
    private const LANGUAGE_KEY = 'pnc_language';

    /** @var array<int, string> */
    private const IMAGE_INPUTS = ['image', 'icon', 'logo', 'banner_image'];

    public function __construct(
        private readonly EmailTemplateService $emailTemplateService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly SocialMediaService $socialMediaService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('email-templates.mail-tester', [
            'socialMedia' => $this->activeSocialMedia(),
        ]);
    }

    public function getView(string $userType, string $templateName): View
    {
        $this->syncTemplates(userType: $userType);

        return view('admin-views.business-settings.email-template.index', [
            'template' => $this->emailTemplateService->findBy(
                params: ['user_type' => $userType, 'template_name' => $templateName],
                relations: ['translations', 'translationCurrentLanguage'],
            ),
            'language' => $this->businessSettingService->findBy(params: ['type' => self::LANGUAGE_KEY]),
            'socialMedia' => $this->activeSocialMedia(),
        ]);
    }

    public function update(EmailTemplateUpdateRequest $request, string $templateName, string $userType): RedirectResponse
    {
        $template = $this->findTemplate(templateName: $templateName, userType: $userType);

        if ($template === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->back();
        }

        $this->emailTemplateService->update(
            id: $template->id,
            data: $this->templateAttributes(request: $request),
            images: $this->uploadedImages(request: $request),
        );

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }

    public function updateStatus(Request $request, string $templateName, string $userType): JsonResponse
    {
        $template = $this->findTemplate(templateName: $templateName, userType: $userType);

        if ($template === null) {
            return response()->json([
                'message' => translate('Not found'),
                'success' => 0,
            ]);
        }

        $status = (bool) $request->get('status', 0);
        $this->emailTemplateService->updateStatus(id: $template->id, status: $status);

        $messageKey = str_replace('-', '_', $templateName).($status ? '_mail_is_on' : '_mail_is_off');

        return response()->json([
            'message' => translate($messageKey),
            'success' => 200,
        ]);
    }

    private function findTemplate(string $templateName, string $userType): ?object
    {
        return $this->emailTemplateService->findBy(params: [
            'template_name' => $templateName,
            'user_type' => $userType,
        ]);
    }

    private function activeSocialMedia(): object
    {
        return $this->socialMediaService->getList(dataLimit: 'all', filters: ['status' => 1]);
    }

    private function syncTemplates(string $userType): void
    {
        $storedTemplates = $this->emailTemplateService->getList(dataLimit: 'all', filters: ['user_type' => $userType]);
        $expectedNames = $this->emailTemplateService->getTemplateNames(userType: $userType);

        foreach (array_diff($expectedNames, $storedTemplates->pluck('template_name')->all()) as $templateName) {
            $this->emailTemplateService->create(
                data: $this->emailTemplateService->getDefaultAttributes(userType: $userType, templateName: $templateName),
            );
        }

        foreach ($storedTemplates->whereNotIn('template_name', $expectedNames) as $template) {
            $this->emailTemplateService->delete(id: $template->id);
        }
    }

    /** @return array<string, mixed> */
    private function templateAttributes(EmailTemplateUpdateRequest $request): array
    {
        $locales = array_values((array) $request['lang']);

        $attributes = [
            'lang' => $locales,
            'button_url' => $request['button_url'],
            'pages' => $request['social_media'] ? array_keys($request['pages']) : null,
            'social_media' => $request['social_media'] ? array_keys($request['social_media']) : null,
            'button_content_status' => $request->get('button_content_status', 0),
            'product_information_status' => $request->get('product_information_status', 0),
            'order_information_status' => $request->get('order_information_status', 0),
        ];

        foreach (EmailTemplateService::TRANSLATABLE_FIELDS as $field) {
            $values = (array) $request->input($field, []);

            foreach ($locales as $index => $locale) {
                $attributes[$field][$index] = ($values[$locale] ?? null) ?: null;
            }
        }

        return $attributes;
    }

    /** @return array<string, \Illuminate\Http\UploadedFile> */
    private function uploadedImages(EmailTemplateUpdateRequest $request): array
    {
        $images = [];

        foreach (self::IMAGE_INPUTS as $input) {
            if ($request->hasFile($input)) {
                $images[$input] = $request->file($input);
            }
        }

        return $images;
    }
}
