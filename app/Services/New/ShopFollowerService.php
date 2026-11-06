<?php

namespace App\Services\New;

use App\Models\ShopFollower;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShopFollowerService extends BaseService
{
    public function __construct(ShopFollower $shopFollower)
    {
        parent::__construct($shopFollower);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'user_id' => $data['user_id'],
            'shop_id' => $data['shop_id'],
        ]);
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->query()->whereKey($id)->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function toggleFollow(int $customerId, int $shopId): bool
    {
        return DB::transaction(function () use ($customerId, $shopId): bool {
            $following = $this->findBy(params: ['user_id' => $customerId, 'shop_id' => $shopId]);

            if ($following !== null) {
                $this->delete(id: $following->id);

                return false;
            }

            $this->create(data: ['user_id' => $customerId, 'shop_id' => $shopId]);

            return true;
        });
    }
}
