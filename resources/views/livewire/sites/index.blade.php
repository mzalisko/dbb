<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Сайти']">
        <x-ui.button variant="secondary" size="sm" style="margin-right:4px;">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
        <x-ui.button size="sm">
            <x-icon.plus width="13" height="13" /> Додати сайт
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        :title="'Сайти · ' . $sites->count()"
        sub="Натисніть на картку щоб відкрити сайт зі всіма контактами." />

    {{-- Group filter --}}
    <div style="padding:0 40px 24px; display:flex; gap:8px; flex-wrap:wrap;">
        <button wire:click="$set('groupFilter', 'all')" style="
            display:inline-flex; align-items:center; gap:8px;
            height:32px; padding:0 14px; border-radius:999px;
            background:{{ $groupFilter === 'all' ? 'var(--ink-9)' : 'var(--card)' }};
            color:{{ $groupFilter === 'all' ? 'var(--paper)' : 'var(--ink-7)' }};
            box-shadow:{{ $groupFilter === 'all' ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
            font:13px var(--font-sans); cursor:pointer;">
            Усі
        </button>
        @foreach ($groups as $group)
            <button wire:click="$set('groupFilter', '{{ $group->group }}')" style="
                display:inline-flex; align-items:center; gap:8px;
                height:32px; padding:0 14px; border-radius:999px;
                background:{{ $groupFilter === $group->group ? 'var(--ink-9)' : 'var(--card)' }};
                color:{{ $groupFilter === $group->group ? 'var(--paper)' : 'var(--ink-7)' }};
                box-shadow:{{ $groupFilter === $group->group ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
                font:13px var(--font-sans); cursor:pointer;">
                <span style="width:6px; height:6px; border-radius:999px; background:{{ $group->group_color }};"></span>
                {{ ucfirst($group->group) }}
            </button>
        @endforeach
    </div>

    {{-- Site cards grid --}}
    <div style="padding:0 40px 64px; display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;">
        @forelse ($sites as $site)
            @php
                $statusClass = match($site->status) {
                    'active'      => 'dot-ok',
                    'maintenance' => 'dot-warn',
                    default       => 'dot-bad',
                };
                $statusLabel = match($site->status) {
                    'active'      => 'Активний',
                    'maintenance' => 'Пауза',
                    default       => 'Помилка',
                };
            @endphp
            <a href="{{ route('sites.show', $site) }}" wire:navigate style="
                text-align:left; padding:18px; background:var(--card);
                border:1px solid var(--ink-3); border-radius:4px;
                cursor:pointer; transition:border-color .15s; display:block;"
               onmouseover="this.style.borderColor='var(--ink-9)'"
               onmouseout="this.style.borderColor='var(--ink-3)'">

                <div style="display:flex; align-items:flex-start; gap:10px;">
                    <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                    <div style="flex:1; min-width:0;">
                        <div class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $site->name }}</div>
                        <div style="margin-top:4px; font:12px var(--font-sans); color:{{ $site->status === 'offline' ? 'var(--bad)' : 'var(--ink-5)' }};">
                            <span class="dot {{ $statusClass }}"></span>{{ $statusLabel }}
                            <span style="color:var(--ink-4); margin-left:6px;">· {{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}</span>
                        </div>
                    </div>
                </div>

                @if ($site->status === 'offline')
                    <div style="margin-top:12px; padding:8px 10px; border-left:2px solid var(--bad); background:var(--bad-soft); font:12px var(--font-mono); color:var(--bad);">
                        Connection refused
                    </div>
                @endif

                <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--ink-3); display:flex; gap:18px; font:12px var(--font-mono); color:var(--ink-5);">
                    @if ($site->group)
                        <span>
                            <span style="width:6px; height:6px; border-radius:999px; background:{{ $site->group_color ?? '#a39d8c' }}; display:inline-block; margin-right:6px;"></span>
                            {{ ucfirst($site->group) }}
                        </span>
                    @endif
                    <span>📞 {{ $site->contactEntries()->where('type', 'phone')->where('visible', true)->count() }}</span>
                    <span>💬 {{ $site->contactEntries()->where('type', 'messenger')->where('visible', true)->count() }}</span>
                </div>
            </a>
        @empty
            <div style="grid-column:1/-1; padding:80px 40px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Немає сайтів. Додайте перший.
            </div>
        @endforelse
    </div>
</div>
