<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto; position:relative;">
    <x-ui.topbar :crumbs="['Команда']">
        @if (auth()->user()->isAdmin())
            <button wire:click="$dispatch('open-modal','invite-user')" class="btn btn-primary btn-sm">
                <x-icon.plus width="13" height="13" /> Запросити
            </button>
        @endif
    </x-ui.topbar>

    <x-ui.page-head
        :number="$users->total()"
        label="учасників"
        sub="Натисніть на учасника щоб переглянути права." />

    <div style="flex:1; overflow-y:auto; padding:0 40px 64px;">
        {{-- Search --}}
        <div style="margin-bottom:20px; max-width:400px;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3);">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Пошук по команді…"
                    style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
            </div>
        </div>

        <div class="card" style="overflow:hidden;">
            {{-- Table header --}}
            <div style="display:grid; grid-template-columns:44px 1.5fr 1fr 100px 1fr 44px; gap:16px; padding:12px 18px; border-bottom:1px solid var(--ink-3); background:var(--paper-2);">
                <span></span>
                @foreach (['Учасник', 'Email', 'Роль', 'Активність', ''] as $h)
                    <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                @endforeach
            </div>

            {{-- Rows --}}
            @forelse ($users as $i => $user)
                @php
                    $roleDot = match($user->role) {
                        'admin'   => 'dot-info',
                        'manager' => 'dot-ok',
                        default   => ''
                    };
                    $roleLabel = match($user->role) {
                        'admin'   => 'Admin',
                        'manager' => 'Manager',
                        default   => 'Viewer'
                    };
                    $nameParts = explode(' ', $user->name);
                    $initials = strtoupper(substr($nameParts[0], 0, 1)) . strtoupper(substr($nameParts[1] ?? $nameParts[0], 0, 1));
                    $isOnline = $user->last_login_at && $user->last_login_at->diffInMinutes() < 30;
                @endphp
                <div wire:click="openUser({{ $user->id }})"
                     style="display:grid; grid-template-columns:44px 1.5fr 1fr 100px 1fr 44px; gap:16px; padding:16px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s; border-left:2px solid {{ $openUserId === $user->id ? 'var(--ink-9)' : 'transparent' }}; background:{{ $openUserId === $user->id ? 'var(--paper-2)' : 'transparent' }};"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='{{ $openUserId === $user->id ? 'var(--paper-2)' : 'transparent' }}'">
                    <span style="position:relative;">
                        <span class="avatar" style="background:var(--ink-9); color:var(--paper);">
                            {{ $initials }}
                        </span>
                        @if ($isOnline)
                            <span style="position:absolute; right:-1px; bottom:-1px; width:8px; height:8px; border-radius:999px; background:var(--ok); border:2px solid var(--card);"></span>
                        @endif
                    </span>
                    <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $user->name }}</span>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-5); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $user->email }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-7); display:flex; align-items:center;">
                        <span class="dot {{ $roleDot }}"></span>{{ $roleLabel }}
                    </span>
                    <span class="mono" style="font:12px var(--font-mono); color:var(--ink-5);">
                        {{ $user->last_login_at?->diffForHumans() ?? 'Ніколи' }}
                    </span>
                    <x-icon.arrow width="14" height="14" style="color:var(--ink-4); justify-self:end;" />
                </div>
            @empty
                <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    Немає учасників.
                </div>
            @endforelse
        </div>

        <div style="margin-top:16px;">{{ $users->links() }}</div>
    </div>

    @if ($openUserId && $openUser)
        <x-ui.drawer
            :open="true"
            :title="$openUser->name"
            :sub="$openUser->email . ' · ' . $openUser->role"
            @drawer-close.window="$wire.closeUser()"
        >
            {{-- Role selector --}}
            <div>
                <label class="label">Роль</label>
                <div style="display:flex; gap:8px; margin-top:12px;">
                    @foreach ([['admin', 'Admin', 'Повний доступ'], ['manager', 'Manager', 'Дані + сайти'], ['viewer', 'Viewer', 'Тільки читання']] as [$k, $l, $d])
                        <button style="flex:1; padding:12px 14px; border-radius:4px; text-align:left; cursor:pointer;
                            border:1px solid {{ $openUser->role === $k ? 'var(--ink-9)' : 'var(--ink-3)' }};
                            background:{{ $openUser->role === $k ? 'var(--ink-9)' : 'transparent' }};
                            color:{{ $openUser->role === $k ? 'var(--paper)' : 'var(--ink-9)' }};">
                            <div style="font:14px var(--font-sans);">{{ $l }}</div>
                            <div style="margin-top:4px; font:11.5px var(--font-mono); opacity:.7;">{{ $d }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Permissions matrix --}}
            <div style="margin-top:32px;">
                <label class="label">Деталізовані права</label>
                <div style="margin-top:14px;">
                    <div style="display:grid; grid-template-columns:1fr 60px 60px 60px 60px; gap:8px; padding:10px 0; border-bottom:1px solid var(--ink-3);">
                        <span class="eyebrow" style="font-size:9.5px;">Ресурс</span>
                        @foreach (['Read', 'Create', 'Edit', 'Delete'] as $a)
                            <span class="eyebrow" style="font-size:9.5px; text-align:center;">{{ $a }}</span>
                        @endforeach
                    </div>
                    @foreach ([
                        ['Sites', [true, true, true, true]],
                        ['Phones', [true, true, true, true]],
                        ['Site groups', [true, true, true, false]],
                        ['Team', [true, true, true, true]],
                        ['API keys', [true, true, false, true]],
                    ] as $i => [$r, $perms])
                        <div style="display:grid; grid-template-columns:1fr 60px 60px 60px 60px; gap:8px; padding:12px 0; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center;">
                            <span style="font:13.5px var(--font-sans); color:var(--ink-9);">{{ $r }}</span>
                            @foreach ($perms as $on)
                                <div style="display:flex; justify-content:center;">
                                    <span style="width:16px; height:16px; border-radius:3px; background:{{ $on ? 'var(--ink-9)' : 'transparent' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }}; display:inline-flex; align-items:center; justify-content:center; color:var(--paper);">
                                        @if ($on) <x-icon.check width="10" height="10" /> @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="closeUser">Скасувати</button>
                <button class="btn btn-primary">Зберегти</button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif

    @livewire('users.invite-form')
</div>
