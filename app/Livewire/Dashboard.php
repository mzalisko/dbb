<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $sites = Site::with('client')
            ->latest('last_checked_at')
            ->take(6)
            ->get();

        $recentLogs = ActivityLog::with(['user', 'subject'])
            ->latest('created_at')
            ->take(7)
            ->get();

        $totalSites = Site::count();

        return view('livewire.dashboard', compact('sites', 'recentLogs', 'totalSites'));
    }
}
