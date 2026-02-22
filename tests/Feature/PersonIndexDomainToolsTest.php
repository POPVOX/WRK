<?php

use App\Livewire\People\PersonIndex;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('contacts can be filtered by email domain and bulk assigned to an organization', function () {
    $user = User::factory()->create();
    $organization = Organization::create(['name' => 'Domain Matched Org']);

    $personA = Person::create([
        'name' => 'Domain Match A',
        'email' => 'a@shared-domain.org',
    ]);
    $personB = Person::create([
        'name' => 'Domain Match B',
        'email' => 'b@shared-domain.org',
    ]);
    $personOther = Person::create([
        'name' => 'Other Domain Person',
        'email' => 'c@other-domain.org',
    ]);

    Livewire::actingAs($user)
        ->test(PersonIndex::class)
        ->set('filterEmailDomain', '@shared-domain.org')
        ->call('selectFiltered')
        ->set('bulkOrgId', $organization->id)
        ->call('applyBulkOrganization');

    expect(Person::find($personA->id)->organization_id)->toBe($organization->id);
    expect(Person::find($personB->id)->organization_id)->toBe($organization->id);
    expect(Person::find($personOther->id)->organization_id)->toBeNull();
});

test('contacts can be sorted by email domain', function () {
    $user = User::factory()->create();

    Person::create([
        'name' => 'Zulu Contact',
        'email' => 'zulu@zeta-domain.org',
    ]);
    Person::create([
        'name' => 'Alpha Contact',
        'email' => 'alpha@alpha-domain.org',
    ]);
    Person::create([
        'name' => 'No Email Contact',
        'email' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PersonIndex::class)
        ->set('viewMode', 'table')
        ->set('sortBy', 'email_domain')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder([
            'Alpha Contact',
            'Zulu Contact',
            'No Email Contact',
        ]);
});

test('contacts can be sorted by linkedin url', function () {
    $user = User::factory()->create();

    Person::create([
        'name' => 'Zulu Link',
        'linkedin_url' => 'https://www.linkedin.com/in/zulu-link',
    ]);
    Person::create([
        'name' => 'Alpha Link',
        'linkedin_url' => 'http://linkedin.com/in/alpha-link',
    ]);
    Person::create([
        'name' => 'No Link',
        'linkedin_url' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PersonIndex::class)
        ->set('viewMode', 'table')
        ->set('sortBy', 'linkedin_url')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder([
            'Alpha Link',
            'Zulu Link',
            'No Link',
        ]);
});
