{{-- ─── Phone entry form (shared by edit + add modes) ─── --}}
<div class="drawer-stack">

    {{-- НОМЕР --}}
    <div>
        <label class="label">Номер</label>
        <input class="input mono phone-input" wire:model="entryValue" placeholder="+48 ...">
        @error('entryValue') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- МІТКА --}}
    <div>
        <label class="label">Мітка</label>
        <input class="input" wire:model="entryLabel" placeholder="напр. PL головний">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ГЕО-ПРАВИЛО --}}
    <div>
        <label class="label">Гео-правило</label>
        <p class="field-hint">Сайт показує номер лише тим відвідувачам, що відповідають правилу.</p>
        <div class="geo-opts">
            @foreach([
                ['k'=>'all',    'l'=>'Усім',             'd'=>'Будь-яка країна'],
                ['k'=>'only',   'l'=>'Тільки в обраних', 'd'=>'Лише вказаним країнам'],
                ['k'=>'except', 'l'=>'Крім обраних',     'd'=>'Усім окрім вказаних'],
            ] as $geo)
                <label class="geo-opt {{ $entryGeoMode===$geo['k'] ? 'is-active' : '' }}" wire:click="$set('entryGeoMode','{{ $geo['k'] }}')">
                    <span class="geo-opt__radio">
                        @if($entryGeoMode===$geo['k'])<span class="geo-opt__dot"></span>@endif
                    </span>
                    <div>
                        <div class="geo-opt__label">{{ $geo['l'] }}</div>
                        <div class="geo-opt__desc">{{ $geo['d'] }}</div>
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Country pills — show when only/except --}}
        @if(in_array($entryGeoMode, ['only', 'except']))
            <div class="country-section">
                <div class="eyebrow eyebrow-xxs">Країни</div>
                <div class="country-pills">
                    @foreach(['PL'=>'PL','UA'=>'UA','DE'=>'DE','US'=>'US','GB'=>'GB','FR'=>'FR'] as $code=>$label)
                        @php $sel = in_array($code, $entryCountries ?? []); @endphp
                        <button type="button"
                            class="country-pill {{ $sel ? 'is-active' : '' }}"
                            wire:click="toggleCountry('{{ $code }}')">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- РОЛЬ --}}
    <div>
        <label class="label">Роль</label>
        <div class="role-cards">
            @foreach([
                ['k'=>'primary', 'l'=>'Головний', 'd'=>'Показуємо першим'],
                ['k'=>'backup',  'l'=>'Резерв',   'd'=>'На випадок блокування головного'],
                ['k'=>'hidden',  'l'=>'Сховано',  'd'=>'У базі, але не показуємо'],
            ] as $role)
                <label class="role-card {{ $entryRole===$role['k'] ? 'is-active' : '' }}" wire:click="$set('entryRole','{{ $role['k'] }}')">
                    <span class="role-card__radio">
                        @if($entryRole===$role['k'])<span class="role-card__dot"></span>@endif
                    </span>
                    <div>
                        <div class="role-card__label">{{ $role['l'] }}</div>
                        <div class="role-card__desc">{{ $role['d'] }}</div>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

</div>
