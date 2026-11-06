<?php

namespace App\Enums;

enum SessionKey
{
    const ADMIN_RECAPTCHA_KEY = 'adminRecaptchaSessionKey';

    const EMPLOYEE_RECAPTCHA_KEY = 'employeeRecaptchaSessionKey';

    const VENDOR_RECAPTCHA_KEY = 'vendorRecaptchaSessionKey';

    const ADMIN_FORGOT_PASSWORD_ROLE = 'adminForgotPasswordRole';

    const CURRENT_USER = 'current_user';

    const CART_NAME = 'cart_name';

    const LAST_ORDER = 'last_order';

    const PRODUCT_COMPARE_LIST = 'compare_list';

    const WISH_LIST = 'wish_list';

    const FORGOT_PASSWORD_IDENTIFY = 'forgot_password_identity';

    const CHECKOUT_BLOCKERS = 'checkout_blockers';

    const RECENT_USER_ROUTES_HISTORY = 'recent_user_routes_history';

    const CUSTOMER_LOGIN_REDIRECT_URL = 'keep_customer_login_redirect_url';
}
