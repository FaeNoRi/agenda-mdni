<?php

namespace Database\Factories;

use App\Models\Projet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Projet> */
class ProjetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'date_limite' => now()->addWeeks(2)->toDateString(),
            'etat' => 'en_cours',
        ];
    }
}
