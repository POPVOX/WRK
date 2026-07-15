<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalStaffChangeSignal;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Congressional Staff Changes')]
class ChangeSignalIndex extends Component
{
    use WithPagination;

    #[Url(except: 'pending')]
    public string $status = 'pending';

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function review(int $signalId, string $status): void
    {
        validator(['status' => $status], [
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected'])],
        ])->validate();

        $signal = CongressionalStaffChangeSignal::query()->findOrFail($signalId);
        $signal->update([
            'status' => $status,
            'reviewed_by' => $status === 'pending' ? null : auth()->id(),
            'reviewed_at' => $status === 'pending' ? null : now(),
        ]);

        $this->dispatch('notify', type: 'success', message: match ($status) {
            'accepted' => 'Staff-change evidence confirmed.',
            'rejected' => 'Signal dismissed.',
            default => 'Signal returned to review.',
        });
    }

    public function render()
    {
        return view('livewire.congressional-directory.change-signal-index', [
            'signals' => CongressionalStaffChangeSignal::query()
                ->with(['gmailMessage', 'reviewer'])
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->latest('detected_at')
                ->paginate(20),
            'counts' => CongressionalStaffChangeSignal::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }
}
