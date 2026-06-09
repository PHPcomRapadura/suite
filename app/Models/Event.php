<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'edition', 'description',
        'starts_at', 'ends_at', 'location', 'is_online',
        'status', 'is_accepting_talks', 'max_attendees',
        'cover_image', 'logo', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_online' => 'boolean',
            'is_accepting_talks' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cfp(): HasOne
    {
        return $this->hasOne(EventCfp::class);
    }

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class);
    }

    public function site(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EventSiteConfig::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(EventSponsor::class)->orderBy('sort_order');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(EventScheduleItem::class)->orderBy('starts_at')->orderBy('sort_order');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(EventExpense::class)->orderBy('date', 'desc');
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelado';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['encerrado', 'cancelado']);
    }
}
