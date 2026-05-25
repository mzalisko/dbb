<?php

namespace App\Livewire;

use App\Models\Client;
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
        // 2 queries instead of 6 separate count() -- performance optimization
        $clientStats = Client::selectRaw("
            count(*) as total,
            sum(case when status='active' then 1 else 0 end) as active
        ")->first();

        $siteStats = Site::selectRaw("
            count(*) as total,
            sum(case when status='active' then 1 else 0 end) as active,
            sum(case when status='maintenance' then 1 else 0 end) as maintenance,
            sum(case when status='offline' then 1 else 0 end) as offline
        ")->first();

        return view('livewire.dashboard', [
            'totalClients' => $clientStats->total,
            'activeClients' => $clientStats->active,
            'totalSites' => $siteStats->total,
            'activeSites' => $siteStats->active,
            'maintenanceSites' => $siteStats->maintenance,
            'offlineSites' => $siteStats->offline,
            'recentActivity' => ActivityLog::with('user')
                ->latest('created_at')
                ->take(10)
                ->get(),
            'recentClients' => Client::with('sites')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
