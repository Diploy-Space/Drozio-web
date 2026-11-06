<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $customer_id
 * @property bool $is_guest
 * @property string|null $contact_person_name
 * @property string|null $email
 * @property string $address_type
 * @property string|null $address
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $phone
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $state
 * @property string|null $country
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_billing
 * @property bool $is_saved
 */
class ShippingAddress extends Model
{
    protected $guarded = [];

    /** @var array */
    protected $fillable = [
        'customer_id',
        'is_guest',
        'contact_person_name',
        'email',
        'address_type',
        'address',
        'city',
        'zip',
        'phone',
        'state',
        'country',
        'latitude',
        'longitude',
        'is_billing',
        'is_saved',
    ];

    /** @var array */
    protected $casts = [
        'is_guest' => 'boolean',
        'is_billing' => 'boolean',
        'is_saved' => 'boolean',
    ];
}
