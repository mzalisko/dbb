<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto; position:relative;">
    <x-ui.topbar :crumbs="['Команда']">
        @if (auth()->user()->isAdmin())
            <button wire:click="$dispatch('open-modal','invite-user')" class="btn btn-primary btn-sm">
                <x-icon.plus width="13" height="13" /> Запросити
            </button>
        @endif
    </x-ui.topbar>

    <x-ui.page-head
        title="Команда"
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
                <div style="display:grid; grid-template-columns:44px 1.5fr 1fr 100px 1fr 44px; gap:16px; padding:16px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
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

    @livewire('users.invite-form')
</div>
