<?php

namespace App\Livewire\Team;

use App\Models\User;
use Livewire\Component;

class TeamMemberProfile extends Component
{
    public User $member;

    public bool $editing = false;

    // Edit form fields
    public string $name = '';

    public string $title = '';

    public string $location = '';

    public string $timezone = '';

    public string $bio = '';

    public string $bio_short = '';

    public string $bio_medium = '';

    public string $phone = '';

    public string $linkedin = '';

    public string $photo_url = '';

    public array $publications = [];

    public string $newPublication = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'title' => 'nullable|string|max:255',
        'location' => 'nullable|string|max:255',
        'timezone' => 'required|string|max:100',
        'bio' => 'nullable|string',
        'bio_short' => 'nullable|string|max:255',
        'bio_medium' => 'nullable|string',
        'phone' => 'nullable|string|max:50',
        'linkedin' => 'nullable|string|max:255',
        'photo_url' => 'nullable|url|max:500',
    ];

    public function mount(User $member)
    {
        $this->member = $member;
        $this->loadFormFields();
    }

    public function loadFormFields()
    {
        $this->name = $this->member->name ?? '';
        $this->title = $this->member->title ?? '';
        $this->location = $this->member->location ?? '';
        $this->timezone = $this->member->timezone ?? 'America/New_York';
        $this->bio = $this->member->bio ?? '';
        $this->bio_short = $this->member->bio_short ?? '';
        $this->bio_medium = $this->member->bio_medium ?? '';
        $this->phone = $this->member->phone ?? '';
        $this->linkedin = $this->member->linkedin ?? '';
        $this->photo_url = $this->member->photo_url ?? '';
        $this->publications = $this->member->publications ?? [];
    }

    public function startEditing()
    {
        $this->loadFormFields();
        $this->editing = true;
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->loadFormFields();
    }

    public function addPublication()
    {
        if (trim($this->newPublication)) {
            $this->publications[] = trim($this->newPublication);
            $this->newPublication = '';
        }
    }

    public function removePublication($index)
    {
        unset($this->publications[$index]);
        $this->publications = array_values($this->publications);
    }

    public function save()
    {
        $this->validate();

        $this->member->update([
            'name' => $this->name,
            'title' => $this->title,
            'location' => $this->location,
            'timezone' => $this->timezone,
            'bio' => $this->bio,
            'bio_short' => $this->bio_short,
            'bio_medium' => $this->bio_medium,
            'phone' => $this->phone,
            'linkedin' => $this->linkedin,
            'photo_url' => $this->photo_url,
            'publications' => $this->publications,
        ]);

        session()->flash('message', 'Profile updated successfully.');

        return $this->redirect(route('team.hub').'?tab=team', navigate: true);
    }

    public function getLocalTimeProperty()
    {
        try {
            $tz = new \DateTimeZone($this->member->timezone ?? 'America/New_York');
            $now = new \DateTime('now', $tz);

            return $now->format('g:i A');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getProjectsProperty()
    {
        return $this->member->projects()->get();
    }

    public function getRecentMeetingsProperty()
    {
        // Get meetings where this user is the creator or logged the meeting
        return \App\Models\Meeting::where('user_id', $this->member->id)
            ->orderBy('meeting_date', 'desc')
            ->take(10)
            ->get();
    }

    public function getTravelProfileProperty()
    {
        return $this->member->travelProfile;
    }

    public function render()
    {
        return view('livewire.team.team-member-profile')->layout('layouts.app');
    }
}
