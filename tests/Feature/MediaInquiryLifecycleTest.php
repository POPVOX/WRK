<?php

use App\Livewire\Media\MediaIndex;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('inquiry double submission creates one record and inquiries can be edited and deleted', function () {
    $user = User::factory()->profileCompleted()->create();

    $component = Livewire::actingAs($user)
        ->test(MediaIndex::class)
        ->call('setTab', 'inquiries')
        ->call('openInquiryModal')
        ->set('inquiryForm.subject', 'AI in Congress')
        ->set('inquiryForm.description', 'A reporter is requesting information about AI policy.')
        ->set('inquiryForm.received_at', now()->format('Y-m-d\TH:i'))
        ->set('inquiryForm.journalist_name', 'Owen Reporter')
        ->set('inquiryForm.outlet_name', 'Example News')
        ->call('saveInquiry')
        ->assertHasNoErrors()
        ->call('saveInquiry')
        ->assertHasNoErrors();

    expect(Inquiry::count())->toBe(1);

    $inquiry = Inquiry::firstOrFail();

    $component
        ->call('setTab', 'inquiries')
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->call('openInquiryModal', $inquiry->id)
        ->set('inquiryForm.subject', 'AI policy in Congress')
        ->call('saveInquiry')
        ->assertHasNoErrors();

    expect($inquiry->fresh()->subject)->toBe('AI policy in Congress');

    $component
        ->call('deleteInquiry', $inquiry->id)
        ->assertDispatched('notify');

    expect(Inquiry::count())->toBe(0);
});

test('replayed inquiry submission token is idempotent across requests', function () {
    $user = User::factory()->profileCompleted()->create();
    $submissionToken = (string) Str::uuid();

    $newSubmission = function () use ($user, $submissionToken) {
        return Livewire::actingAs($user)
            ->test(MediaIndex::class)
            ->call('openInquiryModal')
            ->set('inquiryForm.submission_token', $submissionToken)
            ->set('inquiryForm.subject', 'Repeated submission')
            ->set('inquiryForm.description', 'The same browser submission was sent twice.')
            ->set('inquiryForm.received_at', now()->format('Y-m-d\TH:i'));
    };

    $newSubmission()->call('saveInquiry')->assertHasNoErrors();
    $newSubmission()->call('saveInquiry')->assertHasNoErrors();

    expect(Inquiry::where('submission_token', $submissionToken)->count())->toBe(1);
});
