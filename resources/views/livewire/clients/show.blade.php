<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Клієнти', $client->company_name]">
        <button class="btn btn-secondary btn-sm" wire:click="$dispatch('edit-client', { id: {{ $client->id }} })">
            <x-icon.edit width="13" height="13" /> Редагувати
        </button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Клієнт"
        :title="$client->company_name" />

    <div style="padding:0 40px 64px;">

        {{-- Contact info card --}}
        <div class="card" style="margin-bottom:24px; overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--ink-3); display:flex; align-items:center; justify-content:space-between;">
                <span class="eyebrow" style="font-size:10px;">Контактна інформація</span>
                @php
                    $sc = match($client->status) { 'active'=>'var(--ok)','inactive'=>'var(--warn)',default=>'var(--info)' };
                    $sl = match($client->status) { 'active'=>'Активний','inactive'=>'Пауза',default=>'Архів' };
                @endphp
                <span style="display:inline-flex; align-items:center; gap:5px; font:12px var(--font-sans); color:{{ $sc }};">
                    <span style="width:6px;height:6px;border-radius:999px;background:{{ $sc }};"></span>
                    {{ $sl }}
                </span>
            </div>
            <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <div class="eyebrow" style="font-size:9.5px; margin-bottom:6px;">Ім'я</div>
                    <div style="font:14px var(--font-sans); color:var(--ink-9);">{{ $client->contact_name ?? '—' }}</div>
                </div>
                <div>
                    <div class="eyebrow" style="font-size:9.5px; margin-bottom:6px;">Email</div>
                    <div style="font:14px var(--font-sans);">
                        @if ($client->contact_email)
                            <a href="mailto:{{ $client->contact_email }}" style="color:var(--accent);">{{ $client->contact_email }}</a>
                        @else
                            <span style="color:var(--ink-4);">—</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="eyebrow" style="font-size:9.5px; margin-bottom:6px;">Телефон</div>
                    <div class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $client->contact_phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="eyebrow" style="font-size:9.5px; margin-bottom:6px;">Створив</div>
                    <div style="font:13.5px var(--font-sans); color:var(--ink-7);">{{ $client->user->name ?? '—' }}</div>
                </div>
            </div>
            @if ($client->notes)
                <div style="padding:16px 20px; border-top:1px solid var(--ink-3);">
                    <div class="eyebrow" style="font-size:9.5px; margin-bottom:8px;">Нотатки</div>
                    <p style="font:13.5px/1.5 var(--font-sans); color:var(--ink-7);">{{ $client->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Sites table --}}
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--ink-3); display:flex; align-items:center; justify-content:space-between;">
                <span class="eyebrow" style="font-size:10px;">
                    WordPress-сайти
                    <span style="background:var(--ink-9); color:var(--paper); border-radius:999px; padding:1px 7px; font-size:9px; margin-left:6px;">{{ $client->sites->count() }}</span>
                </span>
                <button class="btn btn-primary btn-sm" wire:click="$dispatch('create-site-for-client', { clientId: {{ $client->id }} })">
                    <x-icon.plus width="12" height="12" /> Додати сайт
                </button>
            </div>
            <div style="display:grid; grid-template-columns:1.6fr 80px 80px 120px 140px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                @foreach(['Сайт','WP','PHP','Статус','Перевірено'] as $h)
                    <span class="eyebrow" style="font-size:9.5px;">{{ $h }}</span>
                @endforeach
            </div>
            @forelse ($client->sites as $i => $site)
                @php
                    $dotBg = match($site->status) { 'active'=>'var(--ok)','maintenance'=>'var(--warn)',default=>'var(--bad)' };
                    $statusLabel = match($site->status) { 'active'=>'Активний','maintenance'=>'Пауза',default=>'Офлайн' };
                @endphp
                <div style="display:grid; grid-template-columns:1.6fr 80px 80px 120px 140px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                    <div>
                        <a href="{{ route('sites.show', $site) }}" wire:navigate class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $site->name }}</a>
                        @if ($site->url)
                            <div style="font:11.5px var(--font-mono); color:var(--ink-5); margin-top:2px;">{{ $site->url }}</div>
                        @endif
                    </div>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $site->wp_version ?? '—' }}</span>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $site->php_version ?? '—' }}</span>
                    <span style="display:inline-flex; align-items:center; gap:5px; font:12px var(--font-sans); color:var(--ink-7);">
                        <span style="width:6px;height:6px;border-radius:999px;background:{{ $dotBg }};"></span>
                        {{ $statusLabel }}
                    </span>
                    <span class="mono" style="font:12px var(--font-mono); color:var(--ink-5);">{{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}</span>
                </div>
            @empty
                <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    Немає сайтів. Додайте перший WordPress-сайт для цього клієнта.
                </div>
            @endforelse
        </div>
    </div>

    @livewire('clients.form')
    @livewire('sites.form')
</div>
