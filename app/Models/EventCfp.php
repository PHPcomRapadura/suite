<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCfp extends Model
{
    use HasFactory;

    protected $table = 'event_cfp';

    protected $fillable = [
        'event_id', 'opens_at', 'closes_at',
        'speaker_guide', 'max_talks_per_speaker', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opens_at'  => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class, 'event_id', 'event_id');
    }

    public function status(): string
    {
        $now = now();
        if ($now->lt($this->opens_at))  return 'aguardando';
        if ($now->lte($this->closes_at)) return 'aberto';
        return 'encerrado';
    }
}
