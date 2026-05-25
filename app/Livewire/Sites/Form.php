<?php

namespace App\Livewire\Sites;

use App\Models\Client;
use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

class Form extends Component
{
    public ?int $siteId = null;

    #[Validate('required|exists:clients,id')]
    public ?int $client_id = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|url|max:255')]
    public string $url = '';

    #[Validate('nullable|string|max:20')]
    public ?string $wp_version = '';

    #[Validate('nullable|string|max:10')]
    public ?string $php_version = '';

    #[Validate('required|in:active,maintenance,offline,archived')]
    public string $status = 'active';

    #[Validate('nullable|string')]
    public ?string $notes = '';

    /** Pre-set client_id when creating from client context */
    public bool $clientLocked = false;

    #[On('edit-site')]
    public function loadSite(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->siteId = $site->id;
        $this->client_id = $site->client_id;
        $this->name = $site->name;
        $this->url = $site->url;
        $this->wp_version = $site->wp_version ?? '';
        $this->php_version = $site->php_version ?? '';
        $this->status = $site->status;
        $this->notes = $site->notes ?? '';
        $this->clientLocked = false;
        $this->dispatch('open-modal', 'site-form');
    }

    #[On('create-site-for-client')]
    public function setClient(int $clientId): void
    {
        $this->reset();
        $this->client_id = $clientId;
        $this->clientLocked = true;
        $this->dispatch('open-modal', 'site-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'client_id' => $this->client_id,
            'name' => $this->name,
            'url' => $this->url,
            'wp_version' => $this->wp_version ?: null,
            'php_version' => $this->php_version ?: null,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
        ];

        if ($this->siteId) {
            $site = Site::with('client')->findOrFail($this->siteId);
            $this->authorize('update', $site);
            $site->update($data);
        } else {
            $this->authorize('create', Site::class);
            Site::create($data);
        }

        $this->reset();
        $this->dispatch('close-modal', 'site-form');
        $this->dispatch('site-saved');
    }

    public function render()
    {
        $clients = Client::orderBy('company_name')->get(['id', 'company_name']);

        return view('livewire.sites.form', compact('clients'));
    }
}
