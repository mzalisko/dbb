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
        $user = auth()->user();
        $allowedEntryTypes = $user?->readableEntryTypes() ?? [];
        $canPhones = $user?->canEntryType('phone') ?? false;
        $canMessengers = $user?->canEntryType('messenger') ?? false;

        $totalSites = Site::accessibleTo($user)->count();

        $favourites = Site::accessibleTo($user)
            ->with(['contactEntries' => fn ($q) => $q->whereIn('type', $allowedEntryTypes)])
            ->where('is_favourite', true)
            ->latest('last_checked_at')
            ->get();

        $groups = Site::accessibleTo($user)->whereNotNull('group')
            ->selectRaw('`group`, group_color')
            ->groupBy('group', 'group_color')
            ->orderBy('group')
            ->get();

        $sites = Site::accessibleTo($user)->with('client')->latest('last_checked_at')->get();

        // Recent site activity from the unified feed (semantic labels + severity).
        // Auth noise is excluded — this widget is about what changed on sites; click
        // a row to dive into that site's logs.
        $recentLogs = \App\Services\AuditFeed::collect(['allowed_entry_types' => $allowedEntryTypes])
            ->filter(fn ($e) => $e->siteId !== null)
            ->take(7)
            ->values();
        $logSiteNames = Site::accessibleTo($user)->pluck('name', 'id');

        return view('livewire.dashboard', compact('sites', 'recentLogs', 'logSiteNames', 'totalSites', 'favourites', 'groups', 'canPhones', 'canMessengers'));
    }
}
