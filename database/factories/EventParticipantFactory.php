<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;

        return [
            'event_id' => Event::factory(),
            'registration_order' => $order++,
            'first_name' => mb_strtoupper($this->faker->firstName(), 'UTF-8'),
            'last_name' => mb_strtoupper($this->faker->lastName(), 'UTF-8'),
            'email' => $this->faker->unique()->safeEmail(),
            'ticket_type' => 'Lote 1 - Geral',
            'amount' => $this->faker->randomFloat(2, 0, 200),
            'purchased_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'payment_status' => 'Aprovado',
            'checked_in' => false,
            'discount_coupon' => null,
            'payment_method' => null,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(['checked_in' => true]);
    }

    public function pending(): static
    {
        return $this->state(['payment_status' => 'Pendente']);
    }
}
