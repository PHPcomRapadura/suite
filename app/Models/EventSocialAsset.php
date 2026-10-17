<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSocialAsset extends Model
{
    protected $fillable = [
        'event_id', 'type', 'talk_id', 'sponsor_id', 'subject_key', 'format', 'url', 'path',
    ];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Talk, $this> */
    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    /** @return BelongsTo<EventSponsor, $this> */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(EventSponsor::class);
    }
}
