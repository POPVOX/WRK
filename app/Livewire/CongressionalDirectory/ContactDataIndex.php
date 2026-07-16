<?php

namespace App\Livewire\CongressionalDirectory;

use App\Jobs\EnrichCongressionalContactData;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Congressional Contact Data')]
class ContactDataIndex extends Component
{
    public string $instructions = 'Database-wide congressional email formula enrichment.';

    public string $housePattern = CongressionalEmailGuessService::HOUSE_PATTERN;

    public string $senatePattern = CongressionalEmailGuessService::SENATE_PATTERN;

    /** @var array<string,mixed> */
    public array $operation = [];

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);

        $this->refreshOperation();
        $this->instructions = (string) ($this->operation['instructions'] ?? $this->instructions);
        $this->housePattern = (string) ($this->operation['house_pattern'] ?? $this->housePattern);
        $this->senatePattern = (string) ($this->operation['senate_pattern'] ?? $this->senatePattern);
    }

    public function refreshOperation(): void
    {
        $this->operation = Cache::get(EnrichCongressionalContactData::CACHE_KEY, []);
    }

    public function generateEmailGuesses(CongressionalEmailGuessService $guesses): void
    {
        $this->refreshOperation();
        if (in_array($this->operation['status'] ?? null, ['queued', 'running'], true)) {
            $this->dispatch('notify', type: 'info', message: 'Congressional contact-data enrichment is already running.');

            return;
        }

        $this->validate([
            'instructions' => ['required', 'string', 'max:2000'],
            'housePattern' => ['required', 'string', 'max:160'],
            'senatePattern' => ['required', 'string', 'max:160'],
        ]);

        try {
            $guesses->renderPattern($this->housePattern, ['first' => 'jane', 'last' => 'doe']);
        } catch (\InvalidArgumentException $exception) {
            $this->addError('housePattern', $exception->getMessage());

            return;
        }

        try {
            $guesses->renderPattern($this->senatePattern, [
                'first' => 'jane',
                'last' => 'doe',
                'senator_last' => 'example',
            ]);
        } catch (\InvalidArgumentException $exception) {
            $this->addError('senatePattern', $exception->getMessage());

            return;
        }

        $estimate = $guesses->estimateAllProfiles($this->housePattern, $this->senatePattern);
        $correctable = $guesses->estimateFormulaRepairs();
        if ($estimate['guessable'] === 0 && $correctable === 0) {
            $this->dispatch('notify', type: 'info', message: 'The directory has no new or correctable provisional addresses.');

            return;
        }

        $this->operation = [
            'status' => 'queued',
            'requested_by' => Auth::id(),
            'queued_at' => now()->toIso8601String(),
            'instructions' => trim($this->instructions),
            'house_pattern' => trim($this->housePattern),
            'senate_pattern' => trim($this->senatePattern),
            'estimate' => $estimate,
            'correctable' => $correctable,
        ];
        Cache::forever(EnrichCongressionalContactData::CACHE_KEY, $this->operation);

        EnrichCongressionalContactData::dispatch(
            Auth::id(),
            trim($this->instructions),
            trim($this->housePattern),
            trim($this->senatePattern)
        )->afterCommit();

        $this->dispatch('notify', type: 'success', message: 'Congressional contact-data enrichment was queued. No email will be sent.');
    }

    public function render(CongressionalEmailGuessService $guesses)
    {
        $this->refreshOperation();

        return view('livewire.congressional-directory.contact-data-index', [
            'estimate' => $guesses->estimateAllProfiles($this->housePattern, $this->senatePattern),
            'correctable' => $guesses->estimateFormulaRepairs(),
        ]);
    }
}
