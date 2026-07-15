<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalStaffProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Congressional Staff Profile')]
class StaffShow extends Component
{
    public CongressionalStaffProfile $profile;

    public function mount(CongressionalStaffProfile $profile): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);

        $this->profile = $profile;
    }

    public function render()
    {
        $this->profile->load([
            'person',
            'positions' => fn ($query) => $query
                ->with('office')
                ->orderByDesc('is_current')
                ->orderByDesc('last_reported_end'),
            'observations' => fn ($query) => $query
                ->with('office')
                ->orderByDesc('period_end')
                ->limit(50),
        ]);

        return view('livewire.congressional-directory.staff-show');
    }
}
