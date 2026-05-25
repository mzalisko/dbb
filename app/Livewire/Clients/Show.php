<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Client $client;

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);
        $this->client = $client->load(['sites', 'user']);
    }

    public function deleteClient(): void
    {
        $this->authorize('delete', $this->client);
        $this->client->delete();
        $this->redirect(route('clients.index'), navigate: true);
    }

    #[On('client-saved')]
    public function refreshClient(): void
    {
        $this->client->refresh();
        $this->client->load(['sites', 'user']);
    }

    #[On('site-saved')]
    public function refreshSites(): void
    {
        $this->client->load('sites');
    }

    public function render()
    {
        return view('livewire.clients.show')
            ->title($this->client->company_name);
    }
}
