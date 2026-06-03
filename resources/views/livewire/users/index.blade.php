<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto; position:relative;">
    <x-ui.topbar :crumbs="['Команда']">
        @if (auth()->user()->isAdmin())
            <button @click="$dispatch('open-modal', 'invite-user')" class="btn btn-primary btn-sm">
                <x-icon.plus width="13" height="13" /> Запросити
            </button>
        @endif
    </x-ui.topbar>

    <x-ui.page-head
        :number="$users->total()"
        label="учасників"
        sub="Натисніть на учасника щоб переглянути та змінити права." />

    <div class="team-scroll">
        {{-- Search --}}
        <div class="team-search-wrap">
            <div class="team-search">
                <x-icon.search width="15" height="15" style="color:var(--ink-5); flex-shrink:0;" />
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Пошук по команді…"
                    class="team-search__input" />
            </div>
        </div>

        <div class="card team-list">
            {{-- Table header --}}
            <div class="team-grid team-head">
                <span></span>
                @foreach (['Учасник', 'Email', 'Роль', 'Активність', ''] as $h)
                    <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                @endforeach
            </div>

            {{-- Rows --}}
            @forelse ($users as $user)
                @php
                    $parts    = explode(' ', trim($user->name));
                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8')
                              . mb_strtoupper(mb_substr($parts[1] ?? $parts[0], 0, 1, 'UTF-8'), 'UTF-8');
                    $isOnline = $user->last_login_at && $user->last_login_at->diffInMinutes() < 30;
                    $roleDot  = match($user->role) {
                        'owner', 'admin' => 'dot-info',
                        'manager'        => 'dot-ok',
                        default          => '',
                    };
                    $roleLabel = match($user->role) {
                        'owner'   => 'Власник',
                        'admin'   => 'Адміністратор',
                        'manager' => 'Менеджер',
                        default   => 'Перегляд',
                    };
                @endphp
                <div wire:click="viewUser({{ $user->id }})"
                     class="team-grid team-row {{ $openUserId === $user->id ? 'team-row--active' : '' }} {{ $user->isSuspended() ? 'team-row--suspended' : '' }}">

                    {{-- Avatar --}}
                    <span class="team-avatar">
                        <span class="avatar" style="background:var(--ink-9); color:var(--paper);">
                            {{ $initials }}
                        </span>
                        @if ($isOnline)
                            <span class="online-dot"></span>
                        @endif
                    </span>

                    {{-- Name --}}
                    <span class="team-name">
                        {{ $user->name }}
                        @if ($user->isSuspended())
                            <span class="team-suspended-badge" style="margin-left:6px;">призупинено</span>
                        @endif
                    </span>

                    {{-- Email --}}
                    <span class="team-email">{{ $user->email }}</span>

                    {{-- Role --}}
                    <span class="team-role">
                        <span class="dot {{ $roleDot }}"></span>{{ $roleLabel }}
                    </span>

                    {{-- Last seen --}}
                    <span class="team-activity">
                        {{ $user->last_login_at?->diffForHumans() ?? 'Ніколи' }}
                    </span>

                    <span class="team-edit-icon">
                        <x-icon.edit width="14" height="14" />
                    </span>
                </div>
            @empty
                <div class="team-empty">Учасників не знайдено.</div>
            @endforelse
        </div>

        <div style="margin-top:16px;">{{ $users->links() }}</div>
    </div>

    {{-- ── User drawer ───────────────────────────────────────── --}}
    @if ($openUserId && $openUser)
        @php
            $u        = $openUser;
            $isOwner  = $u->role === 'owner';
            $isSelf   = $u->id === auth()->id();
            $canEdit  = auth()->user()->isAdmin() && !$isSelf;
            // Role / permissions / access are read-only for the owner and for yourself.
            $locked   = $isOwner || !$canEdit;
            $parts    = explode(' ', trim($u->name));
            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8')
                      . mb_strtoupper(mb_substr($parts[1] ?? $parts[0], 0, 1, 'UTF-8'), 'UTF-8');
            $isOnline = $u->last_login_at && $u->last_login_at->diffInMinutes() < 30;
            $subLine  = $u->email . ' · ' . ($u->role === 'owner' ? 'owner' : $pendingRole) . ($isOnline ? ' · online' : '');
        @endphp

        <x-ui.drawer
            wire:key="user-drawer-{{ $openUserId }}"
            :open="true"
            :title="$openUser->name"
            :sub="$subLine"
            @drawer-close.window="$wire.closeUser()"
        >
            {{-- ── Role selector ──────────────────────────────── --}}
            <div>
                <span class="label">Роль</span>
                <div class="role-cards">
                    @foreach ([
                        ['admin',   'Admin',   'Повний доступ'],
                        ['manager', 'Manager', 'Дані + сайти'],
                        ['viewer',  'Viewer',  'Тільки читання'],
                    ] as [$k, $l, $d])
                        <button
                            @if(! $locked) wire:click="selectRole('{{ $k }}')" @endif
                            class="role-card {{ $pendingRole === $k ? 'role-card--active' : '' }} {{ $isOwner ? 'role-card--owner' : '' }}"
                            @if($locked) disabled @endif>
                            <div class="role-card__name">{{ $l }}</div>
                            <div class="role-card__desc">{{ $d }}</div>
                        </button>
                    @endforeach
                </div>
                @if ($isOwner)
                    <p style="margin-top:8px; font:12px var(--font-mono); color:var(--ink-4);">
                        Роль власника незмінна.
                    </p>
                @elseif ($isSelf)
                    <p style="margin-top:8px; font:12px var(--font-mono); color:var(--ink-4);">
                        Ви не можете змінювати власну роль та права.
                    </p>
                @endif
            </div>

            {{-- ── Permissions matrix (editable) ──────────────── --}}
            <div style="margin-top:28px;">
                <div style="display:flex; align-items:baseline; justify-content:space-between;">
                    <span class="label">Деталізовані права</span>
                    @unless ($locked)
                        <span style="font:11px var(--font-mono); color:var(--ink-4);">натисніть, щоб змінити</span>
                    @endunless
                </div>
                <div class="perm-matrix">
                    {{-- Header row --}}
                    <div class="perm-grid perm-head">
                        <span class="eyebrow" style="font-size:9.5px;">Ресурс</span>
                        @foreach (['Read', 'Create', 'Edit', 'Delete'] as $a)
                            <span class="perm-col-label">{{ $a }}</span>
                        @endforeach
                    </div>

                    {{-- Data rows --}}
                    @foreach ($resources as $rkey => $rlabel)
                        <div class="perm-grid perm-row">
                            <span class="perm-resource">{{ $rlabel }}</span>
                            @foreach ($actions as $action)
                                @php
                                    $on = $matrix[$rkey][$action] ?? false;
                                @endphp
                                <div class="perm-cell">
                                    <button type="button"
                                        @if(! $locked) wire:click="togglePerm('{{ $rkey }}', '{{ $action }}')" @endif
                                        class="perm-box {{ $on ? 'perm-box--on' : 'perm-box--off' }} {{ $locked ? '' : 'perm-box--editable' }}"
                                        @if($locked) disabled @endif>
                                        @if ($on)
                                            <x-icon.check width="10" height="10" />
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Site access (granular) ─────────────────────── --}}
            <div style="margin-top:28px;">
                <span class="label">Доступ до сайтів</span>
                <div class="access-scope">
                    <button type="button" wire:click="setAccessScope('all')"
                        class="access-scope__btn {{ $accessScope === 'all' ? 'access-scope__btn--active' : '' }}"
                        {{ $locked ? 'disabled' : '' }}>
                        Усі сайти
                    </button>
                    <button type="button" wire:click="setAccessScope('limited')"
                        class="access-scope__btn {{ $accessScope === 'limited' ? 'access-scope__btn--active' : '' }}"
                        {{ $locked ? 'disabled' : '' }}>
                        Обмежений доступ
                    </button>
                </div>

                @if ($accessScope === 'limited' && ! $isOwner)
                    {{-- Groups --}}
                    <div style="margin-top:18px;">
                        <span class="access-sub-label">Групи сайтів</span>
                        @php
                            $visibleAccessGroups = $allGroups->take(2);
                            $overflowAccessGroups = $allGroups->slice(2);
                            $overflowAccessActive = $overflowAccessGroups->contains(fn ($g) => in_array($g->name, $groupAccess, true));
                        @endphp
                        <div class="access-pills">
                            @forelse ($visibleAccessGroups as $g)
                                @php
                                    $active = in_array($g->name, $groupAccess, true);
                                @endphp
                                <button type="button" wire:click="toggleGroup('{{ $g->name }}')"
                                    class="access-pill {{ $active ? 'access-pill--active' : '' }}"
                                    {{ $locked ? 'disabled' : '' }}>
                                    <span class="access-pill__dot" style="background:{{ $g->color }};"></span>
                                    {{ $g->name }}
                                    @if($active)<x-icon.check width="11" height="11" />@endif
                                </button>
                            @empty
                                <span style="font:12px var(--font-mono); color:var(--ink-4);">Немає груп.</span>
                            @endforelse
                            @if ($overflowAccessGroups->isNotEmpty())
                                <span class="access-more" x-data="{ open: false }" @click.outside="open = false">
                                    <button type="button"
                                            class="access-pill access-pill--more {{ $overflowAccessActive ? 'access-pill--active' : '' }}"
                                            @click="open = !open"
                                            @if($locked) disabled @endif>
                                        Ще {{ $overflowAccessGroups->count() }}
                                    </button>
                                    <span class="dropdown access-more__dropdown" x-show="open" x-cloak>
                                        @foreach ($overflowAccessGroups as $g)
                                            @php
                                                $active = in_array($g->name, $groupAccess, true);
                                            @endphp
                                            <button type="button"
                                                    class="pill-menu-item {{ $active ? 'is-active' : '' }}"
                                                    wire:click="toggleGroup('{{ $g->name }}')"
                                                    @click="open = false"
                                                    {{ $locked ? 'disabled' : '' }}>
                                                <span class="pill-dot" style="background:{{ $g->color }};"></span>
                                                {{ $g->name }}
                                                @if($active)<x-icon.check width="11" height="11" />@endif
                                            </button>
                                        @endforeach
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Individual sites --}}
                    <div style="margin-top:18px;">
                        <span class="access-sub-label">Окремі сайти</span>
                        <div class="access-site-list">
                            @forelse ($allSites as $s)
                                @php
                                    $inGroup = in_array($s->group, $groupAccess, true);
                                    $checked = in_array($s->id, $siteAccess, true);
                                @endphp
                                <button type="button" wire:click="toggleSite({{ $s->id }})"
                                    class="access-site {{ ($checked || $inGroup) ? 'access-site--on' : '' }}"
                                    {{ $locked ? 'disabled' : '' }}
                                    title="{{ $inGroup ? 'Доступ через групу «' . $s->group . '»' : '' }}">
                                    <span class="access-site__check">
                                        @if($checked || $inGroup)<x-icon.check width="11" height="11" />@endif
                                    </span>
                                    <span class="access-site__name">{{ $s->name }}</span>
                                    <span class="access-site__group mono">{{ $s->group }}</span>
                                </button>
                            @empty
                                <span style="font:12px var(--font-mono); color:var(--ink-4);">Немає сайтів.</span>
                            @endforelse
                        </div>
                        <p style="margin-top:8px; font:11px var(--font-mono); color:var(--ink-4);">
                            Сайти з обраних груп доступні автоматично.
                        </p>
                    </div>
                @endif
            </div>

            {{-- ── Change password ────────────────────────────── --}}
            @if (auth()->user()->isAdmin())
                <div class="team-danger">
                    <span class="team-danger__label">Дії</span>

                    <button wire:click="$set('changingPassword', {{ $changingPassword ? 'false' : 'true' }})"
                            class="team-action-btn">
                        <x-icon.lock width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                        <span>{{ $changingPassword ? 'Скасувати зміну пароля' : 'Змінити пароль' }}</span>
                    </button>

                    @if (!$isOwner)
                        <button wire:click="generateTemporaryPassword"
                                class="team-action-btn team-temp-reset-action">
                            <x-icon.refresh width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                            <span class="team-temp-reset-label">Тимчасовий пароль (адмін-доступ)</span>
                            <span>Згенерувати тимчасовий пароль</span>
                        </button>
                    @endif

                    @if ($generatedPassword !== '')
                        <div class="team-temp-pass">
                            <span class="team-temp-pass__label">Тимчасовий пароль · діє 48 год</span>
                            <span class="team-temp-pass__value mono">{{ $generatedPassword }}</span>
                            <button type="button"
                                    class="team-temp-pass__copy"
                                    x-data
                                    x-on:click="navigator.clipboard?.writeText(@js($generatedPassword))">
                                Копіювати
                            </button>
                            <span class="team-temp-pass__hint" style="flex-basis:100%; font:11.5px var(--font-sans); color:var(--ink-5); margin-top:4px;">
                                Не скидає основний пароль — користувач і далі входить своїм. Зміна пароля стирає тимчасовий.
                            </span>
                        </div>
                    @endif

                    @if ($changingPassword)
                        @if (false && $generatedPassword !== '')
                            <div class="team-temp-pass">
                                <span class="team-temp-pass__label">Тимчасовий пароль</span>
                                <span class="team-temp-pass__value mono">{{ $generatedPassword }}</span>
                            </div>
                        @endif
                        <div class="team-pass-fields">
                            <div>
                                <label class="label" for="new_pass">Новий пароль</label>
                                <input wire:model="newPassword" id="new_pass" type="password"
                                    class="input" placeholder="Мін. 8 символів" autocomplete="new-password" />
                                @error('newPassword')
                                    <p style="margin-top:5px; font:12px var(--font-mono); color:var(--bad);">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="label" for="confirm_pass">Підтвердити пароль</label>
                                <input wire:model="confirmPassword" id="confirm_pass" type="password"
                                    class="input" placeholder="Повторіть пароль" autocomplete="new-password" />
                                @error('confirmPassword')
                                    <p style="margin-top:5px; font:12px var(--font-mono); color:var(--bad);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (!$isSelf && !$isOwner)
                        @if ($u->isSuspended())
                            <button wire:click="toggleSuspend({{ $u->id }})"
                                    class="team-action-btn team-action-btn--warn">
                                <x-icon.unlock width="14" height="14" style="flex-shrink:0;" />
                                <span>Відновити доступ</span>
                            </button>
                        @else
                            <button wire:click="toggleSuspend({{ $u->id }})"
                                    class="team-action-btn team-action-btn--warn">
                                <x-icon.lock width="14" height="14" style="flex-shrink:0;" />
                                <span>Призупинити доступ</span>
                            </button>
                        @endif

                        @if (!$confirmingDelete)
                            <button wire:click="$set('confirmingDelete', true)"
                                    class="team-action-btn team-action-btn--danger">
                                <x-icon.trash width="14" height="14" style="flex-shrink:0;" />
                                <span>Видалити користувача</span>
                            </button>
                        @else
                            <div style="padding:12px 14px; border-radius:6px; background:var(--bad-soft); border:1px solid var(--bad-soft);">
                                <p style="font:13px var(--font-sans); color:var(--bad); margin-bottom:10px;">
                                    Видалити <strong>{{ $u->name }}</strong>? Цю дію не можна скасувати.
                                </p>
                                <div style="display:flex; gap:8px;">
                                    <button wire:click="$set('confirmingDelete', false)"
                                            class="btn btn-ghost btn-sm">Скасувати</button>
                                    <button wire:click="removeUser({{ $u->id }})"
                                            class="btn btn-danger-fill btn-sm">
                                        <x-icon.trash width="12" height="12" /> Видалити
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="closeUser">Скасувати</button>
                @if (auth()->user()->isAdmin() && !$isOwner)
                    <button class="btn btn-primary" wire:click="saveUser">Зберегти</button>
                @endif
            </x-slot:footer>
        </x-ui.drawer>
    @endif

    @livewire('users.invite-form')
</div>
