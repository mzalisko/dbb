<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Групи сайтів']">
        <x-ui.button size="sm">
            <x-icon.plus width="13" height="13" /> Нова група
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        :number="$groups->count()"
        label="груп"
        sub="Об'єднайте сайти за середовищем, регіоном чи командою." />

    <div style="padding:0 40px 64px; display:grid; grid-template-columns:repeat(2, 1fr); gap:14px;">
        @forelse ($groups as $group)
            <a href="{{ route('sites.index', ['groupFilter' => $group->group]) }}" wire:navigate style="display:block; text-decoration:none; color:inherit;">
            <article class="card" style="padding:22px; position:relative; overflow:hidden; cursor:pointer; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='var(--ink-9)'"
                     onmouseout="this.style.borderColor='var(--ink-3)'">
                {{-- Color accent bar --}}
                <div style="position:absolute; top:0; left:0; bottom:0; width:3px; background:{{ $group->group_color }};"></div>

                <header style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="flex:1;">
                        <div style="display:flex; align-items:baseline; gap:12px;">
                            <h3 style="font:400 22px/1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">{{ ucfirst($group->group) }}</h3>
                            <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4); letter-spacing:.06em; text-transform:uppercase;">{{ $group->sites_count }} сайтів</span>
                        </div>
                    </div>
                    <button style="color:var(--ink-4); padding:4px; cursor:pointer;">
                        <x-icon.more-v width="14" height="14" />
                    </button>
                </header>

                {{-- Site preview list --}}
                <div style="margin-top:18px; display:flex; flex-direction:column; gap:4px;">
                    @foreach ($group->sites as $site)
                        @php
                            $dotClass = match($site->status) {
                                'active' => 'dot-ok',
                                'maintenance' => 'dot-warn',
                                default => 'dot-bad',
                            };
                        @endphp
                        <div style="display:flex; align-items:center; gap:10px; padding:6px 0;">
                            <span class="dot {{ $dotClass }}" style="margin:0; flex-shrink:0;"></span>
                            <a href="{{ route('sites.show', $site) }}" wire:navigate @click.stop
                               class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $site->name }}</a>
                            <div style="flex:1;"></div>
                            <span style="font:11px var(--font-mono); color:var(--ink-4);">{{ $site->last_checked_at?->diffForHumans(null, true) ?? '—' }}</span>
                        </div>
                    @endforeach

                    @if ($group->sites_count > $group->sites->count())
                        <div style="font:12px var(--font-sans); color:var(--ink-4); padding:6px 0;">
                            + {{ $group->sites_count - $group->sites->count() }} ще
                        </div>
                    @endif
                </div>

                <footer style="margin-top:18px; padding-top:14px; border-top:1px solid var(--ink-3); display:flex; align-items:center; gap:14px; font:11.5px var(--font-mono); color:var(--ink-5);">
                    @php
                        $activeCount = $group->sites->where('status', 'active')->count();
                        $errorCount  = $group->sites->where('status', 'offline')->count();
                    @endphp
                    <span><span class="dot dot-ok"></span>{{ $activeCount }} активні</span>
                    @if ($errorCount > 0)
                        <span><span class="dot dot-bad"></span>{{ $errorCount }} помилка</span>
                    @endif
                    <div style="flex:1;"></div>
                    <a href="{{ route('sites.index', ['groupFilter' => $group->group]) }}" wire:navigate style="color:var(--ink-9); display:inline-flex; align-items:center; gap:4px;">
                        Відкрити <x-icon.arrow width="11" height="11" />
                    </a>
                </footer>
            </article>
            </a>
        @empty
            <div style="grid-column:1/-1; padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Немає груп. Додайте перший сайт з групою.
            </div>
        @endforelse

        {{-- New group button --}}
        <button style="
            padding:22px; border-radius:4px;
            border:1px dashed var(--ink-3); background:transparent;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:10px; color:var(--ink-5); min-height:200px; cursor:pointer;
            transition:all .15s;"
            onmouseover="this.style.borderColor='var(--ink-9)'; this.style.color='var(--ink-9)'"
            onmouseout="this.style.borderColor='var(--ink-3)'; this.style.color='var(--ink-5)'">
            <x-icon.plus width="20" height="20" />
            <span style="font:13.5px var(--font-sans);">Нова група</span>
        </button>
    </div>
</div>
