<?php

namespace Tests\Feature\Auth;

test('self-service registration returns staff to Google sign-in', function () {
    $this->get('/register')
        ->assertRedirect(route('login'));
});
