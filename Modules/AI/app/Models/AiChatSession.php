<?php

namespace Modules\AI\app\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChatSession extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'customer_id',
        'guest_id',
        'business_module',
        'title',
        'locale',
        'context',
        'last_activity_at',
    ];

    protected $hidden = [
        'business_module',
    ];

    protected $casts = [
        'context' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id')->orderBy('created_at');
    }

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForGuest($query, string $guestId)
    {
        return $query->where('guest_id', $guestId);
    }
}
