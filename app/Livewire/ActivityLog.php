<?php

namespace App\Livewire;

use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app')]
#[Title('Логи')]
class ActivityLog extends Component
{
    #[Url]
    public string $tab = 'data';

    public function render()
    {
        $dataLogs = \App\Models\ActivityLog::with([
            'user',
            'subject' => function (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) {
                $morphTo->morphWith([
                    \App\Models\ContactEntry::class => ['site'],
                ]);
            },
        ])->latest('created_at')->take(50)->get();

        $sites = Site::orderBy('name')->take(20)->get();

        return view('livewire.activity-log', compact('dataLogs', 'sites'));
    }
}
