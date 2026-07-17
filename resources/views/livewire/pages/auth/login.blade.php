<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
}; ?>

<div class="space-y-5">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="rounded-lg border border-[#d9c8b7] bg-[#f3eee3] px-4 py-3 text-sm text-[#4a453b]">
        WRKBench uses Google Workspace sign-in only. Password login is disabled.
    </div>

    <x-input-error :messages="$errors->get('form.email')" class="mt-2" />

    <a href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center rounded-md border border-[#b8aa98] bg-[#26221c] px-4 py-2.5 text-sm font-semibold text-[#f7f3ec] shadow-sm hover:bg-[#8a4b2d] focus:outline-none focus:ring-2 focus:ring-[#8a4b2d] focus:ring-offset-2">
        Continue with Google
    </a>
</div>
