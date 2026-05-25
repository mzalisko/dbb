<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Site $site;

    public function mount(Site $site): void
    {
        $site->loadMissing('client');
        $this->authorize('view', $site);
        $this->site = $site->load('client');
    }

    #[On('site-saved')]
    public function refreshSite(): void
    {
        $this->site->refresh();
        $this->site->load('client');
    }

    public function render()
    {
        return view('livewire.sites.show')
            ->title($this->site->name);
    }
}
