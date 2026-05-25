<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Database\Eloquent\Collection;

#[Layout('components.layouts.app')]
#[Title('Сайт')]
class Show extends Component
{
    public Site $site;
    public string $geoFilter = 'all';

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site->load('client');
    }

    public function updatedGeoFilter(): void {}

    private function filterForGeo(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;
        return $entries->filter(fn($e) => $e->visibleForGeo($geo))->values();
    }

    public function render()
    {
        $allPhones = $this->site->contactEntries()->where('type', 'phone')->where('visible', true)->orderBy('order')->with('backups')->get();
        $allMsgs   = $this->site->contactEntries()->where('type', 'messenger')->where('visible', true)->orderBy('order')->with('backups')->get();

        // Overview: 3 geo pools with their primaries and backups
        $geos = [
            ['key' => 'PL',    'flag' => '🇵🇱', 'label' => 'Польща'],
            ['key' => 'UA',    'flag' => '🇺🇦', 'label' => 'Україна'],
            ['key' => 'world', 'flag' => '🌐',  'label' => 'Світ'],
        ];
        foreach ($geos as &$geo) {
            $geoPhones = $this->filterForGeo($allPhones, $geo['key']);
            $geoMsgs   = $this->filterForGeo($allMsgs,   $geo['key']);
            $geo['primaryPhone'] = $geoPhones->firstWhere('role', 'primary');
            $geo['primaryMsg']   = $geoMsgs->firstWhere('role', 'primary');
            $geo['backupPhones'] = $geo['primaryPhone']
                ? $allPhones->where('parent_id', $geo['primaryPhone']->id)->values()
                : collect();
            $geo['backupMsgs']   = $geo['primaryMsg']
                ? $allMsgs->where('parent_id', $geo['primaryMsg']->id)->values()
                : collect();
        }
        unset($geo);

        // Data tab: filter by selected geo
        $phonePrimaries = $this->filterForGeo($allPhones, $this->geoFilter)
            ->filter(fn($e) => is_null($e->parent_id))
            ->values();
        $msgPrimaries = $this->filterForGeo($allMsgs, $this->geoFilter)
            ->filter(fn($e) => is_null($e->parent_id))
            ->values();

        // Prices
        $allPrices = $this->site->contactEntries()->where('type', 'price')->where('visible', true)->get();
        $priceBySku = $allPrices->groupBy('sku');

        // Activity
        $activityLogs = ActivityLog::where('subject_type', Site::class)
            ->where('subject_id', $this->site->id)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        // Counts
        $phoneCount = $allPhones->count();
        $msgCount   = $allMsgs->count();
        $priceCount = $allPrices->count();

        return view('livewire.sites.show', compact(
            'geos',
            'allPhones', 'allMsgs',
            'phonePrimaries', 'msgPrimaries',
            'priceBySku',
            'activityLogs',
            'phoneCount', 'msgCount', 'priceCount',
        ));
    }
}
