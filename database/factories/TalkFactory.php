<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Talk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talk>
 */
class TalkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'     => Event::factory(),
            'speaker_id'   => Speaker::factory(),
            'title'        => fake('pt_BR')->sentence(5),
            'abstract'     => fake('pt_BR')->paragraph(3),
            'duration'     => fake()->randomElement(['25', '50']),
            'level'        => fake()->randomElement(['iniciante', 'intermediario', 'avancado']),
            'status'       => 'submetida',
            'feedback'     => null,
            'submitted_at' => now(),
        ];
    }

    public function submetida(): static { return $this->state(['status' => 'submetida']); }
    public function emAnalise(): static { return $this->state(['status' => 'em_analise']); }
    public function aprovada(): static  { return $this->state(['status' => 'aprovada']); }

    public function rejeitada(): static
    {
        return $this->state(['status' => 'rejeitada', 'feedback' => fake('pt_BR')->sentence()]);
    }

    public function cancelada(): static { return $this->state(['status' => 'cancelada']); }
}
