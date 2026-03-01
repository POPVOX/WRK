<?php

use App\Models\User;

test('admin integrations warns when webhook signature verification is disabled', function () {
    config()->set('services.box.access_token', null);
    config()->set('services.box.client_id', null);
    config()->set('services.box.client_secret', null);
    config()->set('services.box.enterprise_id', null);
    config()->set('services.box.user_id', null);
    config()->set('services.box.webhook.enforce_signature', false);
    config()->set('services.box.webhook.primary_signature_key', null);
    config()->set('services.box.webhook.secondary_signature_key', null);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.integrations'))
        ->assertOk()
        ->assertSee('Signature verification is disabled.');
});
