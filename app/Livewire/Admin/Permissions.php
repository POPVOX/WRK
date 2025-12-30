<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Permissions')]
class Permissions extends Component
{
    public array $rows = [];

    public function mount(): void
    {
        $this->rows = User::orderBy('name')
            ->get(['id', 'name', 'email', 'access_level', 'is_admin'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'access_level' => $u->access_level ?? 'team',
                'is_admin' => $u->is_admin ? '1' : '0',
            ])
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

        foreach ($this->rows as $row) {
            User::where('id', $row['id'])->update([
                'access_level' => $row['access_level'],
                'is_admin' => $row['is_admin'] === '1',
            ]);
        }

        session()->flash('status', 'Permissions updated.');
        $this->mount();
    }

    public function render()
    {
        return view('livewire.admin.permissions');
    }
}

