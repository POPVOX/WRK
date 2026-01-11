<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TimezoneLocation extends Component
{
    public string $timezone = '';

    public string $location = '';

    public bool $showModal = false;

    public bool $isPrompt = false;

    public ?string $detectedTimezone = null;

    public function mount(bool $isPrompt = false): void
    {
        $user = Auth::user();
        $this->timezone = $user->timezone ?? '';
        $this->location = $user->location ?? '';
        $this->isPrompt = $isPrompt;

        if ($isPrompt) {
            $this->showModal = true;
        }
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function setDetectedTimezone(string $timezone): void
    {
        $this->detectedTimezone = $timezone;

        // If user has no timezone set, pre-fill with detected
        if (empty($this->timezone)) {
            $this->timezone = $timezone;
        }
    }

    public function save(): void
    {
        $this->validate([
            'timezone' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
        ]);

        Auth::user()->update([
            'timezone' => $this->timezone,
            'location' => $this->location ?: null,
            'timezone_confirmed_at' => now(),
        ]);

        $this->showModal = false;
        $this->dispatch('notify', type: 'success', message: 'Timezone and location updated!');
        $this->dispatch('timezone-updated');
    }

    public function skipPrompt(): void
    {
        // Just close without saving - they can update later
        $this->showModal = false;
    }

    public function useDetected(): void
    {
        if ($this->detectedTimezone) {
            $this->timezone = $this->detectedTimezone;
        }
    }

    public static function getTimezones(): array
    {
        return [
            'America/New_York' => 'Eastern Time (ET)',
            'America/Chicago' => 'Central Time (CT)',
            'America/Denver' => 'Mountain Time (MT)',
            'America/Los_Angeles' => 'Pacific Time (PT)',
            'America/Anchorage' => 'Alaska Time (AKT)',
            'Pacific/Honolulu' => 'Hawaii Time (HT)',
            'America/Phoenix' => 'Arizona (no DST)',
            'America/Puerto_Rico' => 'Puerto Rico (AST)',
            'Pacific/Guam' => 'Guam (ChST)',
            'Europe/London' => 'London (GMT/BST)',
            'Europe/Paris' => 'Paris (CET)',
            'Europe/Berlin' => 'Berlin (CET)',
            'Europe/Rome' => 'Rome (CET)',
            'Europe/Madrid' => 'Madrid (CET)',
            'Europe/Amsterdam' => 'Amsterdam (CET)',
            'Europe/Brussels' => 'Brussels (CET)',
            'Europe/Zurich' => 'Zurich (CET)',
            'Europe/Vienna' => 'Vienna (CET)',
            'Europe/Stockholm' => 'Stockholm (CET)',
            'Europe/Oslo' => 'Oslo (CET)',
            'Europe/Copenhagen' => 'Copenhagen (CET)',
            'Europe/Helsinki' => 'Helsinki (EET)',
            'Europe/Athens' => 'Athens (EET)',
            'Europe/Moscow' => 'Moscow (MSK)',
            'Asia/Dubai' => 'Dubai (GST)',
            'Asia/Kolkata' => 'India (IST)',
            'Asia/Singapore' => 'Singapore (SGT)',
            'Asia/Hong_Kong' => 'Hong Kong (HKT)',
            'Asia/Tokyo' => 'Tokyo (JST)',
            'Asia/Seoul' => 'Seoul (KST)',
            'Asia/Shanghai' => 'Beijing/Shanghai (CST)',
            'Australia/Sydney' => 'Sydney (AEST)',
            'Australia/Melbourne' => 'Melbourne (AEST)',
            'Australia/Brisbane' => 'Brisbane (AEST)',
            'Australia/Perth' => 'Perth (AWST)',
            'Pacific/Auckland' => 'Auckland (NZST)',
            'Africa/Johannesburg' => 'Johannesburg (SAST)',
            'Africa/Cairo' => 'Cairo (EET)',
            'Africa/Lagos' => 'Lagos (WAT)',
            'Africa/Nairobi' => 'Nairobi (EAT)',
            'America/Sao_Paulo' => 'São Paulo (BRT)',
            'America/Mexico_City' => 'Mexico City (CST)',
            'America/Toronto' => 'Toronto (ET)',
            'America/Vancouver' => 'Vancouver (PT)',
            'UTC' => 'UTC',
        ];
    }

    public function render()
    {
        return view('livewire.components.timezone-location', [
            'timezones' => self::getTimezones(),
        ]);
    }
}
