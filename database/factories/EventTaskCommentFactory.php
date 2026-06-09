<?php

namespace Database\Factories;

use App\Models\EventTask;
use App\Models\EventTaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTaskComment>
 */
class EventTaskCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_task_id' => EventTask::factory(),
            'user_id' => User::factory(),
            'body' => fake('pt_BR')->paragraph(),
        ];
    }
}
