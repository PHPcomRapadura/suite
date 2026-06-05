<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSponsor>
 */
class EventSponsorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'    => Event::factory(),
            'name'        => $this->faker->company(),
            'logo_url'    => null,
            'website_url' => $this->faker->url(),
            'level'       => 'rapadura_tradicional',
            'sort_order'  => 0,
        ];
    }
}
