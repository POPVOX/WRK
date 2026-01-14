<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Activate Your Account')]
class ActivateAccount extends Component
{
    public ?User $user = null;

    public string $token = '';

    public bool $validToken = false;

    public bool $expired = false;

    public bool $alreadyActivated = false;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->user = User::where('activation_token', $token)->first();

        if (! $this->user) {
            $this->validToken = false;

            return;
        }

        // Check if already activated
        if ($this->user->activated_at) {
            $this->alreadyActivated = true;

            return;
        }

        // Check if expired
        if ($this->user->activation_token_expires_at && $this->user->activation_token_expires_at->isPast()) {
            $this->expired = true;

            return;
        }

        $this->validToken = true;
    }

    public function activate(): void
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $this->user || ! $this->validToken) {
            $this->dispatch('notify', type: 'error', message: 'Invalid activation token.');

            return;
        }

        $this->user->update([
            'password' => Hash::make($this->password),
            'activated_at' => now(),
            'activation_token' => null,
            'activation_token_expires_at' => null,
        ]);

        Auth::login($this->user);

        $this->dispatch('notify', type: 'success', message: 'Account activated! Welcome aboard!');

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.activate-account');
    }
}

