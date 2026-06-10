<div style="max-width:900px;">
    <div class="eyebrow" style="font-size:10px;margin-bottom:10px;">WP &middot; Плагін</div>
    <h3 style="font:400 28px/1.1 var(--font-sans);color:var(--ink-9);letter-spacing:-0.02em;margin-bottom:10px;">Dead-drop фід для сайту</h3>
    <p style="font:13.5px/1.55 var(--font-sans);color:var(--ink-5);max-width:600px;margin-bottom:24px;">
        Унікальний білд плагіна для цього сайту. Контакти доставляються зашифрованим конвертом
        через нейтральний статик-домен — плагін не лишає слідів центру і не збігається з білдами інших сайтів.
    </p>

    @if (! $plugin)
        {{-- Empty state --}}
        <div class="card" style="padding:48px;text-align:center;">
            <div style="font:14px var(--font-sans);color:var(--ink-9);margin-bottom:6px;">Сайт ще не підключений</div>
            <div style="font:12.5px var(--font-sans);color:var(--ink-5);margin-bottom:20px;">
                Підключення згенерує унікальну ідентичність білда та per-site ключ шифрування.
            </div>
            <button class="btn btn-primary btn-sm" wire:click="connect">+ Підключити сайт</button>
        </div>
    @else
        @php
            $statusLabel = match($plugin->status) {
                'enabled' => 'АКТИВНИЙ', 'paused' => 'ПАУЗА', default => 'ЧЕРНЕТКА',
            };
            $statusColor = match($plugin->status) {
                'enabled' => 'var(--ok)', 'paused' => 'var(--warn)', default => 'var(--ink-4)',
            };
        @endphp

        {{-- Статус-картка --}}
        <div class="card" style="display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
            <div style="padding:16px 20px;">
                <div class="eyebrow" style="font-size:9.5px;margin-bottom:8px;">Статус</div>
                <div style="font:400 16px var(--font-sans);color:var(--ink-9);display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:7px;height:7px;border-radius:999px;background:{{ $statusColor }};"></span>{{ $statusLabel }}
                </div>
                <div style="margin-top:4px;font:11.5px var(--font-mono);color:var(--ink-5);">v{{ $plugin->payload_version }}</div>
            </div>
            <div style="padding:16px 20px;border-left:1px solid var(--ink-3);">
                <div class="eyebrow" style="font-size:9.5px;margin-bottom:8px;">Білд</div>
                <div style="font:400 14px var(--font-mono);color:var(--ink-9);">{{ $plugin->slug }}</div>
                <div style="margin-top:4px;font:11.5px var(--font-mono);color:var(--ink-5);">{{ $plugin->prefix }}</div>
            </div>
            <div style="padding:16px 20px;border-left:1px solid var(--ink-3);">
                <div class="eyebrow" style="font-size:9.5px;margin-bottom:8px;">Остання публікація</div>
                <div style="font:400 14px var(--font-sans);color:var(--ink-9);">
                    {{ $publication?->published_at?->format('d.m.Y H:i') ?? '—' }}
                </div>
                <div style="margin-top:4px;font:11.5px var(--font-mono);color:var(--ink-5);">
                    {{ $publication ? $publication->entry_count.' записів · '.$publication->byte_size.' B' : 'ще не публікувався' }}
                </div>
            </div>
            <div style="padding:16px 20px;border-left:1px solid var(--ink-3);">
                <div class="eyebrow" style="font-size:9.5px;margin-bottom:8px;">Фід</div>
                <div style="font:400 12px var(--font-mono);color:var(--ink-9);word-break:break-all;">
                    &hellip;/{{ $plugin->feed_filename }}
                </div>
                <div style="margin-top:4px;font:11.5px var(--font-mono);color:var(--ink-5);">cron ~{{ intdiv($plugin->cron_interval, 60) }} хв</div>
            </div>
        </div>

        {{-- Дії --}}
        <div class="card" style="overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--ink-3);">
                <span class="eyebrow" style="font-size:10px;">Дії</span>
                <div style="display:flex;gap:6px;">
                    <button class="btn btn-ghost btn-sm" wire:click="downloadZip">&#8681; ZIP білда</button>
                    @if ($plugin->status === 'paused')
                        <button class="btn btn-primary btn-sm" wire:click="resume">&#9654; Відновити</button>
                    @else
                        <button class="btn btn-primary btn-sm" wire:click="publishNow">&#8635; Опублікувати</button>
                        <button class="btn btn-ghost btn-sm" wire:click="pause">&#10073;&#10073; Пауза</button>
                    @endif
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;">
                <div style="max-width:500px;">
                    <div style="font:14px var(--font-sans);color:var(--ink-9);margin-bottom:4px;">Ротація ключів та ідентичності</div>
                    <div style="font:12.5px var(--font-sans);color:var(--ink-5);">
                        Новий slug, префікси, шлях фіду і ключ шифрування. Старий артефакт зникне з dead-drop,
                        плагін на сайті треба перевстановити з нового ZIP вручну.
                    </div>
                </div>
                @if (! $confirmingRotate)
                    <button class="btn btn-ghost btn-sm" style="color:var(--bad);" wire:click="$set('confirmingRotate', true)">Ротувати</button>
                @else
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <button class="btn btn-primary btn-sm" style="background:var(--bad);border-color:var(--bad);" wire:click="rotateKeys">Так, ротувати</button>
                        <button class="btn btn-ghost btn-sm" wire:click="$set('confirmingRotate', false)">Скасувати</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
