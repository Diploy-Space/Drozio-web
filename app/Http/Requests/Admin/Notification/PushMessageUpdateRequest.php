<?php

namespace App\Http\Requests\Admin\Notification;

use App\Enums\NotificationUserType;
use App\Http\Requests\BaseRequest;
use App\Services\New\PushNotificationService;
use Illuminate\Validation\Rule;

class PushMessageUpdateRequest extends BaseRequest
{
    /** @var array<int, string> */
    private const LEGACY_USER_TYPE_ALIASES = ['vendor', 'deliveryman', 'delivery-man'];

    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly PushNotificationService $pushNotificationService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $availableModules = [...array_keys(businessModuleManager()->availableInPanel()), PushNotificationService::MODULE_COMMON];
        $userType = NotificationUserType::resolveFromRequest($this->input('user_type'));

        $availableUserTypes = ($module = $this->resolvedModule($availableModules))
            ? $this->pushNotificationService->getAvailableUserTypes(businessModule: $module)
            : [];

        return [
            'module' => ['required', Rule::in($availableModules)],
            'user_type' => ['required', Rule::in(array_merge(
                NotificationUserType::values(),
                self::LEGACY_USER_TYPE_ALIASES
            )), function ($attribute, $value, $fail) use ($userType, $availableUserTypes) {
                if ($userType === null || ! in_array($userType, $availableUserTypes, true)) {
                    $fail(translate('The selected user type is invalid for this module'));
                }
            }],
        ];
    }

    /** @param  array<int, string>  $availableModules */
    private function resolvedModule(array $availableModules): ?string
    {
        $module = $this->input('module');

        return in_array($module, $availableModules, true) ? $module : null;
    }

    public function messages(): array
    {
        return [
            'module.required' => translate('The module field is required'),
            'module.in' => translate('The selected module is invalid'),
            'user_type.required' => translate('The user type field is required'),
        ];
    }
}
