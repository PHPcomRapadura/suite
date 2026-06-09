<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'registration_order', 'first_name', 'last_name', 'email',
        'ticket_type', 'amount', 'purchased_at', 'payment_status',
        'checked_in', 'discount_coupon', 'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'amount' => 'decimal:2',
            'checked_in' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
