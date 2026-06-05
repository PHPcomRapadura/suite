<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSiteConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'is_published', 'layout',
        'primary_color', 'secondary_color', 'font',
        'hero_tagline', 'ticket_url',
        'code_of_conduct', 'faq', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'faq'          => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
