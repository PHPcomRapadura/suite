<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake('pt_BR')->sentence(3);
        $startsAt = fake()->dateTimeBetween('+1 month', '+6 months');
        $endsAt = (clone $startsAt)->modify('+8 hours');

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'edition' => fake()->numberBetween(1, 10),
            'description' => fake('pt_BR')->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'location' => fake('pt_BR')->city().' — CE',
            'is_online' => false,
            'status' => 'rascunho',
            'is_accepting_talks' => false,
            'max_attendees' => fake()->randomElement([null, 100, 200, 300, 500]),
            'cover_image' => null,
            'logo' => null,
            'created_by' => User::factory(),
        ];
    }

    public function rascunho(): static
    {
        return $this->state(['status' => 'rascunho']);
    }

    public function publicado(): static
    {
        return $this->state(['status' => 'publicado']);
    }

    public function encerrado(): static
    {
        return $this->state(['status' => 'encerrado']);
    }

    public function cancelado(): static
    {
        return $this->state(['status' => 'cancelado']);
    }

    public function online(): static
    {
        return $this->state(['is_online' => true, 'location' => null]);
    }
}
