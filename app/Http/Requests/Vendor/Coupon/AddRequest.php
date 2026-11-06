<?php

namespace App\Http\Requests\Vendor\Coupon;

use App\Http\Requests\BaseRequest;
use App\Services\New\CouponService;
use Closure;
use Illuminate\Validation\Rule;

class AddRequest extends BaseRequest
{
    public function __construct(
        private readonly CouponService $couponService,
    ) {}

    public function rules(): array
    {
        $groups = $this->fieldGroups();

        return [
            'coupon_type' => ['required', Rule::in(array_keys($this->couponService->getCouponTypes(addedBy: CouponService::ADDED_BY_SELLER)))],
            'title' => ['required', 'max:80'],
            'code' => ['required', $this->availableCodeRule()],
            'min_purchase' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'expire_date' => ['required', 'date', 'after_or_equal:start_date'],
        ] + $this->targetingRules($groups['targeting']) + $this->discountRules($groups['discount']);
    }

    /** @return array{targeting: bool, discount: bool} */
    protected function fieldGroups(): array
    {
        return $this->couponService->getFieldVisibility()[$this->input('coupon_type')]
            ?? ['targeting' => false, 'discount' => false];
    }

    protected function targetingRules(bool $isTargeted): array
    {
        if (! $isTargeted) {
            return [];
        }

        return [
            'customer_id' => ['required'],
            'limit' => ['required', 'integer', 'min:'.CouponService::MIN_USAGE_LIMIT, 'max:'.CouponService::MAX_USAGE_LIMIT],
        ];
    }

    protected function discountRules(bool $hasDiscount): array
    {
        if (! $hasDiscount) {
            return [];
        }

        $isPercentage = $this->input('discount_type') === CouponService::DISCOUNT_TYPE_PERCENTAGE;

        return [
            'discount_type' => ['required', Rule::in(array_keys($this->couponService->getDiscountTypes()))],
            'discount' => $this->discountAmountRules($isPercentage),
            'max_discount' => $isPercentage
                ? ['required', 'numeric', 'gt:0']
                : ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_type.required' => translate('Coupon type is required').'!',
            'coupon_type.in' => translate('The selected coupon type is not available'),
            'title.required' => translate('Coupon title is required'),
            'title.max' => translate('Coupon title must not be longer than 80 characters'),
            'code.required' => translate('Coupon code is required'),
            'customer_id.required' => translate('Customer is required').'!',
            'limit.required' => translate('Limit for same user is required').'!',
            'limit.integer' => translate('Limit for same user must be a whole number'),
            'limit.min' => translate('Limit for same user must be at least 1'),
            'limit.max' => translate('Limit for same user is too large'),
            'discount_type.required' => translate('Discount type is required').'!',
            'discount_type.in' => translate('The selected discount type is not available'),
            'discount.required' => translate('Discount amount is required').'!',
            'discount.numeric' => translate('Discount amount must be a number'),
            'discount.min' => translate('Discount amount cannot be negative'),
            'discount.lte' => translate('The minimum purchase amount must be greater than discount amount'),
            'discount.lt' => translate('A percentage discount must be less than 100'),
            'max_discount.required' => translate('Max discount amount is required!'),
            'max_discount.numeric' => translate('Maximum discount must be a number'),
            'max_discount.min' => translate('Maximum discount cannot be negative'),
            'max_discount.gt' => translate('Maximum discount must be greater than 0'),
            'min_purchase.required' => translate('Minimum purchase is required').'!',
            'min_purchase.numeric' => translate('Minimum purchase must be a number'),
            'min_purchase.min' => translate('Minimum purchase cannot be negative'),
            'start_date.required' => translate('Start date is required'),
            'start_date.date' => translate('Please enter a valid start date'),
            'expire_date.required' => translate('Expire date is required'),
            'expire_date.date' => translate('Please enter a valid expire date'),
            'expire_date.after_or_equal' => translate('Expire date cannot be earlier than the start date'),
        ];
    }

    protected function availableCodeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $this->couponService->isCodeAvailable(code: $value, ignoreId: $this->route('id'))) {
                $fail(translate('This coupon code is already in use'));
            }
        };
    }

    protected function discountAmountRules(bool $isPercentage): array
    {
        $rules = ['required', 'numeric', 'min:0'];

        if ($isPercentage) {
            $rules[] = 'lt:100';
        } elseif (is_numeric($this->input('min_purchase'))) {
            $rules[] = 'lte:min_purchase';
        }

        return $rules;
    }
}
