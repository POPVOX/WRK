<?php

namespace App\Livewire\Admin;

use App\Models\AgentPermission;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Permissions')]
class Permissions extends Component
{
    public array $rows = [];

    public array $projectOptions = [];

    public bool $agentPermissionsEnabled = false;

    public function mount(): void
    {
        $this->agentPermissionsEnabled = Schema::hasTable('agent_permissions');

        $this->projectOptions = Project::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])->values()->all();

        $users = User::orderBy('name')
            ->get(['id', 'name', 'email', 'access_level', 'is_admin']);

        $permissionMap = $this->agentPermissionsEnabled
            ? AgentPermission::query()
                ->whereIn('user_id', $users->pluck('id'))
                ->get()
                ->keyBy('user_id')
            : collect();

        $this->rows = $users
            ->map(function (User $user) use ($permissionMap): array {
                /** @var AgentPermission|null $permission */
                $permission = $permissionMap->get($user->id);
                $defaultSpecialist = $user->isManagement();
                $defaultApprove = $user->isManagement();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_level' => $user->access_level ?? 'team',
                    'is_admin' => $user->is_admin ? '1' : '0',
                    'can_create_specialist' => (($permission?->can_create_specialist ?? $defaultSpecialist) ? '1' : '0'),
                    'can_create_project' => (($permission?->can_create_project ?? true) ? '1' : '0'),
                    'project_scope' => (string) ($permission?->project_scope ?? 'all'),
                    'allowed_project_ids_text' => collect($permission?->allowed_project_ids ?? [])->implode(', '),
                    'can_approve_medium_risk' => (($permission?->can_approve_medium_risk ?? $defaultApprove) ? '1' : '0'),
                    'can_approve_high_risk' => (($permission?->can_approve_high_risk ?? $defaultApprove) ? '1' : '0'),
                ];
            })
            ->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'rows' => 'required|array',
            'rows.*.id' => ['required', 'integer', Rule::exists('users', 'id')],
            'rows.*.access_level' => ['required', Rule::in(['team', 'management', 'admin'])],
            'rows.*.is_admin' => ['required', Rule::in(['0', '1'])],
        ]);

        if ($this->agentPermissionsEnabled) {
            $this->validate([
                'rows.*.can_create_specialist' => ['required', Rule::in(['0', '1'])],
                'rows.*.can_create_project' => ['required', Rule::in(['0', '1'])],
                'rows.*.project_scope' => ['required', Rule::in(['none', 'assigned', 'all', 'custom'])],
                'rows.*.allowed_project_ids_text' => ['nullable', 'string', 'max:2000'],
                'rows.*.can_approve_medium_risk' => ['required', Rule::in(['0', '1'])],
                'rows.*.can_approve_high_risk' => ['required', Rule::in(['0', '1'])],
            ]);
        }

        $knownProjectIds = collect($this->projectOptions)->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($this->rows as $index => $row) {
            if (! $this->agentPermissionsEnabled) {
                break;
            }

            $allowedProjectIds = $this->parseProjectIds((string) Arr::get($row, 'allowed_project_ids_text'));

            $invalid = collect($allowedProjectIds)
                ->filter(fn ($id) => ! in_array((int) $id, $knownProjectIds, true))
                ->values()
                ->all();

            if (! empty($invalid)) {
                $this->addError('rows.'.$index.'.allowed_project_ids_text', 'Unknown project ID(s): '.implode(', ', $invalid));

                return;
            }

            if (($row['project_scope'] ?? 'all') === 'custom' && empty($allowedProjectIds)) {
                $this->addError('rows.'.$index.'.allowed_project_ids_text', 'Custom scope requires at least one project ID.');

                return;
            }
        }

        foreach ($this->rows as $row) {
            $allowedProjectIds = $this->parseProjectIds((string) Arr::get($row, 'allowed_project_ids_text'));

            User::where('id', $row['id'])->update([
                'access_level' => $row['access_level'],
                'is_admin' => $row['is_admin'] === '1',
            ]);

            if ($this->agentPermissionsEnabled) {
                AgentPermission::query()->updateOrCreate(
                    ['user_id' => $row['id']],
                    [
                        'can_create_specialist' => $row['can_create_specialist'] === '1',
                        'can_create_project' => $row['can_create_project'] === '1',
                        'project_scope' => $row['project_scope'],
                        'allowed_project_ids' => $row['project_scope'] === 'custom' ? $allowedProjectIds : null,
                        'can_approve_medium_risk' => $row['can_approve_medium_risk'] === '1',
                        'can_approve_high_risk' => $row['can_approve_high_risk'] === '1',
                    ]
                );
            }
        }

        session()->flash('status', 'Permissions updated.');
        $this->mount();
    }

    protected function parseProjectIds(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => $item !== '' && ctype_digit($item))
            ->map(fn ($item) => (int) $item)
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.permissions', [
            'projectCount' => count($this->projectOptions),
        ]);
    }
}
