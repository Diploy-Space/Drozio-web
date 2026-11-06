<?php

namespace App\Services\New;

use App\Models\GuestUser;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;

class GuestUserService extends BaseService
{
    public function __construct(GuestUser $guestUser)
    {
        parent::__construct($guestUser);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create(['ip_address' => $data['ip_address'] ?? null]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'ip_address' => $data['ip_address'] ?? null,
            'fcm_token' => $data['fcm_token'] ?? null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }
}
