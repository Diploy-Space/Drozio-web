<?php

namespace App\Http\Resources\V6\Customer;

use App\Enums\BusinessModule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * @param  array<string, int>  $orderCountsByBusinessModule
     * @param  array<string, int|bool>  $auctionProfileData
     */
    public function __construct(
        mixed $resource,
        private readonly array $orderCountsByBusinessModule = [],
        private readonly array $auctionProfileData = [],
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'country' => $this->country,
            'city' => $this->city,
            'zip' => $this->zip,
            'street_address' => $this->street_address,
            'is_phone_verified' => (bool) $this->is_phone_verified,
            'is_email_verified' => (bool) $this->is_email_verified,
            'email_verified_at' => $this->email_verified_at,
            'wallet_balance' => (float) ($this->wallet_balance ?? 0),
            'loyalty_point' => (float) ($this->loyalty_point ?? 0),
            'referral_code' => $this->referral_code,
            'referral_user_count' => $this->whenCounted('referredCustomers'),
            'orders_count' => $this->whenCounted('orders'),
            'retail_orders_count' => $this->orderCountsByBusinessModule[BusinessModule::RETAIL] ?? 0,
            'wholesale_orders_count' => $this->orderCountsByBusinessModule[BusinessModule::WHOLESALE] ?? 0,
            'auction_products_count' => (int) ($this->auctionProfileData['auction_products_count'] ?? 0),
            'auction_bids_count' => (int) ($this->auctionProfileData['auction_bids_count'] ?? 0),
            'auction_saved_products_count' => (int) ($this->auctionProfileData['auction_saved_products_count'] ?? 0),
            'show_auction_menu_for_customer' => (bool) ($this->auctionProfileData['show_auction_menu_for_customer'] ?? false),
            'created_at' => $this->created_at,
        ];
    }
}
