<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ section: 'workspace' }">
    <x-ui.topbar :crumbs="['Налаштування']" />

    <x-ui.page-head
        title="Налаштування"
        sub="Workspace-параметри, довідники, інтеграції." />

    <div style="padding:0 40px 64px; display:grid; grid-template-columns:240px 1fr; gap:40px;">

        {{-- Left nav --}}
        <nav style="display:flex; flex-direction:column; gap:2px; align-self:flex-start; position:sticky; top:0;">
            @foreach ([
                ['k' => 'workspace',  'l' => 'Workspace',        'd' => 'Назва, регіон, логотип'],
                ['k' => 'countries',  'l' => 'Довідник країн',   'd' => 'PL, UA, DE, …'],
                ['k' => 'categories', 'l' => 'Категорії даних',  'd' => 'Phones, prices…'],
                ['k' => 'webhooks',   'l' => 'Webhooks',          'd' => '3 активних'],
                ['k' => 'api',        'l' => 'API ключі',          'd' => 'Tokens, scopes'],
                ['k' => 'profile',    'l' => 'Акаунт',             'd' => 'Профіль, пароль'],
            ] as $s)
                <button @click="section = '{{ $s['k'] }}'" style="
                    display:flex; align-items:flex-start; gap:10px;
                    padding:10px 12px; border-radius:4px; text-align:left; cursor:pointer;
                    background:transparent; border:1px solid transparent; transition:all .12s;"
                    :style="section === '{{ $s['k'] }}' ? 'background:var(--card); border-color:var(--ink-3); color:var(--ink-9);' : 'color:var(--ink-5);'">
                    <div style="flex:1;">
                        <div style="font:13.5px var(--font-sans);">{{ $s['l'] }}</div>
                        <div style="margin-top:3px; font:11px var(--font-mono); color:var(--ink-4);">{{ $s['d'] }}</div>
                    </div>
                    <x-icon.arrow width="12" height="12" x-show="section === '{{ $s['k'] }}'" style="margin-top:2px; color:var(--ink-4);" />
                </button>
            @endforeach
        </nav>

        {{-- Content area --}}
        <div>

            {{-- Workspace --}}
            <div x-show="section === 'workspace'">
                <header style="margin-bottom:24px;">
                    <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9);">Workspace</h2>
                    <p style="margin-top:8px; font:13.5px var(--font-sans); color:var(--ink-5);">Загальні параметри робочого простору.</p>
                </header>
                <div class="card" style="padding:24px; display:flex; flex-direction:column; gap:24px;">
                    <div>
                        <label class="label">Назва workspace</label>
                        <input class="input" value="{{ auth()->user()->organization_name ?? 'DataBridge' }}" />
                    </div>
                    <div>
                        <label class="label">Регіон зберігання</label>
                        <div style="display:flex; gap:8px; margin-top:12px;">
                            @foreach (['EU · Frankfurt', 'US · Virginia', 'AP · Singapore'] as $i => $r)
                                <button style="flex:1; padding:12px 14px; border-radius:4px; border:1px solid {{ $i === 0 ? 'var(--ink-9)' : 'var(--ink-3)' }}; background:{{ $i === 0 ? 'var(--ink-9)' : 'transparent' }}; color:{{ $i === 0 ? 'var(--paper)' : 'var(--ink-9)' }}; font:13px var(--font-sans); cursor:pointer;">
                                    {{ $r }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Countries --}}
            <div x-show="section === 'countries'">
                <header style="display:flex; align-items:flex-end; margin-bottom:24px;">
                    <div style="flex:1;">
                        <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9);">Довідник країн</h2>
                        <p style="margin-top:8px; font:13.5px var(--font-sans); color:var(--ink-5);">Використовується для гео-правил. ISO-2 + dial code.</p>
                    </div>
                    <button class="btn btn-primary btn-sm"><x-icon.plus width="13" height="13" /> Додати країну</button>
                </header>
                <div class="card" style="overflow:hidden;">
                    <div style="display:grid; grid-template-columns:60px 1fr 90px 110px 70px; gap:16px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                        @foreach (['', 'Назва', 'ISO-2', 'Dial code', ''] as $h)
                            <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                        @endforeach
                    </div>
                    @foreach ([
                        ['🇵🇱', 'Польща',           'PL', '+48'],
                        ['🇺🇦', 'Україна',           'UA', '+380'],
                        ['🇩🇪', 'Німеччина',         'DE', '+49'],
                        ['🇺🇸', 'США',               'US', '+1'],
                        ['🇬🇧', 'Велика Британія',   'GB', '+44'],
                        ['🇫🇷', 'Франція',           'FR', '+33'],
                    ] as $i => $c)
                        <div style="display:grid; grid-template-columns:60px 1fr 90px 110px 70px; gap:16px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center;">
                            <span style="font:22px var(--font-sans);">{{ $c[0] }}</span>
                            <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $c[1] }}</span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7); letter-spacing:.06em;">{{ $c[2] }}</span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7);">{{ $c[3] }}</span>
                            <div style="display:flex; justify-content:flex-end; gap:4px; color:var(--ink-4);">
                                <button style="width:24px; height:24px; cursor:pointer;"><x-icon.edit width="13" height="13" /></button>
                                <button style="width:24px; height:24px; cursor:pointer;"><x-icon.trash width="13" height="13" /></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Categories --}}
            <div x-show="section === 'categories'">
                <header style="margin-bottom:24px;">
                    <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9);">Категорії даних</h2>
                    <p style="margin-top:8px; font:13.5px var(--font-sans); color:var(--ink-5); max-width:540px;">
                        Які типи контактів і даних зберігає workspace.
                    </p>
                </header>
                <div style="margin-bottom:24px;">
                    <div class="eyebrow" style="margin-bottom:12px;">Основні · контактні</div>
                    <div class="card" style="overflow:hidden; border-left:2px solid var(--accent);">
                        @foreach ([['Телефони', 'обов\'язкове'], ['Месенджери', 'обов\'язкове']] as $i => $cat)
                            <div style="display:grid; grid-template-columns:1fr 80px 80px; gap:16px; padding:16px 20px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $cat[0] }}</span>
                                    <span class="pill" style="height:20px; font-size:10px; background:var(--accent-soft); color:var(--accent);">{{ $cat[1] }}</span>
                                </div>
                                <span></span>
                                <span style="color:var(--ink-4); display:flex; justify-content:flex-end;"><x-icon.lock width="14" height="14" /></span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom:12px;">Інші · додаткові</div>
                    <div class="card" style="overflow:hidden;">
                        @foreach (['Ціни', 'Адреси', 'Соціальні мережі', 'Custom поля'] as $i => $cat)
                            <div style="display:grid; grid-template-columns:1fr 80px; gap:16px; padding:16px 20px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                                <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $cat }}</span>
                                <div style="display:flex; justify-content:flex-end;">
                                    <span style="width:32px; height:18px; border-radius:999px; background:var(--ink-9); position:relative; display:inline-block; cursor:pointer;">
                                        <span style="position:absolute; top:2px; left:16px; width:14px; height:14px; border-radius:999px; background:var(--paper);"></span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Webhooks --}}
            <div x-show="section === 'webhooks'">
                <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9); margin-bottom:24px;">Webhooks</h2>
                <div class="card" style="overflow:hidden;">
                    @foreach (['phone.created', 'phone.failover', 'site.error'] as $i => $e)
                        <div style="display:grid; grid-template-columns:1fr auto auto; gap:12px; padding:14px 18px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                            <div>
                                <div class="mono" style="font:13px var(--font-mono); color:var(--ink-9);">{{ $e }}</div>
                                <div class="mono" style="margin-top:3px; font:11.5px var(--font-mono); color:var(--ink-5);">https://hooks.acme.com/db/{{ explode('.', $e)[0] }}</div>
                            </div>
                            <span style="width:32px; height:18px; border-radius:999px; background:var(--ink-9); position:relative; display:inline-block; cursor:pointer;">
                                <span style="position:absolute; top:2px; left:16px; width:14px; height:14px; border-radius:999px; background:var(--paper);"></span>
                            </span>
                            <button style="color:var(--ink-4); cursor:pointer;"><x-icon.trash width="13" height="13" /></button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- API keys --}}
            <div x-show="section === 'api'">
                <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9); margin-bottom:24px;">API ключі</h2>
                <div class="card" style="overflow:hidden;">
                    @foreach ([['Production server', 'db_prod_2K7sX9m...', '1 хв'], ['CI bot', 'db_ci_4M8sX2k...', 'вчора']] as $i => $t)
                        <div style="display:grid; grid-template-columns:1fr 1fr 100px auto; gap:12px; padding:16px 18px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                            <div>
                                <div style="font:13px var(--font-sans); color:var(--ink-9);">{{ $t[0] }}</div>
                                <div class="mono" style="margin-top:3px; font:12px var(--font-mono); color:var(--ink-5);">{{ $t[1] }}</div>
                            </div>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">Останнє: {{ $t[2] }}</span>
                            <button class="btn btn-secondary btn-sm">Копіювати</button>
                            <button class="btn btn-ghost btn-sm" style="color:var(--bad);">Відкликати</button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Profile / Account --}}
            <div x-show="section === 'profile'" x-data="{ tab: 'profile' }">
                <header style="margin-bottom:24px;">
                    <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9);">Акаунт</h2>
                </header>
                <div style="display:flex; gap:24px; margin-bottom:32px; border-bottom:1px solid var(--ink-3);">
                    @foreach ([['profile', 'Профіль'], ['password', 'Пароль']] as $t)
                        <button @click="tab = '{{ $t[0] }}'" style="padding:14px 0; border-bottom:2px solid transparent; font:14px var(--font-sans); color:var(--ink-5); cursor:pointer; background:transparent;"
                            :style="tab === '{{ $t[0] }}' ? 'border-bottom-color:var(--ink-9); color:var(--ink-9); font-weight:500;' : ''">
                            {{ $t[1] }}
                        </button>
                    @endforeach
                </div>

                <div x-show="tab === 'profile'" style="max-width:520px;">
                    <form wire:submit="saveProfile">
                        @if (session('profile-saved'))
                            <div style="margin-bottom:16px; padding:12px 16px; background:var(--ok-soft); color:var(--ok); border-radius:4px; font:13.5px var(--font-sans);">Профіль збережено.</div>
                        @endif
                        <div style="display:flex; flex-direction:column; gap:24px;">
                            <x-ui.input wire:model="name" label="Імʼя" name="settings_name" :error="$errors->first('name')" />
                            <x-ui.input wire:model="email" label="Email" name="settings_email" type="email" :error="$errors->first('email')" />
                            <div><x-ui.button type="submit">Зберегти</x-ui.button></div>
                        </div>
                    </form>
                </div>

                <div x-show="tab === 'password'" style="max-width:520px;">
                    <form wire:submit="changePassword">
                        @if (session('password-changed'))
                            <div style="margin-bottom:16px; padding:12px 16px; background:var(--ok-soft); color:var(--ok); border-radius:4px; font:13.5px var(--font-sans);">Пароль змінено.</div>
                        @endif
                        <div style="display:flex; flex-direction:column; gap:24px;">
                            <x-ui.input wire:model="current_password" label="Поточний пароль" name="current_password" type="password" :error="$errors->first('current_password')" />
                            <x-ui.input wire:model="new_password" label="Новий пароль" name="new_password" type="password" :error="$errors->first('new_password')" />
                            <x-ui.input wire:model="new_password_confirmation" label="Підтвердити пароль" name="new_password_confirmation" type="password" />
                            <div><x-ui.button type="submit">Змінити пароль</x-ui.button></div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
