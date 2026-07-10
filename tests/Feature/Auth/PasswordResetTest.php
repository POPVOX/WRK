<?php

namespace Tests\Feature\Auth;

test('password reset requests return staff to Google sign-in', function () {
    $this->get('/forgot-password')
        ->assertRedirect(route('login'));
});

test('password reset tokens return staff to Google sign-in', function () {
    $this->get('/reset-password/example-token')
        ->assertRedirect(route('login'));
});
