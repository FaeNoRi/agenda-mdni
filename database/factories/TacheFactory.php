<?php

namespace Database\Factories;

use App\Models\Tache;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tache> */
class TacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(4),
            'date_limite' => now()->addWeek()->toDateString(),
            'statut' => 'a_faire',
        ];
    }
}
