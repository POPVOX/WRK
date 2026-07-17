<?php

use App\Livewire\Organizations\OrganizationShow;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('organization records present durable details and relationships as a document', function () {
    $user = User::factory()->create();
    $organization = Organization::query()->create([
        'name' => 'Civic Systems Lab',
        'type' => 'Nonprofit',
        'email' => 'hello@civicsystems.example',
        'website' => 'https://civicsystems.example',
        'description' => 'A partner focused on legislative capacity.',
        'status' => 'active',
    ]);
    Person::query()->create([
        'name' => 'Jordan Partner',
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($user)
        ->test(OrganizationShow::class, ['organization' => $organization])
        ->assertSee('People · Organizations')
        ->assertSee('Details')
        ->assertSee('hello@civicsystems.example')
        ->assertSee('Jordan Partner')
        ->assertSee('Documents')
        ->assertSee('Notes')
        ->assertSee('Meetings');
});
