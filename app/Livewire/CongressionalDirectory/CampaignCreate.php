<?php

namespace App\Livewire\CongressionalDirectory;

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Models\CongressionalStaffList;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create Congressional Campaign')]
class CampaignCreate extends Component
{
    public ?int $staffListId = null;

    public string $name = '';

    public string $subject = '';

    public string $bodyText = '';

    public int $batchSize = 10;

    public string $deliveryMode = 'manual';

    public int $cadenceValue = 1;

    public string $cadenceUnit = 'hour';

    public string $timezone = 'America/New_York';

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
        $this->timezone = Auth::user()->timezone ?: 'America/New_York';

        $requestedList = (int) request()->query('list', 0);
        $listId = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->when($requestedList > 0, fn ($query) => $query->whereKey($requestedList))
            ->value('id');
        $this->staffListId = $listId ? (int) $listId : null;
    }

    public function createCampaign(CongressionalOutreachWorkbenchService $workbench): mixed
    {
        $validated = $this->validate([
            'staffListId' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'subject' => ['nullable', 'string', 'max:255'],
            'bodyText' => ['nullable', 'string', 'max:50000'],
            'batchSize' => ['required', 'integer', 'min:1', 'max:5000'],
            'deliveryMode' => ['required', 'in:manual,scheduled,recurring'],
            'cadenceValue' => ['required', 'integer', 'min:1', 'max:1000'],
            'cadenceUnit' => ['required', 'in:minute,hour,day,week'],
            'timezone' => ['required', 'timezone'],
        ]);

        $list = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->findOrFail($validated['staffListId']);

        try {
            $draft = $workbench->createDraft($list, Auth::id(), $validated['name']);
            $draft->update([
                'subject' => trim($this->subject) ?: null,
                'body_text' => trim($this->bodyText) ?: null,
                'batch_size' => $validated['batchSize'],
                'delivery_mode' => $validated['deliveryMode'],
                'cadence_value' => $validated['cadenceValue'],
                'cadence_unit' => $validated['cadenceUnit'],
                'timezone' => $validated['timezone'],
            ]);
            BuildCongressionalOutreachDraftSnapshot::dispatch($draft->id)->afterCommit();
        } catch (DomainException $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return null;
        }

        return $this->redirectRoute('congress.outreach.show', ['draft' => $draft], navigate: true);
    }

    public function render()
    {
        $lists = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->withCount('profiles')
            ->orderBy('name')
            ->get();

        return view('livewire.congressional-directory.campaign-create', compact('lists'));
    }
}
