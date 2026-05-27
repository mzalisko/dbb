<?php

namespace App\Livewire\Groups;

use App\Models\Site;
use App\Models\SiteGroup;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Групи сайтів')]
class Index extends Component
{
    public string $createGroupName  = '';
    public string $createGroupColor = '#5a8a3c';

    public function createGroup(): void
    {
        $this->validate([
            'createGroupName'  => 'required|string|max:60|unique:site_groups,name',
            'createGroupColor' => 'required|string|max:10',
        ]);

        SiteGroup::create([
            'name'  => strtolower(trim($this->createGroupName)),
            'color' => $this->createGroupColor,
        ]);

        $this->reset('createGroupName', 'createGroupColor');
        $this->createGroupColor = '#5a8a3c';
        $this->dispatch('group-created');
    }

    public function render()
    {
        // Show groups from site_groups table; orphaned site groups still visible via union
        $defined = SiteGroup::orderBy('name')->get()->map(function ($g) {
            $allSites       = Site::where('group', $g->name)->orderBy('name')->get();
            $g->sites       = $allSites;
            $g->sites_count = $allSites->count();
            $g->active_count = $allSites->where('status', 'active')->count();
            $g->error_count  = $allSites->where('status', 'offline')->count();
            $g->group       = $g->name;
            $g->group_color = $g->color;
            return $g;
        });

        $definedNames = $defined->pluck('group');

        $orphaned = Site::whereNotNull('group')
            ->whereNotIn('group', $definedNames)
            ->selectRaw('`group`, group_color, count(*) as sites_count')
            ->groupBy('group', 'group_color')
            ->get()
            ->map(function ($g) {
                $allSites        = Site::where('group', $g->group)->orderBy('name')->get();
                $g->sites        = $allSites;
                $g->sites_count  = $allSites->count();
                $g->active_count = $allSites->where('status', 'active')->count();
                $g->error_count  = $allSites->where('status', 'offline')->count();
                return $g;
            });

        $groups = $defined->concat($orphaned)->sortBy('group')->values();

        return view('livewire.groups.index', compact('groups'));
    }
}
