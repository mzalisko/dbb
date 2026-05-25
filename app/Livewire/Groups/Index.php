<?php

namespace App\Livewire\Groups;

use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Групи сайтів')]
class Index extends Component
{
    public function render()
    {
        $groups = Site::whereNotNull('group')
            ->selectRaw('`group`, group_color, count(*) as sites_count')
            ->groupBy('group', 'group_color')
            ->get()
            ->map(function ($g) {
                $g->sites = Site::where('group', $g->group)
                    ->take(3)
                    ->get();
                return $g;
            });

        return view('livewire.groups.index', compact('groups'));
    }
}
