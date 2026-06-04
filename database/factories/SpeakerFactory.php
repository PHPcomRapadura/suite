<?php

namespace Database\Factories;

use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Speaker>
 */
class SpeakerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory()->palestrante(),
            'bio'          => fake('pt_BR')->paragraph(),
            'company'      => fake('pt_BR')->company(),
            'avatar_url'   => null,
            'phone_number' => null,
            'website'      => null,
            'twitter'      => fake()->userName(),
            'github'       => fake()->userName(),
            'linkedin'     => null,
        ];
    }
}
