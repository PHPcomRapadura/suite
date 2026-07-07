<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSocialAsset extends Model
{
    protected $fillable = [
        'event_id', 'format', 'url', 'path',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
