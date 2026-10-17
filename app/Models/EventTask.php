<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id', 'title', 'description', 'status', 'priority',
        'assigned_to', 'due_date', 'sort_order', 'created_by',
    ];

    // Espelha os defaults da migration para que o model recém-criado já os tenha em memória
    protected $attributes = [
        'status' => 'a_fazer',
        'priority' => 'media',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<EventTaskComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(EventTaskComment::class)->orderBy('created_at', 'asc');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'concluida';
    }
}
