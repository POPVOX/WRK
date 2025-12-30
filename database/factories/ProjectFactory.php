<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['planning', 'active', 'completed', 'on_hold']),
            'is_initiative' => false,
            'start_date' => fake()->date(),
            'tags' => [],
        ];
    }

    public function initiative(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_initiative' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }
}
