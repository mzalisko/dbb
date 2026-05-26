/* DataBridge · Design System reference */

const System = () => (
  <div style={{ width: "100%", minHeight: "100%", padding: "56px 64px 80px", overflowY: "auto", background: "var(--paper)" }}>
    <header style={{ marginBottom: 56 }}>
      <div className="eyebrow">Design System · v1</div>
      <p style={{ marginTop: 16, font: "15px/1.6 var(--font-sans)", color: "var(--ink-5)", maxWidth: 580 }}>
        Тиха операційна панель. Теплий папір, чорнило, один теракотовий акцент.
        Гайрлайни замість бордерів. Крапки замість пілюль. Один шрифт, моно для даних.
      </p>
    </header>

    {/* Colors */}
    <Block n="01" t="Колір">
      <Sub>Папір та чорнило</Sub>
      {[
        { tk: "paper",   use: "Тло сторінки" },
        { tk: "paper-2", use: "Сайдбар, секції" },
        { tk: "card",    use: "Картки, поверхні" },
        { tk: "ink-9",   use: "Заголовки" },
        { tk: "ink-7",   use: "Основний текст" },
        { tk: "ink-5",   use: "Вторинний текст" },
        { tk: "ink-3",   use: "Гайрлайни" },
        { tk: "ink-2",   use: "Hover / fill" },
      ].map(c => <Swatch key={c.tk} {...c}/>)}

      <Sub style={{ marginTop: 32 }}>Акцент</Sub>
      <Swatch tk="accent" use="Теракота · 1× на екран"/>
      <Swatch tk="accent-soft" use="Активні стани, підказки"/>

      <Sub style={{ marginTop: 32 }}>Семантика — крапки</Sub>
      {[
        { d: "dot-ok",   tk: "ok",   l: "Активний, успіх, online" },
        { d: "dot-info", tk: "info", l: "Резерв, інформаційне" },
        { d: "dot-warn", tk: "warn", l: "Пауза, попередження" },
        { d: "dot-bad",  tk: "bad",  l: "Заблоковано, помилка" },
      ].map(s => (
        <div key={s.tk} style={{ display: "grid", gridTemplateColumns: "32px 140px 1fr", gap: 24, alignItems: "center", padding: "12px 0", borderTop: "1px solid var(--ink-3)" }}>
          <span className={"dot " + s.d} style={{ marginRight: 0 }}/>
          <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-5)" }}>--{s.tk}</span>
          <span style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{s.l}</span>
        </div>
      ))}
      <End/>
    </Block>

    {/* Type */}
    <Block n="02" t="Типографія">
      {[
        { n: "H1",       v: "48 / 400",      s: { font: "400 48px/1 var(--font-sans)", letterSpacing: "-0.035em" }, ex: "Loft Quiet" },
        { n: "H2 page",  v: "36 / 400",      s: { font: "400 36px/1.05 var(--font-sans)", letterSpacing: "-0.030em" }, ex: "Сайти · 14" },
        { n: "H3",       v: "22 / 400",      s: { font: "400 22px/1.2 var(--font-sans)", letterSpacing: "-0.020em" }, ex: "Що бачать відвідувачі" },
        { n: "Body",     v: "14 / 400",      s: { font: "14px/1.55 var(--font-sans)", color: "var(--ink-7)" }, ex: "Параграф для опису." },
        { n: "Mono",     v: "Geist Mono 14", s: { font: "14px var(--font-mono)", color: "var(--ink-9)" }, ex: "+48 22 555 33 11" },
        { n: "Eyebrow",  v: "11 mono UPPER", s: { font: "400 11px/1 var(--font-mono)", letterSpacing: "0.18em", textTransform: "uppercase", color: "var(--ink-5)" }, ex: "ЩО БАЧАТЬ ВІДВІДУВАЧІ" },
      ].map((t, i) => (
        <div key={t.n} style={{ display: "grid", gridTemplateColumns: "200px 1fr", gap: 32, padding: "18px 0", borderTop: i ? "1px solid var(--ink-3)" : "none" }}>
          <div>
            <div style={{ font: "13.5px var(--font-sans)", color: "var(--ink-9)" }}>{t.n}</div>
            <div className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-5)", marginTop: 4 }}>{t.v}</div>
          </div>
          <div style={t.s}>{t.ex}</div>
        </div>
      ))}
      <End/>
    </Block>

    {/* Buttons */}
    <Block n="03" t="Кнопки">
      <Row label="Варіанти">
        <button className="btn btn-primary">Primary</button>
        <button className="btn btn-secondary">Secondary</button>
        <button className="btn btn-ghost">Ghost</button>
        <button className="btn btn-accent">Accent</button>
        <button className="btn btn-danger">Danger</button>
      </Row>
      <Row label="Розміри">
        <button className="btn btn-primary btn-lg">Large 44</button>
        <button className="btn btn-primary">Default 36</button>
        <button className="btn btn-primary btn-sm">Small 30</button>
      </Row>
      <Row label="З іконкою">
        <button className="btn btn-primary"><I.Plus size={13}/> Додати сайт</button>
        <button className="btn btn-secondary"><I.Refresh size={13}/> Sync</button>
        <button className="btn btn-secondary">Відкрити <I.Arrow size={13}/></button>
      </Row>
    </Block>

    {/* Inputs */}
    <Block n="04" t="Форми">
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 56 }}>
        <div style={{ display: "flex", flexDirection: "column", gap: 32 }}>
          <div>
            <label className="label">Номер</label>
            <input className="input mono" defaultValue="+48 22 555 33 11" style={{ font: "400 22px var(--font-mono)" }}/>
          </div>
          <div>
            <label className="label">Email</label>
            <input className="input" defaultValue="you@databridge.app"/>
          </div>
        </div>
        <div>
          <label className="label">Радіо</label>
          <div style={{ display: "flex", flexDirection: "column", gap: 10, marginTop: 12 }}>
            {[{ l: "Головний", sel: true }, { l: "Резерв" }, { l: "Сховано" }].map(r => (
              <label key={r.l} style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer" }}>
                <span style={{ width: 14, height: 14, borderRadius: 999, border: "1px solid " + (r.sel ? "var(--ink-9)" : "var(--ink-3)"), display: "inline-flex", alignItems: "center", justifyContent: "center" }}>
                  {r.sel && <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--ink-9)" }}/>}
                </span>
                <span style={{ font: "14px var(--font-sans)", color: r.sel ? "var(--ink-9)" : "var(--ink-7)" }}>{r.l}</span>
              </label>
            ))}
          </div>
        </div>
      </div>
    </Block>

    {/* Tabs */}
    <Block n="05" t="Вкладки">
      <div className="tabs">
        <div className="tab active">Огляд <span className="tab-n">12</span></div>
        <div className="tab">Дані <span className="tab-n">38</span></div>
        <div className="tab">Активність</div>
        <div className="tab">Налаштування</div>
      </div>
    </Block>

    {/* Pills + dots */}
    <Block n="06" t="Статуси">
      <Row label="Pills">
        <span className="pill pill-ok"><span className="dot dot-ok" style={{ margin: 0 }}/>Активний</span>
        <span className="pill pill-warn"><span className="dot dot-warn" style={{ margin: 0 }}/>Пауза</span>
        <span className="pill pill-bad"><span className="dot dot-bad" style={{ margin: 0 }}/>Помилка</span>
        <span className="pill pill-info"><span className="dot dot-info" style={{ margin: 0 }}/>Резерв</span>
      </Row>
      <Row label="Інлайн">
        <span><span className="dot dot-ok"/>Головний</span>
        <span><span className="dot dot-info"/>Резерв</span>
        <span><span className="dot dot-warn"/>Пауза</span>
        <span><span className="dot dot-bad"/>Заблокований</span>
        <span><span className="dot"/>Сховано</span>
      </Row>
    </Block>
  </div>
);

const Block = ({ n, t, children }) => (
  <section style={{ marginBottom: 64 }}>
    <header style={{ marginBottom: 24, display: "flex", alignItems: "baseline", gap: 20 }}>
      <span style={{ font: "400 11px/1 var(--font-mono)", letterSpacing: "0.18em", color: "var(--ink-5)" }}>{n}</span>
      <h2 style={{ font: "400 28px/1 var(--font-sans)", letterSpacing: "-0.025em", color: "var(--ink-9)" }}>{t}</h2>
      <div style={{ flex: 1, height: 1, background: "var(--ink-3)" }}/>
    </header>
    {children}
  </section>
);
const Sub = ({ children, style }) => <div className="eyebrow" style={{ marginTop: 8, ...style }}>{children}</div>;
const Row = ({ label, children }) => (
  <div style={{ display: "grid", gridTemplateColumns: "120px 1fr", gap: 32, padding: "20px 0", borderTop: "1px solid var(--ink-3)", alignItems: "center" }}>
    <span style={{ font: "11px var(--font-mono)", letterSpacing: "0.16em", textTransform: "uppercase", color: "var(--ink-5)" }}>{label}</span>
    <div style={{ display: "flex", gap: 14, flexWrap: "wrap", alignItems: "center", font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{children}</div>
  </div>
);
const End = () => <div style={{ height: 1, background: "var(--ink-3)" }}/>;
const Swatch = ({ tk, use }) => (
  <div style={{ display: "grid", gridTemplateColumns: "44px 200px 1fr", gap: 24, alignItems: "center", padding: "10px 0", borderTop: "1px solid var(--ink-3)" }}>
    <span style={{ width: 32, height: 32, borderRadius: 4, background: `var(--${tk})`, border: "1px solid var(--ink-3)" }}/>
    <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>--{tk}</span>
    <span style={{ font: "13px var(--font-sans)", color: "var(--ink-7)" }}>{use}</span>
  </div>
);

window.System = System;
