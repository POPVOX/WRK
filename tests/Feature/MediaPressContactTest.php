<?php

use App\Livewire\Media\MediaIndex;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('press contacts tab offers contact creation instead of pitch creation', function () {
    $user = User::factory()->profileCompleted()->create();

    Livewire::actingAs($user)
        ->test(MediaIndex::class)
        ->call('setTab', 'contacts')
        ->assertSee('New Contact')
        ->assertDontSee('New Pitch')
        ->call('openContactModal')
        ->assertSet('showContactModal', true)
        ->set('contactForm.name', 'Alex Reporter')
        ->set('contactForm.email', 'alex@example.com')
        ->set('contactForm.title', 'Technology Reporter')
        ->set('contactForm.phone', '+1 202 555 0100')
        ->set('contactForm.outlet_name', 'Example Daily')
        ->call('saveContact')
        ->assertHasNoErrors()
        ->assertSet('showContactModal', false)
        ->assertDispatched('notify');

    $contact = Person::where('email', 'alex@example.com')->firstOrFail();

    expect($contact->name)->toBe('Alex Reporter')
        ->and($contact->is_journalist)->toBeTrue()
        ->and($contact->title)->toBe('Technology Reporter')
        ->and($contact->phone)->toBe('+1 202 555 0100')
        ->and($contact->contact_type)->toBe('media')
        ->and($contact->organization?->name)->toBe('Example Daily');
});
