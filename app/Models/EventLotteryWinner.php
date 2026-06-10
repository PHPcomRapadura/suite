<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLotteryWinner extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'participant_id', 'position', 'drawn_at'];

    protected function casts(): array
    {
        return ['drawn_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }
}
