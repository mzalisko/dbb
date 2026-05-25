<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

class Form extends Component
{
    public ?int $clientId = null;

    #[Validate('required|string|max:255')]
    public string $company_name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $contact_name = '';

    #[Validate('nullable|email|max:255')]
    public ?string $contact_email = '';

    #[Validate('nullable|string|max:30')]
    public ?string $contact_phone = '';

    #[Validate('nullable|string')]
    public ?string $notes = '';

    #[Validate('required|in:active,inactive,archived')]
    public string $status = 'active';

    #[On('edit-client')]
    public function loadClient(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->clientId = $client->id;
        $this->company_name = $client->company_name;
        $this->contact_name = $client->contact_name ?? '';
        $this->contact_email = $client->contact_email ?? '';
        $this->contact_phone = $client->contact_phone ?? '';
        $this->notes = $client->notes ?? '';
        $this->status = $client->status;
        $this->dispatch('open-modal', 'client-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
            'status' => $this->status,
        ];

        if ($this->clientId) {
            $client = Client::findOrFail($this->clientId);
            $this->authorize('update', $client);
            $client->update($data);
        } else {
            $this->authorize('create', Client::class);
            $data['user_id'] = auth()->id();
            Client::create($data);
        }

        $this->reset();
        $this->dispatch('close-modal', 'client-form');
        $this->dispatch('client-saved');
    }

    public function render()
    {
        return view('livewire.clients.form');
    }
}
