<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCfp>
 */
class EventCfpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'              => Event::factory(),
            'opens_at'              => now()->addDays(7),
            'closes_at'             => now()->addDays(37),
            'speaker_guide'         => null,
            'max_talks_per_speaker' => null,
            'created_by'            => User::factory()->admin(),
        ];
    }

    public function aberto(): static
    {
        return $this->state([
            'opens_at'  => now()->subDay(),
            'closes_at' => now()->addDays(30),
        ]);
    }

    public function encerrado(): static
    {
        return $this->state([
            'opens_at'  => now()->subDays(60),
            'closes_at' => now()->subDays(30),
        ]);
    }
}
