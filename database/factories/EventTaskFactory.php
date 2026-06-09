<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTask>
 */
class EventTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => fake('pt_BR')->sentence(4),
            'description' => null,
            'status' => fake()->randomElement(['a_fazer', 'em_andamento', 'em_revisao', 'concluida']),
            'priority' => fake()->randomElement(['baixa', 'media', 'alta']),
            'assigned_to' => null,
            'due_date' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => User::factory(),
        ];
    }

    public function aFazer(): static
    {
        return $this->state(['status' => 'a_fazer']);
    }

    public function emAndamento(): static
    {
        return $this->state(['status' => 'em_andamento']);
    }

    public function concluida(): static
    {
        return $this->state(['status' => 'concluida']);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => 'a_fazer',
        ]);
    }

    public function withDueDate(string $date): static
    {
        return $this->state(['due_date' => $date]);
    }
}
