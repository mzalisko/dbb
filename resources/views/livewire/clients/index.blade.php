<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Клієнти']">
        <button class="btn btn-secondary btn-sm">
            <x-icon.export width="13" height="13" /> Експорт
        </button>
        <button class="btn btn-primary btn-sm" wire:click="$dispatch('open-modal','client-form')">
            <x-icon.plus width="13" height="13" /> Додати клієнта
        </button>
    </x-ui.topbar>

    <x-ui.page-head
        :number="$clients->total()"
        label="клієнтів"
        sub="Усі клієнтські компанії та їхні WordPress-сайти." />

    <div style="padding:0 40px 64px;">
        @if (session('message'))
            <div style="margin-bottom:16px; padding:12px 16px; background:var(--ok-soft); color:var(--ok); border-radius:4px; font:13.5px var(--font-sans);">
                {{ session('message') }}
            </div>
        @endif

        {{-- Filters --}}
        <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); flex:1; max-width:440px;">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Пошук клієнтів…"
                    style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
            </div>
            <select wire:model.live="status" style="height:36px; padding:0 12px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); font:13px var(--font-sans); color:var(--ink-7); cursor:pointer;">
                <option value="">Всі статуси</option>
                <option value="active">Активний</option>
                <option value="inactive">Пауза</option>
                <option value="archived">Архів</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="card" style="overflow:hidden;">
            <div style="display:grid; grid-template-columns:1.8fr 1.2fr 80px 120px 120px 40px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                @foreach(['Компанія','Контакт','Сайти','Статус','Створено',''] as $h)
                    <span class="eyebrow" style="font-size:9.5px;">{{ $h }}</span>
                @endforeach
            </div>
            @forelse ($clients as $i => $client)
                <div style="display:grid; grid-template-columns:1.8fr 1.2fr 80px 120px 120px 40px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                    <a href="{{ route('clients.show', $client) }}" wire:navigate style="display:flex; align-items:center; gap:10px;">
                        <span class="avatar avatar-sq">{{ strtoupper(substr($client->company_name, 0, 1)) }}</span>
                        <span style="font:13.5px var(--font-sans); color:var(--ink-9);">{{ $client->company_name }}</span>
                    </a>
                    <div>
                        <div style="font:13px var(--font-sans); color:var(--ink-8);">{{ $client->contact_name ?? '—' }}</div>
                        <div style="font:11.5px var(--font-mono); color:var(--ink-5); margin-top:2px;">{{ $client->contact_email ?? '' }}</div>
                    </div>
                    <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $client->sites_count }}</span>
                    <span>
                        @php
                            $sc = match($client->status) { 'active'=>'ok','inactive'=>'warn',default=>'info' };
                            $sl = match($client->status) { 'active'=>'Активний','inactive'=>'Пауза',default=>'Архів' };
                        @endphp
                        <span class="pill pill-{{ $sc }}" style="font-size:11.5px;">{{ $sl }}</span>
                    </span>
                    <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5);">{{ $client->created_at->format('d M Y') }}</span>
                    <div style="display:flex; justify-content:flex-end;">
                        <button style="color:var(--ink-4); cursor:pointer; padding:4px;" wire:click.stop="$dispatch('edit-client', { id: {{ $client->id }} })">
                            <x-icon.more-v width="14" height="14" />
                        </button>
                    </div>
                </div>
            @empty
                <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    Немає клієнтів. Додайте першого.
                </div>
            @endforelse
        </div>

        @if ($clients->hasPages())
            <div style="margin-top:16px; display:flex; justify-content:center;">
                {{ $clients->links() }}
            </div>
        @endif
    </div>

    @livewire('clients.form')
</div>
