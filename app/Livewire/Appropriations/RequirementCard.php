<?php

namespace App\Livewire\Appropriations;

use App\Models\ReportingRequirement;
use App\Models\User;
use Livewire\Component;

class RequirementCard extends Component
{
    public ReportingRequirement $requirement;
    public bool $showDetails = false;
    public bool $showEditModal = false;

    // Edit fields
    public string $editStatus = '';
    public ?int $editAssignedTo = null;
    public string $editNotes = '';

    protected $listeners = ['requirement-updated' => '$refresh'];

    public function mount(ReportingRequirement $requirement): void
    {
        $this->requirement = $requirement;
        $this->editStatus = $requirement->status;
        $this->editAssignedTo = $requirement->assigned_to;
        $this->editNotes = $requirement->notes ?? '';
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function updateStatus(string $newStatus): void
    {
        $updateData = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'submitted') {
            $updateData['completed_at'] = now();
            $updateData['completed_by'] = auth()->id();
        } else {
            $updateData['completed_at'] = null;
            $updateData['completed_by'] = null;
        }

        $this->requirement->update($updateData);
        $this->requirement->refresh();

        $this->dispatch('requirement-updated');
        $this->dispatch('notify', type: 'success', message: 'Status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)));
    }

    public function openEditModal(): void
    {
        $this->editStatus = $this->requirement->status;
        $this->editAssignedTo = $this->requirement->assigned_to;
        $this->editNotes = $this->requirement->notes ?? '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
    }

    public function saveEdit(): void
    {
        $updateData = [
            'status' => $this->editStatus,
            'assigned_to' => $this->editAssignedTo,
            'notes' => $this->editNotes,
        ];

        if ($this->editStatus === 'submitted' && $this->requirement->status !== 'submitted') {
            $updateData['completed_at'] = now();
            $updateData['completed_by'] = auth()->id();
        } elseif ($this->editStatus !== 'submitted') {
            $updateData['completed_at'] = null;
            $updateData['completed_by'] = null;
        }

        $this->requirement->update($updateData);
        $this->requirement->refresh();

        $this->closeEditModal();
        $this->dispatch('requirement-updated');
        $this->dispatch('notify', type: 'success', message: 'Requirement updated');
    }

    public function getTeamMembersProperty()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.appropriations.requirement-card', [
            'teamMembers' => $this->teamMembers,
        ]);
    }
}

