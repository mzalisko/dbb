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
