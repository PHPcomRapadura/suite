<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['event_task_id', 'user_id', 'body'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EventTask::class, 'event_task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
