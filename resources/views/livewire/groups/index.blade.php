<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;"
     x-data="{ showCreate: false, gName: '', gColor: '#5a8a3c', showDelete: false, deleteTarget: '', deleteConfirm: '' }"
     @group-created.window="showCreate = false; gName = ''; gColor = '#5a8a3c'"
     @group-deleted.window="showDelete = false; deleteTarget = ''; deleteConfirm = ''">

    <x-ui.topbar :crumbs="['Групи сайтів']">
        <x-ui.button size="sm" @click="showCreate = true">
            <x-icon.plus width="13" height="13" /> Нова група
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        :number="$groups->count()"
        :label="ua_word($groups->count(), 'група', 'групи', 'груп')"
        sub="Об'єднайте сайти за середовищем, регіоном чи командою." />

    <div style="padding:0 40px 64px; display:grid; grid-template-columns:repeat(2, 1fr); gap:14px;">
        @forelse ($groups as $group)
            @php $url = route('sites.index', ['group' => $group->group]); @endphp

            {{-- Card: position:relative so overlay-link works --}}
            <article style="position:relative; background:var(--card); border:1px solid var(--ink-3);
                            border-radius:4px; overflow:hidden; display:flex; flex-direction:column;
                            transition:border-color .15s;"
                     onmouseover="this.style.borderColor='var(--ink-9)'"
                     onmouseout="this.style.borderColor='var(--ink-3)'">

                {{-- Overlay link covers entire card (z-index:0 — behind inner interactive elements) --}}
                <a href="{{ $url }}" wire:navigate
                   style="position:absolute; inset:0; z-index:0;" aria-label="{{ ucfirst($group->group) }}"></a>

                {{-- Left color bar --}}
                <div style="position:absolute; top:0; left:0; bottom:0; width:3px; background:{{ $group->group_color }};"></div>

                {{-- Card body --}}
                <div style="padding:22px 22px 18px 28px; flex:1; display:flex; flex-direction:column;">

                    {{-- Header --}}
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:16px;">
                        <div style="display:flex; align-items:baseline; gap:12px; flex:1; min-width:0;">
                            <h3 style="font:400 22px/1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">
                                {{ ucfirst($group->group) }}
                            </h3>
                            <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);
                                                      letter-spacing:.06em; text-transform:uppercase; white-space:nowrap;">
                                {{ $group->sites_count }} {{ ua_word($group->sites_count, 'сайт', 'сайти', 'сайтів') }}
                            </span>
                        </div>
                        {{-- Dots menu — z-index:1 to be above overlay; only for users who can manage groups --}}
                        @if ($canManageGroups)
                            <div style="position:relative; z-index:2; flex-shrink:0;" x-data="{ open: false }">
                                <button style="color:var(--ink-4); padding:4px; border-radius:4px; transition:color .12s; display:block;"
                                        @click.stop.prevent="open = !open"
                                        onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-4)'">
                                    <x-icon.more-v width="14" height="14" />
                                </button>
                                <div class="dropdown" x-show="open" x-cloak style="top:calc(100% + 4px); right:0;"
                                     @click.outside="open = false">
                                    <button class="dropdown-item dropdown-item--danger"
                                            @click.stop.prevent="open = false; deleteTarget = '{{ $group->group }}'; deleteConfirm = ''; showDelete = true">
                                        Видалити групу
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Sites list — scrollable, z-index:1 for inner links --}}
                    @if ($group->sites->isNotEmpty())
                        <div class="thin-scroll"
                             style="position:relative; z-index:1; flex:1; max-height:180px; overflow-y:auto;
                                    margin-right:-6px; padding-right:6px;">
                            @foreach ($group->sites as $site)
                                @php
                                    $dotCls = match($site->status) {
                                        'active'      => 'dot-ok',
                                        'maintenance' => 'dot-warn',
                                        default       => 'dot-bad',
                                    };
                                @endphp
                                <div style="display:flex; align-items:center; gap:10px; padding:7px 0;
                                            border-bottom:1px solid var(--ink-3);">
                                    <span class="dot {{ $dotCls }}" style="margin:0; flex-shrink:0;"></span>
                                    <a href="{{ route('sites.show', $site) }}" wire:navigate @click.stop
                                       class="mono"
                                       style="font:12.5px var(--font-mono); color:var(--ink-8);
                                              flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        {{ $site->name }}
                                    </a>
                                    <span style="font:11px var(--font-mono); color:var(--ink-4); flex-shrink:0;">
                                        {{ $site->last_checked_at?->diffForHumans(null, true) ?? '—' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="flex:1; padding:24px 0; color:var(--ink-4); font:13px var(--font-sans);">
                            Немає сайтів
                        </div>
                    @endif

                    {{-- Footer --}}
                    <div style="position:relative; z-index:1; margin-top:16px; padding-top:14px;
                                border-top:1px solid var(--ink-3); display:flex; align-items:center;
                                gap:14px; font:11.5px var(--font-mono); color:var(--ink-5);">
                        <span><span class="dot dot-ok"></span>{{ $group->active_count }} {{ ua_word($group->active_count, 'активний', 'активні', 'активних') }}</span>
                        @if ($group->error_count > 0)
                            <span><span class="dot dot-bad"></span>{{ $group->error_count }} {{ ua_word($group->error_count, 'помилка', 'помилки', 'помилок') }}</span>
                        @endif
                        <div style="flex:1;"></div>
                        <a href="{{ $url }}" wire:navigate @click.stop
                           style="position:relative; z-index:1; color:var(--ink-9);
                                  display:inline-flex; align-items:center; gap:4px;
                                  font:12px var(--font-mono);">
                            Відкрити <x-icon.arrow width="11" height="11" />
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div style="grid-column:1/-1; padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Немає груп. Натисніть «Нова група».
            </div>
        @endforelse

        {{-- New group card --}}
        <button @click="showCreate = true"
                style="padding:22px; border-radius:4px;
                       border:1px dashed var(--ink-3); background:transparent;
                       display:flex; flex-direction:column; align-items:center; justify-content:center;
                       gap:10px; color:var(--ink-5); min-height:220px; cursor:pointer;
                       transition:all .15s;"
                onmouseover="this.style.borderColor='var(--ink-9)'; this.style.color='var(--ink-9)'"
                onmouseout="this.style.borderColor='var(--ink-3)'; this.style.color='var(--ink-5)'">
            <x-icon.plus width="20" height="20" />
            <span style="font:13.5px var(--font-sans);">Нова група</span>
        </button>
    </div>

    {{-- ══ Create Group Drawer ══ --}}
    <div x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showCreate = false"
         style="position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:60;"></div>

    <div x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-x-5"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-5"
         style="position:fixed; top:0; right:0; bottom:0; width:440px; max-width:100vw;
                background:var(--card); border-left:1px solid var(--ink-3); z-index:61;
                display:flex; flex-direction:column;"
         @keydown.escape.window="showCreate = false">

        <div style="padding:28px 28px 0; flex-shrink:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                <h2 style="font:400 22px/1.1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">Нова група</h2>
                <button @click="showCreate = false"
                        style="width:28px; height:28px; border-radius:999px; color:var(--ink-5);
                               display:inline-flex; align-items:center; justify-content:center;
                               flex-shrink:0; transition:color .12s; margin-top:2px;"
                        onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                    <x-icon.close width="16" height="16" style="display:block;" />
                </button>
            </div>
            <div style="margin-top:20px; border-bottom:1px solid var(--ink-3);"></div>
        </div>

        <div style="flex:1; overflow-y:auto; padding:24px 28px;">

            <div style="margin-bottom:24px;">
                <label class="label">Назва групи</label>
                <input type="text" class="input" x-model="gName"
                       placeholder="production, staging, demo…"
                       @keydown.enter="$wire.createGroup(gName, gColor)" />
                @error('createGroupName')
                    <span style="font:12px var(--font-sans); color:var(--bad); margin-top:6px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:24px;">
                <label class="label">Колір</label>
                <div style="display:flex; align-items:center; gap:14px; margin-top:8px;">
                    <input type="color" x-model="gColor"
                           style="width:40px; height:40px; border:1px solid var(--ink-3);
                                  border-radius:6px; cursor:pointer; padding:2px;" />
                    <span style="font:13px var(--font-mono); color:var(--ink-5);" x-text="gColor">#5a8a3c</span>
                </div>
            </div>

            {{-- Live preview (client-side Alpine — instant) --}}
            <div style="padding:14px 16px; background:var(--paper-2); border:1px solid var(--ink-3); border-radius:6px;">
                <span style="font:11px var(--font-mono); color:var(--ink-5); letter-spacing:.08em; text-transform:uppercase;">Попередній перегляд</span>
                <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                    <div style="width:3px; height:28px; border-radius:2px;" :style="{ background: gColor }"></div>
                    <span style="font:16px var(--font-sans); color:var(--ink-9);" x-text="gName || 'Назва групи'">Назва групи</span>
                </div>
            </div>

        </div>

        <div style="flex-shrink:0; padding:16px 28px; border-top:1px solid var(--ink-3);
                    display:flex; align-items:center; justify-content:flex-end; gap:8px;
                    background:var(--paper-2);">
            <x-ui.button variant="ghost" @click="showCreate = false">Скасувати</x-ui.button>
            <x-ui.button @click="$wire.createGroup(gName, gColor)" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="createGroup">Створити</span>
                <span wire:loading wire:target="createGroup">Збереження…</span>
            </x-ui.button>
        </div>
    </div>

    {{-- ══ Delete Group Confirmation Drawer ══ --}}
    <div class="drawer-backdrop" x-show="showDelete" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="showDelete = false"></div>

    <div class="drawer-panel drawer-panel--narrow" x-show="showDelete" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-x-5" x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-5"
         @keydown.escape.window="showDelete = false">

        <div class="drawer-head">
            <div class="drawer-head__row">
                <h2 class="drawer-title">Видалити групу</h2>
                <button class="drawer-close" @click="showDelete = false">
                    <x-icon.close width="16" height="16" style="display:block;" />
                </button>
            </div>
            <div class="drawer-divider"></div>
        </div>

        <div class="drawer-body">
            <p style="font:14px/1.6 var(--font-sans); color:var(--ink-7); margin-bottom:20px;">
                Групу <strong style="color:var(--ink-9);" x-text="deleteTarget"></strong> буде видалено.
                Сайти НЕ видаляються — вони лишаться без групи.
            </p>

            <div class="field">
                <label class="label">Для підтвердження введіть назву групи</label>
                <input type="text" class="input" x-model="deleteConfirm"
                       :placeholder="deleteTarget"
                       @keydown.enter="deleteConfirm === deleteTarget && $wire.deleteGroup(deleteTarget)" />
            </div>
        </div>

        <div class="drawer-foot">
            <x-ui.button variant="ghost" @click="showDelete = false">Скасувати</x-ui.button>
            <x-ui.button variant="danger"
                         x-bind:disabled="deleteConfirm !== deleteTarget"
                         @click="$wire.deleteGroup(deleteTarget)"
                         wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="deleteGroup">Видалити</span>
                <span wire:loading wire:target="deleteGroup">Видалення…</span>
            </x-ui.button>
        </div>
    </div>
</div>
