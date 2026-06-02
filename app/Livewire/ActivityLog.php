<?php

namespace App\Livewire;

use App\Models\Site;
use App\Services\AuditFeed;
use App\Support\AuditAction;
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

    #[Url]
    public string $filterDomain = '';

    #[Url]
    public string $filterSeverity = '';

    #[Url]
    public string $filterSite = '';

    #[Url]
    public string $search = '';

    /** Selected event, serialized for the detail drawer (null = closed). */
    public ?array $detail = null;

    public function updating($name): void
    {
        if (str_starts_with($name, 'filter') || $name === 'search') {
            $this->resetPage();
        }
    }

    /** @return array<string,mixed> active filters for AuditFeed. */
    private function filters(): array
    {
        return array_filter([
            'domain'   => $this->filterDomain ?: null,
            'severity' => $this->filterSeverity !== '' ? (int) $this->filterSeverity : null,
            'site_id'  => $this->filterSite ?: null,
            'search'   => $this->search ?: null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function openDetail(string $source, int $id): void
    {
        $event = AuditFeed::collect($this->filters())
            ->first(fn ($e) => $e->source === $source && $e->id === $id);

        if (! $event) {
            return;
        }

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
            'batchId'       => $event->batchId,
            'siteId'        => $event->siteId,
            'isBulk'        => str_contains($event->actionCode, '.bulk.'),
            'changes'       => $event->changes(),
            'summary'       => $event->new,
        ];
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    public function clearFilters(): void
    {
        $this->reset('filterDomain', 'filterSeverity', 'filterSite', 'search');
        $this->resetPage();
    }

    /** Stream the current filtered feed as CSV (capped to AuditFeed's window). */
    public function export()
    {
        $events = AuditFeed::collect($this->filters());
        $filename = 'audit-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            fputcsv($out, ['Час', 'Подія', 'Код', 'Сайт', 'Користувач', 'Важливість', 'IP', 'Зміни']);

            foreach ($events as $e) {
                $changes = collect($e->changes())
                    ->map(fn ($c) => $c['field'].': '.json_encode($c['old'], JSON_UNESCAPED_UNICODE).' → '.json_encode($c['new'], JSON_UNESCAPED_UNICODE))
                    ->implode('; ');

                fputcsv($out, [
                    $e->occurredAt->format('Y-m-d H:i:s'),
                    $e->label(),
                    $e->actionCode,
                    $e->siteId,
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
        $events = AuditFeed::paginate($this->filters(), 30);
        $counts = AuditFeed::counts($this->filters());

        return view('livewire.activity-log', [
            'events'     => $events,
            'counts'     => $counts,
            'sites'      => Site::orderBy('name')->get(['id', 'name']),
            'siteNames'  => Site::pluck('name', 'id'),
            'domains'    => [
                'entry'  => 'Дані',
                'site'   => 'Сайти',
                'group'  => 'Групи',
                'user'   => 'Команда',
                'auth'   => 'Авторизація',
                'system' => 'Система',
            ],
            'severities' => [
                AuditAction::INFO     => 'Інфо',
                AuditAction::WARN     => 'Увага',
                AuditAction::CRITICAL => 'Критичні',
            ],
        ]);
    }
}
