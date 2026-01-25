<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuickAddTask extends Component
{
    public bool $isOpen = false;

    public string $title = '';

    public ?int $projectId = null;

    public ?int $assignedTo = null;

    public ?string $dueDate = null;

    public string $priority = 'medium';

    public string $description = '';

    // Success state
    public bool $submitted = false;

    public function open(): void
    {
        $this->isOpen = true;
        $this->submitted = false;
        $this->reset(['title', 'description', 'projectId', 'assignedTo', 'dueDate']);
        $this->priority = 'medium';
        $this->assignedTo = Auth::id();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['title', 'description', 'projectId', 'assignedTo', 'dueDate', 'submitted']);
        $this->priority = 'medium';
    }

    public function submit(): void
    {
        $this->validate([
            'title' => 'required|min:3|max:255',
            'projectId' => 'nullable|exists:projects,id',
            'assignedTo' => 'nullable|exists:users,id',
            'dueDate' => 'nullable|date|after_or_equal:today',
            'priority' => 'required|in:low,medium,high',
            'description' => 'nullable|max:2000',
        ]);

        ProjectTask::create([
            'project_id' => $this->projectId,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'assigned_to' => $this->assignedTo,
            'due_date' => $this->dueDate,
            'priority' => $this->priority,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        $this->submitted = true;
        $this->dispatch('notify', type: 'success', message: 'Task created successfully!');
    }

    public function addAnother(): void
    {
        $this->submitted = false;
        $this->reset(['title', 'description', 'dueDate']);
    }

    public function getProjectsProperty()
    {
        return Project::orderBy('name')->get();
    }

    public function getTeamMembersProperty()
    {
        return User::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.quick-add-task');
    }
}
