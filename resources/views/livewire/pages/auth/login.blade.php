<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
}; ?>

<div class="space-y-5">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-900/20 dark:text-indigo-200">
        WRK uses Google Workspace sign-in only. Password login is disabled.
    </div>

    <x-input-error :messages="$errors->get('form.email')" class="mt-2" />

    <a href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Continue with Google
    </a>
</div>
