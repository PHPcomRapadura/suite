<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSiteConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSiteConfig>
 */
class EventSiteConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'        => Event::factory(),
            'is_published'    => false,
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
            'hero_tagline'    => null,
            'ticket_url'      => null,
            'code_of_conduct' => null,
            'faq'             => null,
            'created_by'      => User::factory()->admin(),
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
