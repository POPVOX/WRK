<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    protected $model = ProjectDocument::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'type' => 'file',
            'file_path' => fake()->word().'.md',
            'file_type' => 'md',
            'is_knowledge_base' => true,
            'ai_indexed' => false,
        ];
    }

    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'link',
            'url' => fake()->url(),
            'file_path' => null,
            'file_type' => null,
        ]);
    }

    public function indexed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_indexed' => true,
            'content_hash' => fake()->sha256(),
        ]);
    }
}
