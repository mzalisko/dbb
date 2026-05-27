<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Renderless;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Renderless]
    public function toggleFavourite(int $id): void
    {
        $site = Site::findOrFail($id);
        $site->update(['is_favourite' => !$site->is_favourite]);
    }

    public function render()
    {
        $totalSites = Site::count();

        $favourites = Site::with(['contactEntries'])
            ->where('is_favourite', true)
            ->latest('last_checked_at')
            ->get();

        $groups = Site::whereNotNull('group')
            ->selectRaw('`group`, group_color')
            ->groupBy('group', 'group_color')
            ->orderBy('group')
            ->get();

        $sites = Site::with('client')->latest('last_checked_at')->get();

        $recentLogs = ActivityLog::with(['user', 'subject'])
            ->latest('created_at')
            ->take(7)
            ->get();

        return view('livewire.dashboard', compact('sites', 'recentLogs', 'totalSites', 'favourites', 'groups'));
    }
}
