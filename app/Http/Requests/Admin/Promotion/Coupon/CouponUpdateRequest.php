<?php

namespace App\Http\Requests\Admin\Promotion\Coupon;

/**
 * An edit is held to the same rules as a create. The only difference — a coupon keeps its own code —
 * is already handled by the store request's code rule, which ignores the row named by the route.
 */
class CouponUpdateRequest extends CouponStoreRequest {}
