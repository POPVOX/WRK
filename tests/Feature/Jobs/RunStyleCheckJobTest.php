<?php

use App\Jobs\RunStyleCheck;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('stores style check suggestions and updates document flags', function () {
    Storage::fake('local');

    $project = Project::factory()->create();
    $doc = ProjectDocument::factory()->create([
        'project_id' => $project->id,
        'file_path' => 'README.md',
        'file_type' => 'md',
        'title' => 'README.md',
    ]);

    // Mock Anthropic
    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [[
                'type' => 'text',
                'text' => json_encode([
                    'suggestions' => [
                        [
                            'original' => 'Bad phrase',
                            'replacement' => 'Improved phrase',
                            'rule' => 'Clarity',
                            'importance' => 'medium',
                        ],
                    ],
                ]),
            ]],
        ], 200),
    ]);

    $content = "# Title\n\nBad phrase in document.";
    $job = new RunStyleCheck($project->id, $doc->id, $doc->file_path, $content);
    $job->handle();

    $doc->refresh();
    expect($doc->ai_indexed)->toBeTrue();
    expect($doc->ai_summary)->toContain('suggestion');
    expect($doc->content_hash)->not()->toBeNull();

    $hash = $doc->content_hash;
    Storage::disk('local')->assertExists("style_checks/{$doc->id}-{$hash}.json");
});
