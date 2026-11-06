<?php

namespace App\Enums;

enum AuthErrorCode
{
    const INVALID_CREDENTIALS = 'invalid_credentials';

    const ACCOUNT_INACTIVE = 'account_inactive';

    const VENDOR_PENDING_APPROVAL = 'vendor_pending_approval';

    const VENDOR_NOT_APPROVED = 'vendor_not_approved';

    const ACCOUNT_NOT_FOUND = 'account_not_found';

    const ACCOUNT_EMAIL_RESERVED = 'account_email_reserved';

    const ACCOUNT_PHONE_RESERVED = 'account_phone_reserved';

    const UNAUTHENTICATED = 'unauthenticated';

    const LOGIN_BLOCKED = 'login_blocked';

    const TOO_MANY_REQUESTS = 'too_many_requests';

    const LOGIN_METHOD_DISABLED = 'login_method_disabled';

    const SOCIAL_MEDIUM_DISABLED = 'social_medium_disabled';

    const OTP_INVALID = 'otp_invalid';

    const OTP_EXPIRED = 'otp_expired';

    const OTP_BLOCKED = 'otp_blocked';

    const OTP_RESEND_WAIT = 'otp_resend_wait';

    const PROVIDER_VERIFICATION_FAILED = 'provider_verification_failed';

    const NOTIFICATION_CHANNEL_UNAVAILABLE = 'notification_channel_unavailable';

    const ACCOUNT_DELETE_BLOCKED = 'account_delete_blocked';

    const ACCOUNT_DELETE_ONGOING_ORDER = 'ongoing_order_left';

    const ACCOUNT_DELETE_COMMISSION_DUE = 'admin_commission_not_paid';

    const ACCOUNT_DELETE_DELIVERY_MAN_DUE = 'delivery_man_transaction_left';

    const DELIVERY_AREA_RESTRICTED = 'delivery_area_restricted';

    const MAP_SERVICE_UNAVAILABLE = 'map_service_unavailable';

    const DELIVERY_IN_PROGRESS = 'delivery_in_progress';

    const INSUFFICIENT_BALANCE = 'insufficient_balance';
}
