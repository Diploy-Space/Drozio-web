<?php

namespace Tests\Feature\Contract\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

trait SeedsMobileAuth
{
    protected const SELLER_TOKEN = 'contract-seller-auth-token-000000000000001';

    private static int $mobileAuthBatch = 0;

    protected function actingAsApiCustomer(array $overrides = []): int
    {
        $batch = ++self::$mobileAuthBatch;

        $id = DB::table('users')->insertGetId(array_merge([
            'name' => 'Contract Customer',
            'f_name' => 'Contract',
            'l_name' => 'Customer',
            'email' => "contract.customer.{$batch}@example.test",
            'phone' => '+1500000'.str_pad((string) $batch, 4, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        Passport::actingAs(User::find($id), [], 'api');

        return $id;
    }

    /** @return array{id: int, token: string, shop_id: int} */
    protected function seedApiSeller(array $overrides = []): array
    {
        $batch = ++self::$mobileAuthBatch;

        $sellerId = DB::table('sellers')->insertGetId(array_merge([
            'f_name' => 'Contract',
            'l_name' => 'Vendor',
            'email' => "contract.vendor.{$batch}@example.test",
            'phone' => '+1600000'.str_pad((string) $batch, 4, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'status' => 'approved',
            'auth_token' => self::SELLER_TOKEN,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $shopId = DB::table('shops')->insertGetId([
            'seller_id' => $sellerId,
            'name' => "Contract Vendor Shop {$batch}",
            'slug' => "contract-vendor-shop-{$batch}",
            'address' => 'Addr',
            'contact' => '+10000000000',
            'banner' => 'b.png',
            'author_type' => 'seller',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $sellerId, 'token' => self::SELLER_TOKEN, 'shop_id' => $shopId];
    }

    /** @return array{Authorization: string} */
    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
