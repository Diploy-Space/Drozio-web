<?php

namespace App\Http\Controllers\Vendor;

use App\Events\CustomerRegistrationEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Customer\AddRequest;
use App\Services\New\CustomerService;
use App\Services\New\PasswordResetService;
use App\Services\New\ShippingAddressService;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerController extends BaseController
{
    use EmailTemplateTrait;

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly PasswordResetService $passwordResetService,
        private readonly ShippingAddressService $shippingAddressService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse {}

    public function getList(Request $request): JsonResponse
    {
        $customers = $this->customerService->getNameOptions(
            searchValue: $request['searchValue'],
            excludesWalkingCustomer: (bool) ($request['avoid_walking_customer'] ?? false),
            dataLimit: getWebConfig(name: 'pagination_limit'),
        );

        return response()->json($customers);
    }

    public function add(AddRequest $request): JsonResponse
    {
        $this->passwordResetService->create(data: [
            'identity' => $request['phone'],
            'user_type' => 'customer',
        ]);
        $customer = $this->customerService->create(data: $request->only([
            'f_name', 'l_name', 'email', 'phone', 'country', 'city', 'zip_code', 'address', 'password',
        ]));
        $this->shippingAddressService->create(data: [
            'customer_id' => $customer['id'],
            'contact_person_name' => $request['f_name'].' '.$request['l_name'],
            'address_type' => ShippingAddressService::TYPE_HOME,
            'address' => $request['address'],
            'city' => $request['city'],
            'zip' => $request['zip_code'],
            'country' => $request['country'],
            'phone' => $request['phone'],
        ]);
        $resetRoute = route('customer.auth.recover-password');
        $data = [
            'userName' => $request['f_name'],
            'userType' => 'customer',
            'templateName' => 'registration-from-pos',
            'subject' => translate('Customer registration completed successfully'),
            'title' => translate('Welcome to').' '.getWebConfig(name: 'company_name').'!',
            'resetPassword' => $resetRoute,
            'message' => translate('Thank you for joining').' '.getWebConfig(name: 'company_name').'.'.translate('To become a registered customer, reset your password with this phone.').' '.($request['phone']).'.'.translate('Then explore the website and app as a registered customer.').'.',
        ];
        event(new CustomerRegistrationEvent(email: $request['email'], data: $data));

        return response()->json(['success' => true, 'message' => translate(':item added successfully', ['item' => translate('Customer')])]);
    }
}
