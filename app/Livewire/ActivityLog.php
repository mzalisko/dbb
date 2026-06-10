<?php

namespace App\Livewire;

use App\Models\Site;
use App\Services\AuditFeed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Логи')]
class ActivityLog extends Component
{
    use WithPagination;

    /** sites | auth | bulk | perms */
    #[Url]
    public string $tab = 'sites';

    #[Url]
    public string $search = '';

    /** Selected event, serialized for the detail drawer (null = closed). */
    public ?array $detail = null;
    public ?int $restoreSiteId = null;
    public string $restoreSiteName = '';

    public function updating($name): void
    {
        if ($name === 'tab' || $name === 'search') {
            $this->resetPage();
            $this->detail = null;
        }
    }

    private function allowedEntryTypes(): array
    {
        return auth()->user()?->readableEntryTypes() ?? [];
    }

    /** Filters for the active tab's event stream. */
    private function tabFilters(): array
    {
        $f = ['allowed_entry_types' => $this->allowedEntryTypes()];
        if ($this->search !== '') {
            $f['search'] = $this->search;
        }

        return match ($this->tab) {
            'auth'  => $f + ['domain' => 'auth'],
            'bulk'  => $f + ['bulk' => true],
            'perms' => $f + ['domain' => 'user'],
            default => $f + ['exclude_bulk' => true], // sites
        };
    }

    public function openDetail(string $source, int $id): void
    {
        $event = AuditFeed::collect($this->tabFilters())
            ->first(fn ($e) => $e->source === $source && $e->id === $id);

        if (! $event) {
            return;
        }

        $siteNames = Site::withTrashed()->accessibleTo(auth()->user())->pluck('name', 'id');

        $this->detail = [
            'label'         => $event->label(),
            'icon'          => $event->icon(),
            'severity'      => $event->severity,
            'severityLabel' => $event->severityLabel(),
            'actionCode'    => $event->actionCode,
            'occurredAt'    => $event->occurredAt->format('d.m.Y · H:i:s'),
            'userName'      => $event->userName ?? 'Система',
            'ip'            => $event->ip,
            'context'       => $event->context,
            'source'        => $event->source,
            'id'            => $event->id,
            'batchId'       => $event->batchId,
            'siteId'        => $event->siteId,
            'targetName'    => $event->targetName($siteNames),
            'isBulk'        => str_contains($event->actionCode, '.bulk.'),
            'changes'       => $event->humanChanges(),
            'summary'       => $event->new,
        ];
    }

    public function closeDetail(): void
    {
        $this->detail = null;
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

    /** Stream the active tab's feed as CSV in plain language. */
    public function export()
    {
        $events = AuditFeed::collect($this->tabFilters());
        // Scope like render() — a limited user must not learn out-of-scope site names.
        $siteNames = Site::accessibleTo(auth()->user())->pluck('name', 'id');
        $filename = 'audit-'.$this->tab.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($events, $siteNames) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($out, ['Час', 'Подія', 'Сайт', 'Користувач', 'Важливість', 'IP', 'Зміни']);

            foreach ($events as $e) {
                $changes = collect($e->humanChanges())->map(function ($row) {
                    return match ($row['kind']) {
                        'group'  => $row['field'].': '.collect($row['lines'])
                            ->map(fn ($l) => $l['label'].' '.$l['old'].' → '.$l['new'])->implode(', '),
                        'delta'  => $row['field'].': '.\App\Support\AuditEntry::deltaText($row['added'], $row['removed']),
                        default  => $row['field'].': '.$row['old'].' → '.$row['new'],
                    };
                })->implode('; ');

                fputcsv($out, [
                    $e->occurredAt->format('Y-m-d H:i:s'),
                    $e->label(),
                    $e->siteId ? ($siteNames[$e->siteId] ?? $e->siteId) : '—',
                    $e->userName ?? 'Система',
                    $e->severityLabel(),
                    $e->ip,
                    $changes,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        // Site names + dive are limited to sites the user may access.
        $siteNames = Site::withTrashed()->accessibleTo(auth()->user())->pluck('name', 'id');
        $deletedSiteIds = Site::onlyTrashed()->accessibleTo(auth()->user())->pluck('id')->map(fn ($id) => (int) $id)->all();

        // One pass over the authorised feed for the tab counts — the Sites count
        // only includes events on sites the user can actually access.
        $all = AuditFeed::collect(['allowed_entry_types' => $this->allowedEntryTypes()]);
        $counts = [
            'sites' => $all->filter(fn ($e) => $e->siteId !== null && $siteNames->has($e->siteId) && ! str_contains($e->actionCode, '.bulk.'))->count(),
            'auth'  => $all->filter(fn ($e) => $e->domain() === 'auth')->count(),
            'bulk'  => $all->filter(fn ($e) => str_contains($e->actionCode, '.bulk.'))->count(),
            'perms' => $all->filter(fn ($e) => $e->domain() === 'user')->count(),
        ];

        $events = null;
        $sitesSummary = null;

        if ($this->tab === 'sites') {
            // Group site activity by site → one card per (accessible) site.
            $sitesSummary = AuditFeed::collect($this->tabFilters())
                ->filter(fn ($e) => $e->siteId !== null && $siteNames->has($e->siteId))
                ->groupBy('siteId')
                ->map(fn ($evs, $sid) => (object) [
                    'siteId' => (int) $sid,
                    'last'   => $evs->first(),
                    'count'  => $evs->count(),
                ])
                ->values();
        } else {
            $events = AuditFeed::paginate($this->tabFilters(), 30);
        }

        return view('livewire.activity-log', [
            'tabs' => [
                'sites' => 'Сайти',
                'auth'  => 'Авторизація',
                'bulk'  => 'Масові зміни',
                'perms' => 'Дозволи',
            ],
            'counts'       => $counts,
            'events'       => $events,
            'sitesSummary' => $sitesSummary,
            'siteNames'    => $siteNames,
            'deletedSiteIds' => $deletedSiteIds,
        ]);
    }
}
