<?php

use App\Livewire\KnowledgeBase;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('knowledge search enforces document visibility', function () {
    $project = Project::factory()->create();
    $document = ProjectDocument::factory()->create([
        'project_id' => $project->id,
        'title' => 'Management Brief',
        'type' => 'file',
        'visibility' => 'management',
        'is_knowledge_base' => true,
    ]);

    DB::table('kb_index')->insert([
        'doc_id' => $document->id,
        'project_id' => $project->id,
        'title' => $document->title,
        'body' => 'confidentialnebula planning material',
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(KnowledgeBase::class)
        ->set('q', 'confidentialnebula')
        ->call('search')
        ->assertSet('results', []);

    Livewire::actingAs(User::factory()->create(['access_level' => 'management']))
        ->test(KnowledgeBase::class)
        ->set('q', 'confidentialnebula')
        ->call('search')
        ->assertSet('results', fn (array $results) => count($results) === 1 && $results[0]['id'] === $document->id);
});
