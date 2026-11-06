<?php

namespace App\Services\New\Auth;

use App\Enums\UserType;
use App\Enums\VendorStatus;
use App\Models\Seller;
use App\Traits\FileManagerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class VendorAuthService extends AuthService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    private const IMAGE_DIRECTORY = 'seller/';

    private const TOKEN_LENGTH = 50;

    public function __construct(Seller $seller)
    {
        parent::__construct($seller);
    }

    protected function identityColumns(): array
    {
        return ['email', 'phone'];
    }

    public function userType(): string
    {
        return UserType::SELLER;
    }

    public function findByAccessToken(string $token, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where('auth_token', $token)->first();
    }

    public function create(array $data, ?UploadedFile $image = null): ?Model
    {
        return $this->query()->create([
            'f_name' => $data['f_name'],
            'l_name' => $data['l_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'status' => VendorStatus::PENDING,
            'image' => $image === null ? null : $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
        ]);
    }

    public function issueAccessToken(Model $account): string
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $this->query()->where('id', $account->id)->update(['auth_token' => $token]);

        return $token;
    }

    public function revokeAccessToken(Model $account): bool
    {
        return (bool) $this->query()->where('id', $account->id)->update([
            'auth_token' => null,
            'cm_firebase_token' => null,
        ]);
    }

    public function clearWebPushToken(mixed $id): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['cm_firebase_token_web' => null]);
    }

    public function accountStatusReason(Model $account): ?string
    {
        return match ($account->status) {
            VendorStatus::APPROVED => null,
            VendorStatus::PENDING => self::REASON_PENDING_APPROVAL,
            default => self::REASON_NOT_APPROVED,
        };
    }
}
