<?php

namespace App\Enums;

enum UserRole
{
    const ADMIN = 'admin';
    const EMPLOYEE = 'employee';

    /**
     * `admins.admin_role_id` of the super admin — the seeded role that bypasses the
     * per-module permission checks. Every other admin row is an employee.
     */
    const SUPER_ADMIN_ROLE_ID = 1;
}
