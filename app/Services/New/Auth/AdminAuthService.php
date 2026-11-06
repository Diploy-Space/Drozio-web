<?php

namespace App\Services\New\Auth;

use App\Enums\UserRole;
use App\Enums\UserType;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class AdminAuthService extends AuthService
{
    public function __construct(Admin $admin)
    {
        parent::__construct($admin);
    }

    protected function identityColumns(): array
    {
        return ['email'];
    }

    public function userType(): string
    {
        return UserType::ADMIN;
    }

    public function accountStatusReason(Model $account): ?string
    {
        return (int) $account->status === 1 ? null : self::REASON_INACTIVE;
    }

    public function loginRoleFor(Model $account): string
    {
        return (int) $account->admin_role_id === UserRole::SUPER_ADMIN_ROLE_ID ? UserRole::ADMIN : UserRole::EMPLOYEE;
    }
}
