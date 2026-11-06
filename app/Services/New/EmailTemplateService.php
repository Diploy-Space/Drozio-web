<?php

namespace App\Services\New;

use App\Enums\EmailTemplateKey;
use App\Models\EmailTemplate;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class EmailTemplateService extends BaseService
{
    public const SORT_LATEST = 'latest';

    /** @var array<int, string> */
    public const TRANSLATABLE_FIELDS = ['title', 'body', 'button_name', 'footer_text', 'copyright_text'];

    private const IMAGE_DIRECTORY = 'email-template/';

    private const IMAGE_FORMAT = 'webp';

    /** @var array<string, string> uploaded input => the column it replaces */
    private const IMAGE_COLUMNS = [
        'image' => 'image',
        'icon' => 'image',
        'logo' => 'logo',
        'banner_image' => 'banner_image',
    ];

    public function __construct(EmailTemplate $emailTemplate)
    {
        parent::__construct($emailTemplate);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('template_name', 'like', "%{$searchValue}%"))
            ->when($sortBy === self::SORT_LATEST, fn (Builder $builder) => $builder->latest());

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = ['translations']): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    /** @param  array<string, UploadedFile>  $images */
    public function update(mixed $id, array $data, array $images = []): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data, $images): Model {
            $record->update([
                ...$this->prepareAttributes($data),
                ...$this->uploadedImageColumns($record, $images),
            ]);

            $this->syncTranslations($record->id, $data, self::TRANSLATABLE_FIELDS, skipBlankValues: true);

            return $record->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($record): bool {
            $this->deleteTranslations([$record->id]);

            return (bool) $record->delete();
        });
    }

    /** @return array<int, string> */
    public function getTemplateNames(string $userType): array
    {
        return match ($userType) {
            'admin' => EmailTemplateKey::ADMIN_EMAIL_LIST,
            'customer' => EmailTemplateKey::CUSTOMER_EMAIL_LIST,
            'vendor' => EmailTemplateKey::VENDOR_EMAIL_LIST,
            'delivery-man' => EmailTemplateKey::DELIVERY_MAN_EMAIL_LIST,
        };
    }

    /** @return array<string, mixed> */
    public function getDefaultAttributes(string $userType, string $templateName): array
    {
        return [
            'template_name' => $templateName,
            'user_type' => $userType,
            'template_design_name' => $templateName,
            'title' => $this->defaultTitle(templateName: $templateName),
            'body' => $this->defaultBody(userType: $userType, templateName: $templateName),
            'hide_field' => $this->hiddenFields(templateName: $templateName),
            'copyright_text' => translate('Copyright').date('Y').' '.getWebConfig(name: 'company_name').'. '.translate('All right reserved').'.',
            'footer_text' => translate('Please contact us for any queries').','.translate('We are always happy to help').'.',
        ];
    }

    /** @return array<string, mixed> */
    private function prepareAttributes(array $data): array
    {
        return [
            'title' => $this->defaultLocaleValue($data, 'title'),
            'body' => $this->defaultLocaleValue($data, 'body'),
            'button_name' => $this->defaultLocaleValue($data, 'button_name'),
            'footer_text' => $this->defaultLocaleValue($data, 'footer_text'),
            'copyright_text' => $this->defaultLocaleValue($data, 'copyright_text'),
            'button_url' => $data['button_url'] ?? null,
            'pages' => $data['pages'] ?? null,
            'social_media' => $data['social_media'] ?? null,
            'button_content_status' => $data['button_content_status'] ?? 0,
            'product_information_status' => $data['product_information_status'] ?? 0,
            'order_information_status' => $data['order_information_status'] ?? 0,
        ];
    }

    /**
     * @param  array<string, UploadedFile>  $images
     * @return array<string, string>
     */
    private function uploadedImageColumns(Model $record, array $images): array
    {
        $columns = [];

        foreach (self::IMAGE_COLUMNS as $input => $column) {
            if (isset($images[$input])) {
                $columns[$column] = $this->replaceImage(
                    self::IMAGE_DIRECTORY,
                    $record->{$column},
                    self::IMAGE_FORMAT,
                    $images[$input],
                );
            }
        }

        return $columns;
    }

    /** @return array<int, string> */
    private function hiddenFields(string $templateName): array
    {
        $withoutButtonContent = ['product_information', 'order_information', 'button_content', 'banner_image'];

        return [
            EmailTemplateKey::REGISTRATION => $withoutButtonContent,
            EmailTemplateKey::REGISTRATION_VERIFICATION => $withoutButtonContent,
            EmailTemplateKey::REGISTRATION_FROM_POS => ['product_information', 'order_information', 'button_url', 'button_content_status', 'banner_image'],
            EmailTemplateKey::REGISTRATION_APPROVED => $withoutButtonContent,
            EmailTemplateKey::REGISTRATION_DENIED => $withoutButtonContent,
            EmailTemplateKey::ACCOUNT_ACTIVATION => $withoutButtonContent,
            EmailTemplateKey::ACCOUNT_SUSPENDED => $withoutButtonContent,
            EmailTemplateKey::ACCOUNT_UNBLOCK => $withoutButtonContent,
            EmailTemplateKey::ACCOUNT_BLOCK => $withoutButtonContent,
            EmailTemplateKey::DIGITAL_PRODUCT_DOWNLOAD => ['product_information', 'button_content', 'banner_image'],
            EmailTemplateKey::DIGITAL_PRODUCT_OTP => $withoutButtonContent,
            EmailTemplateKey::ORDER_PLACE => ['icon', 'product_information', 'banner_image'],
            EmailTemplateKey::ORDER_DELIVERED => $withoutButtonContent,
            EmailTemplateKey::FORGET_PASSWORD => $withoutButtonContent,
            EmailTemplateKey::ORDER_RECEIVED => ['icon', 'product_information', 'button_content', 'banner_image'],
            EmailTemplateKey::ADD_FUND_TO_WALLET => $withoutButtonContent,
            EmailTemplateKey::RESET_PASSWORD_VERIFICATION => $withoutButtonContent,
        ][$templateName];
    }

    private function defaultTitle(string $templateName): string
    {
        return [
            EmailTemplateKey::REGISTRATION => 'Registration Complete',
            EmailTemplateKey::REGISTRATION_VERIFICATION => 'Registration Verification',
            EmailTemplateKey::REGISTRATION_FROM_POS => 'Registration Complete',
            EmailTemplateKey::REGISTRATION_APPROVED => 'Registration Approved',
            EmailTemplateKey::REGISTRATION_DENIED => 'Registration Denied',
            EmailTemplateKey::ACCOUNT_ACTIVATION => 'Account Activation',
            EmailTemplateKey::ACCOUNT_SUSPENDED => 'Account Suspended',
            EmailTemplateKey::ACCOUNT_UNBLOCK => 'Account Unblocked',
            EmailTemplateKey::ACCOUNT_BLOCK => 'Account Blocked',
            EmailTemplateKey::DIGITAL_PRODUCT_DOWNLOAD => 'Congratulations',
            EmailTemplateKey::DIGITAL_PRODUCT_OTP => 'Digital Product Download OTP Verification',
            EmailTemplateKey::ORDER_PLACE => 'Order'.' # '.'{orderId}'.' Has Been Placed Successfully!',
            EmailTemplateKey::FORGET_PASSWORD => 'Change Password Request',
            EmailTemplateKey::ORDER_RECEIVED => 'New Order Received',
            EmailTemplateKey::ADD_FUND_TO_WALLET => 'Transaction Successful',
            EmailTemplateKey::RESET_PASSWORD_VERIFICATION => 'OTP Verification For Password Reset',
        ][$templateName];
    }

    private function defaultBody(string $userType, string $templateName): string
    {
        $recipient = match ($userType) {
            'admin' => '{adminName}',
            'customer' => '{userName}',
            'vendor' => '{vendorName}',
            'delivery-man' => '{deliveryManName}',
        };

        $companyName = getWebConfig(name: 'company_name');
        $siteUrl = url('/');
        $verificationCode = '<p><b>Hi '.$recipient.',</b></p><p>Your verification code is</p>';
        $siteLink = '<div><br></div><div>Meanwhile, click here to visit the'.$companyName.' shop website</div><div><font color="#0000ff"> <a href="'.$siteUrl.'" target="_blank">'.$siteUrl.'</a></font></div>';

        return [
            EmailTemplateKey::REGISTRATION => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Congratulation! Your registration request has been send to admin successfully! Please wait until admin reviewal. </div><div><br></div><div>meanwhile click here to visit the '.$companyName.' Shop Website</div><div><font color="#0000ff"> <a href="'.$siteUrl.'" target="_blank">'.$siteUrl.'</a></font></div>',
            EmailTemplateKey::REGISTRATION_VERIFICATION => $verificationCode,
            EmailTemplateKey::REGISTRATION_FROM_POS => '<p><b>Hi '.$recipient.',</b></p><p>Thank you for joining '.$companyName.' Shop.If you want to become a registered customer then reset your password below by using this email. Then you’ll be able to explore the website and app as a registered customer.</p>',
            EmailTemplateKey::REGISTRATION_APPROVED => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your registration request has been approved by admin. Now you can complete your store setting and start selling your product on '.$companyName.' Shop. </div>'.$siteLink,
            EmailTemplateKey::REGISTRATION_DENIED => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your registration request has been denied by admin. Please contact with admin or support center if you have any queries.</div>'.$siteLink,
            EmailTemplateKey::ACCOUNT_ACTIVATION => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your account suspension has been revoked by admin. From now you can access your app and panel again Please contact us for any queries we’re always happy to help.</div>'.$siteLink,
            EmailTemplateKey::ACCOUNT_SUSPENDED => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your account access has been suspended by admin.From now you can access your app and panel again Please contact us for any queries we’re always happy to help.</div>'.$siteLink,
            EmailTemplateKey::ACCOUNT_UNBLOCK => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your account has been successfully unblocked. We appreciate your cooperation in resolving this issue. Thank you for your understanding and patience. </div>'.$siteLink,
            EmailTemplateKey::ACCOUNT_BLOCK => '<div><b>Hi '.$recipient.',</b></div><div><b><br></b></div><div>Your account has been blocked due to suspicious activity by the admin .To resolve this issue please contact with admin or support center. We apologize for any inconvenience caused.</div><div><br></div><div>Meanwhile, click here to visit the'.$companyName.'shop website</div><div><font color="#0000ff"> <a href="'.$siteUrl.'" target="_blank">'.$siteUrl.'</a></font></div>',
            EmailTemplateKey::DIGITAL_PRODUCT_DOWNLOAD => '<p>Thank you for choosing '.$companyName.' shop! Your digital product is ready for download. To download your product use your email <b>{emailId}</b> and order # {orderId} below.</b><br></p>',
            EmailTemplateKey::DIGITAL_PRODUCT_OTP => $verificationCode,
            EmailTemplateKey::ORDER_PLACE => '<p><b>Hi '.$recipient.',</b></p><p>Your order from {vendorName} has been placed to know the current status of your order click track order</p>',
            EmailTemplateKey::FORGET_PASSWORD => '<p><b>Hi '.$recipient.',</b></p><p>Please click the link below to change your password.</p>',
            EmailTemplateKey::ORDER_RECEIVED => '<p><b>Hi '.$recipient.',</b></p><p>We have sent you this email to notify that you have a new order.You will be able to see your orders after login to your panel.</p>',
            EmailTemplateKey::ADD_FUND_TO_WALLET => '<div style="text-align: center; ">Amount successfully credited to your wallet .</div><div style="text-align: center; "><br></div>',
            EmailTemplateKey::RESET_PASSWORD_VERIFICATION => $verificationCode,
        ][$templateName];
    }
}
