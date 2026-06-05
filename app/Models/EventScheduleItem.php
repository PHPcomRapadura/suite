<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Illuminate\Support\Carbon $starts_at
 */
class EventScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'talk_id', 'title', 'speaker_name',
        'starts_at', 'duration', 'room', 'type', 'sort_order', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }
}
