<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Renderless;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use WithPagination;

    private const LOGS_PER_PAGE = 7;

    public ?int $restoreSiteId = null;
    public string $restoreSiteName = '';

    #[Renderless]
    public function toggleFavourite(int $id): void
    {
        $site = Site::findOrFail($id);
        $site->update(['is_favourite' => !$site->is_favourite]);
    }

    public function requestRestoreSite(int $id): void
    {
        $site = Site::withTrashed()->accessibleTo(auth()->user())->findOrFail($id);

        if (! $site->trashed()) {
            $this->redirectRoute('sites.show', $site);
            return;
        }

        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $this->restoreSiteId = $site->id;
        $this->restoreSiteName = $site->name;
    }

    public function cancelRestoreSite(): void
    {
        $this->restoreSiteId = null;
        $this->restoreSiteName = '';
    }

    public function confirmRestoreSite()
    {
        if (! auth()->user()?->isAdmin() || ! $this->restoreSiteId) {
            abort(403);
        }

        $site = Site::withTrashed()->accessibleTo(auth()->user())->findOrFail($this->restoreSiteId);
        $site->restore();
        $this->cancelRestoreSite();
        $this->dispatch('toast', type: 'success', message: "Сайт «{$site->name}» відновлено");

        return $this->redirectRoute('sites.show', $site);
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

        // Site names for the log rows — accessible sites incl. trashed (so a deleted
        // site can still be restored from here). Its keys also SCOPE the feed below:
        // a limited user must never see out-of-scope site activity, nor its name
        // (targetName can resolve a name from the audit row itself, so filtering by
        // name presence is not enough — we filter by accessible site id).
        $logSiteNames = Site::withTrashed()->accessibleTo($user)->pluck('name', 'id');
        $accessibleSiteIds = $logSiteNames->keys()->map(fn ($id) => (int) $id)->all();
        $deletedSiteIds = Site::onlyTrashed()->accessibleTo($user)->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Recent site activity from the unified feed (semantic labels + severity).
        // Auth noise is excluded — this widget is about what changed on sites; click
        // a row to dive into that site's logs.
        $allRecentLogs = \App\Services\AuditFeed::collect(['allowed_entry_types' => $allowedEntryTypes])
            ->filter(fn ($e) => $e->siteId !== null && in_array($e->siteId, $accessibleSiteIds, true))
            ->values();
        $logsPage = $this->getPage('dashboardLogsPage');
        $recentLogs = new LengthAwarePaginator(
            $allRecentLogs->forPage($logsPage, self::LOGS_PER_PAGE)->values(),
            $allRecentLogs->count(),
            self::LOGS_PER_PAGE,
            $logsPage,
            [
                'path' => request()->url(),
                'pageName' => 'dashboardLogsPage',
            ],
        );

        return view('livewire.dashboard', compact('sites', 'recentLogs', 'logSiteNames', 'deletedSiteIds', 'totalSites', 'favourites', 'groups', 'canPhones', 'canMessengers'));
    }
}
