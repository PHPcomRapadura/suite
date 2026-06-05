<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventScheduleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventScheduleItem>
 */
class EventScheduleItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'     => Event::factory(),
            'talk_id'      => null,
            'title'        => $this->faker->sentence(4),
            'speaker_name' => $this->faker->name(),
            'starts_at'    => now()->setTime(9, 0),
            'duration'     => 50,
            'room'         => null,
            'type'         => 'palestra',
            'sort_order'   => 0,
            'created_by'   => null,
        ];
    }
}
