<?php

namespace App\Livewire\Components;

use App\Models\Country;
use App\Models\Region;
use App\Models\UsState;
use Livewire\Component;

class GeographicTagSelector extends Component
{
    public array $selectedRegions = [];

    public array $selectedCountries = [];

    public array $selectedUsStates = [];

    public string $activeTab = 'regions';

    public string $countrySearch = '';

    public string $stateSearch = '';

    public ?int $filterByRegion = null;

    public bool $showUsStates = true;

    public function mount(
        array $selectedRegions = [],
        array $selectedCountries = [],
        array $selectedUsStates = [],
        bool $showUsStates = true
    ): void {
        $this->selectedRegions = $selectedRegions;
        $this->selectedCountries = $selectedCountries;
        $this->selectedUsStates = $selectedUsStates;
        $this->showUsStates = $showUsStates;
    }

    protected function emitUpdate(): void
    {
        $this->dispatch('geographic-tags-updated', [
            'regions' => $this->selectedRegions,
            'countries' => $this->selectedCountries,
            'usStates' => $this->selectedUsStates,
        ]);
    }

    public function toggleRegion(int $id): void
    {
        if (in_array($id, $this->selectedRegions)) {
            $this->selectedRegions = array_values(array_diff($this->selectedRegions, [$id]));
        } else {
            $this->selectedRegions[] = $id;
        }
        $this->emitUpdate();
    }

    public function toggleCountry(int $id): void
    {
        if (in_array($id, $this->selectedCountries)) {
            $this->selectedCountries = array_values(array_diff($this->selectedCountries, [$id]));
        } else {
            $this->selectedCountries[] = $id;
        }
        $this->emitUpdate();
    }

    public function toggleUsState(int $id): void
    {
        if (in_array($id, $this->selectedUsStates)) {
            $this->selectedUsStates = array_values(array_diff($this->selectedUsStates, [$id]));
        } else {
            $this->selectedUsStates[] = $id;
        }
        $this->emitUpdate();
    }

    public function removeTag(string $type, int $id): void
    {
        match ($type) {
            'region' => $this->selectedRegions = array_values(array_diff($this->selectedRegions, [$id])),
            'country' => $this->selectedCountries = array_values(array_diff($this->selectedCountries, [$id])),
            'us_state' => $this->selectedUsStates = array_values(array_diff($this->selectedUsStates, [$id])),
        };
        $this->emitUpdate();
    }

    public function clearAll(): void
    {
        $this->selectedRegions = [];
        $this->selectedCountries = [];
        $this->selectedUsStates = [];
        $this->emitUpdate();
    }

    public function getRegionsProperty()
    {
        return Region::orderBy('sort_order')->get();
    }

    public function getCountriesProperty()
    {
        $query = Country::with('region')->orderBy('name');

        if ($this->filterByRegion) {
            $query->where('region_id', $this->filterByRegion);
        }

        if ($this->countrySearch) {
            $query->where('name', 'like', "%{$this->countrySearch}%");
        }

        return $query->get();
    }

    public function getUsStatesProperty()
    {
        $query = UsState::orderBy('type')->orderBy('name');

        if ($this->stateSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->stateSearch}%")
                    ->orWhere('abbreviation', 'like', "%{$this->stateSearch}%");
            });
        }

        return $query->get();
    }

    public function getSelectedTagsProperty(): array
    {
        $tags = [];

        foreach ($this->selectedRegions as $id) {
            $region = Region::find($id);
            if ($region) {
                $tags[] = ['type' => 'region', 'id' => $id, 'name' => $region->name, 'emoji' => '🌍'];
            }
        }

        foreach ($this->selectedCountries as $id) {
            $country = Country::find($id);
            if ($country) {
                $tags[] = ['type' => 'country', 'id' => $id, 'name' => $country->name, 'emoji' => '🏳️'];
            }
        }

        foreach ($this->selectedUsStates as $id) {
            $state = UsState::find($id);
            if ($state) {
                $tags[] = ['type' => 'us_state', 'id' => $id, 'name' => $state->name, 'emoji' => '🇺🇸'];
            }
        }

        return $tags;
    }

    public function getTotalSelectedProperty(): int
    {
        return count($this->selectedRegions) + count($this->selectedCountries) + count($this->selectedUsStates);
    }

    public function render()
    {
        return view('livewire.components.geographic-tag-selector', [
            'regions' => $this->regions,
            'countries' => $this->countries,
            'usStates' => $this->usStates,
            'selectedTags' => $this->selectedTags,
            'totalSelected' => $this->totalSelected,
        ]);
    }
}
