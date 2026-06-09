<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventExpense>
 */
class EventExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'category' => fake()->randomElement([
                'alimentacao', 'transporte', 'hospedagem', 'equipamentos',
                'marketing', 'infraestrutura', 'palestrantes', 'premiacao', 'outros',
            ]),
            'description' => fake('pt_BR')->sentence(4),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'is_paid' => fake()->boolean(),
            'receipt_url' => null,
            'notes' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(['is_paid' => true]);
    }

    public function pending(): static
    {
        return $this->state(['is_paid' => false]);
    }

    public function withReceipt(): static
    {
        return $this->state(['receipt_url' => 'https://assets.example.com/receipt.pdf']);
    }
}
