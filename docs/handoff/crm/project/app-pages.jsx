/* DataBridge · Other pages — Groups, Data, Team, Logs, Settings */

const GroupsPage = () => (
  <div style={{ flex: 1, overflowY: "auto" }}>
    <Topbar crumbs={["Групи сайтів"]} actions={
      <button className="btn btn-primary btn-sm"><I.Plus size={13}/> Нова група</button>
    }/>
    <PageHead title="Групи сайтів" sub="Об'єднайте сайти за середовищем, регіоном чи командою."/>
    <div style={{ padding: "0 40px 64px", display: "grid", gridTemplateColumns: "repeat(2, 1fr)", gap: 14 }}>
      {GROUPS.map(g => {
        const sites = SITES.filter(s => s.group === g.id).slice(0, 3);
        return (
          <article key={g.id} className="card" style={{ padding: 22, position: "relative", overflow: "hidden", cursor: "pointer", transition: "border-color .15s" }}
            onMouseEnter={e => e.currentTarget.style.borderColor = "var(--ink-9)"}
            onMouseLeave={e => e.currentTarget.style.borderColor = "var(--ink-3)"}>
            <div style={{ position: "absolute", top: 0, left: 0, bottom: 0, width: 3, background: g.color }}/>
            <header style={{ display: "flex", alignItems: "flex-start", gap: 12 }}>
              <div style={{ flex: 1 }}>
                <div style={{ display: "flex", alignItems: "baseline", gap: 12 }}>
                  <h3 style={{ font: "400 22px/1 var(--font-sans)", color: "var(--ink-9)", letterSpacing: "-0.02em" }}>{g.name}</h3>
                  <span className="mono num" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)", letterSpacing: "0.06em", textTransform: "uppercase" }}>{g.sites} сайтів</span>
                </div>
              </div>
              <button style={{ color: "var(--ink-4)", padding: 4 }}><I.MoreV size={14}/></button>
            </header>

            {/* Site preview list */}
            <div style={{ marginTop: 18, display: "flex", flexDirection: "column", gap: 4 }}>
              {sites.map(s => (
                <div key={s.id} style={{ display: "flex", alignItems: "center", gap: 10, padding: "6px 0" }}>
                  <span className={"dot " + (s.status === "ok" ? "dot-ok" : s.status === "pause" ? "dot-warn" : "dot-bad")} style={{ margin: 0 }}/>
                  <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{s.name}</span>
                  <div style={{ flex: 1 }}/>
                  <span style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{s.lastSync}</span>
                </div>
              ))}
              {g.sites > sites.length && (
                <div style={{ font: "12px var(--font-sans)", color: "var(--ink-4)", padding: "6px 0" }}>+ {g.sites - sites.length} ще</div>
              )}
            </div>

            <footer style={{ marginTop: 18, paddingTop: 14, borderTop: "1px solid var(--ink-3)", display: "flex", alignItems: "center", gap: 14, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>
              <span><span className="dot dot-ok"/>{sites.filter(s=>s.status==="ok").length} активні</span>
              {sites.filter(s=>s.status==="error").length > 0 && <span><span className="dot dot-bad"/>{sites.filter(s=>s.status==="error").length} помилка</span>}
              <div style={{ flex: 1 }}/>
              <span style={{ color: "var(--ink-9)" }}>Відкрити <I.Arrow size={11} style={{ display: "inline", verticalAlign: -1 }}/></span>
            </footer>
          </article>
        );
      })}
      <button style={{
        padding: 22, borderRadius: 4,
        border: "1px dashed var(--ink-3)",
        background: "transparent",
        display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center",
        gap: 10, color: "var(--ink-5)", minHeight: 200, cursor: "pointer",
        transition: "all .15s",
      }}
        onMouseEnter={e => { e.currentTarget.style.borderColor = "var(--ink-9)"; e.currentTarget.style.color = "var(--ink-9)"; }}
        onMouseLeave={e => { e.currentTarget.style.borderColor = "var(--ink-3)"; e.currentTarget.style.color = "var(--ink-5)"; }}>
        <I.Plus size={20}/>
        <span style={{ font: "13.5px var(--font-sans)" }}>Нова група</span>
      </button>
    </div>
  </div>
);

const DataPage = () => {
  const [selected, setSelected] = React.useState(new Set([1, 4]));
  const toggle = id => setSelected(s => {
    const n = new Set(s);
    if (n.has(id)) n.delete(id); else n.add(id);
    return n;
  });

  // Generate cross-site data
  const allRows = [];
  SITES.slice(0, 4).forEach(site => {
    [
      { n: "+48 22 555 33 11",  lbl: "Універсальний резерв", geo: "Усім", role: "backup" },
      { n: "11111111111",        lbl: "Головний (UA + Світ)",  geo: "Крім PL", role: "primary" },
      { n: "@demo_main",         lbl: "Telegram primary",       geo: "Усім", role: "primary" },
    ].forEach((r, i) => allRows.push({ ...r, id: `${site.id}-${i}`, site: site.name, group: site.group }));
  });

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column" }}>
      <Topbar crumbs={["Браузер даних"]} actions={
        <>
          <button className="btn btn-secondary btn-sm"><I.Export size={13}/> Експорт</button>
          <button className="btn btn-primary btn-sm">Bulk операції</button>
        </>
      }/>
      <PageHead title="Браузер даних" sub="Усі контактні дані з усіх сайтів. Шукайте, фільтруйте, виконуйте групові операції."/>

      <div style={{ padding: "0 40px" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10, height: 44, padding: "0 18px", borderRadius: 999, background: "var(--card)", border: "1px solid var(--ink-3)", maxWidth: 600 }}>
          <I.Search size={15} style={{ color: "var(--ink-5)" }}/>
          <span style={{ flex: 1, font: "14.5px var(--font-sans)", color: "var(--ink-4)" }}>Пошук по 284 записах…</span>
          <span style={{ font: "11px var(--font-mono)", padding: "3px 8px", borderRadius: 5, background: "var(--ink-2)", color: "var(--ink-5)" }}>⌘K</span>
        </div>
      </div>

      {/* Sticky bulk action bar */}
      {selected.size > 0 && (
        <div style={{ position: "sticky", top: 0, zIndex: 5, marginTop: 16, padding: "12px 40px", background: "var(--ink-9)", color: "var(--paper)", display: "flex", alignItems: "center", gap: 14 }}>
          <span className="mono" style={{ font: "500 13px var(--font-mono)" }}>{selected.size} обрано</span>
          <span style={{ height: 14, width: 1, background: "rgba(255,255,255,0.2)" }}/>
          <button style={{ font: "13px var(--font-sans)", color: "var(--paper)" }}>Видимість</button>
          <button style={{ font: "13px var(--font-sans)", color: "var(--paper)" }}>Змінити гео</button>
          <button style={{ font: "13px var(--font-sans)", color: "var(--paper)" }}>Копіювати в…</button>
          <button style={{ font: "13px var(--font-sans)", color: "var(--paper)" }}>Видалити</button>
          <div style={{ flex: 1 }}/>
          <button onClick={() => setSelected(new Set())} style={{ font: "13px var(--font-sans)", color: "rgba(250,249,246,0.7)" }}>Зняти виділення</button>
        </div>
      )}

      <div style={{ padding: "20px 40px 64px", flex: 1, overflowY: "auto" }}>
        <div className="card" style={{ overflow: "hidden" }}>
          <div style={{ display: "grid", gridTemplateColumns: "32px 1.4fr 1.6fr 1fr 100px 80px", gap: 12, padding: "12px 18px", borderBottom: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
            {["_", "Значення", "Сайт", "Мітка", "Гео", "Роль"].map(h => (
              <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>
          {allRows.map((r, i) => {
            const sel = selected.has(r.id);
            return (
              <div key={r.id} onClick={() => toggle(r.id)} style={{
                display: "grid", gridTemplateColumns: "32px 1.4fr 1.6fr 1fr 100px 80px",
                gap: 12, padding: "14px 18px", borderTop: i ? "1px solid var(--ink-3)" : "none",
                background: sel ? "var(--accent-soft)" : "transparent",
                alignItems: "center", cursor: "pointer",
              }}>
                <span style={{
                  width: 16, height: 16, borderRadius: 3,
                  background: sel ? "var(--ink-9)" : "transparent",
                  border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"),
                  display: "inline-flex", alignItems: "center", justifyContent: "center", color: "var(--paper)",
                }}>{sel && <I.Check size={11} stroke={2.5}/>}</span>
                <span className="mono" style={{ font: "13.5px var(--font-mono)", color: "var(--ink-9)" }}>{r.n}</span>
                <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{r.site}</span>
                <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-7)" }}>{r.lbl}</span>
                <span style={{ font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{r.geo}</span>
                <span style={{ font: "12.5px var(--font-sans)" }}>
                  {r.role === "primary" && <><span className="dot dot-ok"/>Головний</>}
                  {r.role === "backup"  && <><span className="dot dot-info"/>Резерв</>}
                </span>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

const TeamPage = () => {
  const [openId, setOpenId] = React.useState(null);
  const open = TEAM.find(m => m.id === openId);
  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", position: "relative" }}>
      <Topbar crumbs={["Команда"]} actions={
        <button className="btn btn-primary btn-sm"><I.Plus size={13}/> Запросити</button>
      }/>
      <PageHead title="Команда" sub="Натисніть на учасника щоб переглянути права."/>
      <div style={{ flex: 1, overflowY: "auto", padding: "0 40px 64px" }}>
        <div className="card" style={{ overflow: "hidden" }}>
          <div style={{ display: "grid", gridTemplateColumns: "44px 1.5fr 1fr 100px 1fr 80px", gap: 16, padding: "12px 18px", borderBottom: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
            {["_", "Учасник", "Email", "Роль", "Активність", "_"].map((h, i) => (
              <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>
          {TEAM.map((m, i) => {
            const sel = m.id === openId;
            return (
              <div key={m.id} onClick={() => setOpenId(m.id)} style={{
                display: "grid", gridTemplateColumns: "44px 1.5fr 1fr 100px 1fr 80px", gap: 16,
                padding: "16px 18px", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center",
                background: sel ? "var(--paper-2)" : "transparent",
                borderLeft: sel ? "2px solid var(--ink-9)" : "2px solid transparent",
                paddingLeft: sel ? 16 : 18,
                cursor: "pointer",
              }}>
                <span style={{ position: "relative" }}>
                  <span className="avatar" style={{ background: "var(--ink-9)", color: "var(--paper)" }}>
                    {m.name.split(" ").map(x => x[0]).join("")}
                  </span>
                  {m.online && <span style={{ position: "absolute", right: -1, bottom: -1, width: 8, height: 8, borderRadius: 999, background: "var(--ok)", border: "2px solid var(--card)" }}/>}
                </span>
                <span style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{m.name}</span>
                <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-5)" }}>{m.email}</span>
                <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-7)" }}>
                  {m.role === "admin"   && <><span className="dot dot-info"/>Admin</>}
                  {m.role === "manager" && <><span className="dot dot-ok"/>Manager</>}
                  {m.role === "viewer"  && <><span className="dot"/>Viewer</>}
                </span>
                <span className="mono" style={{ font: "12px var(--font-mono)", color: m.online ? "var(--ok)" : "var(--ink-5)" }}>{m.last}</span>
                <I.Arrow size={14} style={{ color: "var(--ink-4)", justifySelf: "end" }}/>
              </div>
            );
          })}
        </div>
      </div>

      {open && (
        <Drawer
          title={open.name}
          sub={<>{open.email} · {open.role} · {open.online ? "online" : "offline"}</>}
          onClose={() => setOpenId(null)}
          footer={<>
            <button className="btn btn-ghost" onClick={() => setOpenId(null)}>Скасувати</button>
            <button className="btn btn-primary" onClick={() => setOpenId(null)}>Зберегти</button>
          </>}
        >
          <div>
            <label className="label">Роль</label>
            <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
              {[
                { k: "admin",   l: "Admin",   d: "Повний доступ" },
                { k: "manager", l: "Manager", d: "Дані + сайти" },
                { k: "viewer",  l: "Viewer",  d: "Тільки читання" },
              ].map(r => {
                const sel = open.role === r.k;
                return (
                  <button key={r.k} style={{
                    flex: 1, padding: "12px 14px", borderRadius: 4,
                    border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"),
                    background: sel ? "var(--ink-9)" : "transparent",
                    color: sel ? "var(--paper)" : "var(--ink-9)",
                    textAlign: "left", cursor: "pointer",
                  }}>
                    <div style={{ font: "14px var(--font-sans)" }}>{r.l}</div>
                    <div style={{ marginTop: 4, font: "11.5px var(--font-mono)", opacity: 0.7 }}>{r.d}</div>
                  </button>
                );
              })}
            </div>
          </div>

          <div style={{ marginTop: 32 }}>
            <label className="label">Деталізовані права</label>
            <div style={{ marginTop: 14 }}>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 60px 60px 60px 60px", gap: 8, padding: "10px 0", borderBottom: "1px solid var(--ink-3)" }}>
                <span style={{ font: "11.5px var(--font-mono)", color: "var(--ink-5)", textTransform: "uppercase", letterSpacing: "0.04em" }}>Ресурс</span>
                {["Read", "Create", "Edit", "Delete"].map(a => (
                  <span key={a} style={{ font: "11.5px var(--font-mono)", color: "var(--ink-5)", textTransform: "uppercase", letterSpacing: "0.04em", textAlign: "center" }}>{a}</span>
                ))}
              </div>
              {[
                { r: "Sites",      perms: [true, true, true, true] },
                { r: "Phones",     perms: [true, true, true, true] },
                { r: "Site groups", perms: [true, true, true, false] },
                { r: "Team",        perms: [true, true, true, true] },
                { r: "API keys",   perms: [true, true, false, true] },
              ].map((p, i) => (
                <div key={p.r} style={{ display: "grid", gridTemplateColumns: "1fr 60px 60px 60px 60px", gap: 8, padding: "12px 0", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center" }}>
                  <span style={{ font: "13.5px var(--font-sans)", color: "var(--ink-9)" }}>{p.r}</span>
                  {p.perms.map((on, k) => (
                    <div key={k} style={{ display: "flex", justifyContent: "center" }}>
                      <span style={{
                        width: 16, height: 16, borderRadius: 3,
                        background: on ? "var(--ink-9)" : "transparent",
                        border: "1px solid " + (on ? "var(--ink-9)" : "var(--ink-3)"),
                        display: "inline-flex", alignItems: "center", justifyContent: "center", color: "var(--paper)",
                      }}>{on && <I.Check size={10} stroke={2.5}/>}</span>
                    </div>
                  ))}
                </div>
              ))}
            </div>
          </div>
        </Drawer>
      )}
    </div>
  );
};

const LogsPage = () => {
  const [tab, setTab] = React.useState("system"); // system | data | sites
  const [selectedSite, setSelectedSite] = React.useState(null);

  const systemLogs = [
    { t: "18:54:23", scope: "auth",     action: "login успіх",        actor: "Test Admin", status: "ok",    ip: "194.30.122.18" },
    { t: "18:42:11", scope: "failover", action: "auto-failover тригер", actor: "System",   status: "warn",  ip: "—" },
    { t: "18:30:08", scope: "api",      action: "API token created",   actor: "Test Admin", status: "ok",    ip: "194.30.122.18" },
    { t: "18:15:42", scope: "auth",     action: "login fail · 3rd",    actor: "lila@db.app", status: "error", ip: "62.181.10.4" },
    { t: "17:54:33", scope: "backup",   action: "Snapshot saved",      actor: "System",   status: "ok",    ip: "—" },
    { t: "17:48:00", scope: "webhook",  action: "Webhook delivered",   actor: "System",   status: "ok",    ip: "—" },
    { t: "17:30:00", scope: "auth",     action: "logout",              actor: "Olha Boyko", status: "ok",    ip: "94.158.61.4" },
  ];

  const dataLogs = [
    { t: "18:54:23", site: "demo-site.example", action: "phone updated",          who: "Test Admin", target: "+48 22 555 33 11", status: "ok" },
    { t: "18:42:11", site: "demo-site.example", action: "failover triggered",     who: "System",      target: "+48 00 000 00 00 → +48 99 999 99 99", status: "warn" },
    { t: "18:30:08", site: "nordwave.com",       action: "phone created",          who: "Test Admin", target: "+49 30 555 12 34", status: "ok" },
    { t: "18:15:42", site: "voltway.pro",         action: "messenger updated",       who: "System",      target: "@voltway_support", status: "ok" },
    { t: "18:02:19", site: "lumen-io.com",        action: "geo rule changed",        who: "Olha Boyko", target: "@lumen_main · all → only US,GB", status: "ok" },
    { t: "17:54:33", site: "kestrel.so",          action: "phone deleted",           who: "Іван Петренко", target: "+49 89 111 22 33", status: "ok" },
    { t: "17:48:00", site: "apex-shop.com",       action: "bulk import",             who: "Test Admin", target: "+8 phones · CSV", status: "ok" },
  ];

  const renderSystemTable = () => (
    <div className="card" style={{ overflow: "hidden" }}>
      <div style={{ display: "grid", gridTemplateColumns: "32px 120px 120px 1.4fr 1fr 130px 90px", gap: 12, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
        {["_", "Час", "Категорія", "Дія", "Користувач", "IP", "Статус"].map(h => (
          <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
        ))}
      </div>
      {systemLogs.map((r, i) => (
        <div key={i} style={{ display: "grid", gridTemplateColumns: "32px 120px 120px 1.4fr 1fr 130px 90px", gap: 12, padding: "14px 18px", borderTop: "1px solid var(--ink-3)", alignItems: "center" }}>
          <span className={"dot " + (r.status === "ok" ? "dot-ok" : r.status === "warn" ? "dot-warn" : "dot-bad")}/>
          <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{r.t}</span>
          <span style={{ font: "11.5px var(--font-mono)", color: "var(--ink-5)", letterSpacing: "0.06em", textTransform: "uppercase" }}>{r.scope}</span>
          <span style={{ font: "13px var(--font-sans)", color: "var(--ink-9)" }}>{r.action}</span>
          <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-5)" }}>{r.actor}</span>
          <span className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-4)" }}>{r.ip}</span>
          <span style={{ font: "12px var(--font-mono)", color: r.status === "ok" ? "var(--ok)" : r.status === "warn" ? "var(--warn)" : "var(--bad)" }}>{r.status === "ok" ? "200 OK" : r.status === "warn" ? "WARN" : "5XX"}</span>
        </div>
      ))}
    </div>
  );

  const renderDataTable = () => (
    <div className="card" style={{ overflow: "hidden" }}>
      <div style={{ display: "grid", gridTemplateColumns: "32px 120px 1.2fr 1fr 1.4fr 1fr 80px", gap: 12, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
        {["_", "Час", "Сайт", "Дія", "Об'єкт", "Користувач", "Статус"].map(h => (
          <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
        ))}
      </div>
      {dataLogs.map((r, i) => (
        <div key={i} style={{ display: "grid", gridTemplateColumns: "32px 120px 1.2fr 1fr 1.4fr 1fr 80px", gap: 12, padding: "14px 18px", borderTop: "1px solid var(--ink-3)", alignItems: "center" }}>
          <span className={"dot " + (r.status === "ok" ? "dot-ok" : "dot-warn")}/>
          <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{r.t}</span>
          <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{r.site}</span>
          <span style={{ font: "13px var(--font-sans)", color: "var(--ink-9)" }}>{r.action}</span>
          <span className="mono" style={{ font: "12px var(--font-mono)", color: "var(--ink-7)" }}>{r.target}</span>
          <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-5)" }}>{r.who}</span>
          <span style={{ font: "11.5px var(--font-mono)", color: r.status === "ok" ? "var(--ok)" : "var(--warn)" }}>{r.status === "ok" ? "OK" : "WARN"}</span>
        </div>
      ))}
    </div>
  );

  const renderSitesList = () => (
    <div className="card" style={{ overflow: "hidden" }}>
      <div style={{ display: "grid", gridTemplateColumns: "32px 1.4fr 1fr 100px 100px 80px", gap: 12, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
        {["_", "Сайт", "Останнє оновлення", "Подій 24h", "Errors", "_"].map((h, i) => (
          <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
        ))}
      </div>
      {SITES.slice(0, 6).map((s, i) => {
        const events24h = [142, 38, 87, 12, 64, 21][i] || 5;
        const errors = s.status === "error" ? 8 : 0;
        return (
          <div key={s.id} onClick={() => setSelectedSite(s.id)} style={{
            display: "grid", gridTemplateColumns: "32px 1.4fr 1fr 100px 100px 80px",
            gap: 12, padding: "14px 18px", borderTop: "1px solid var(--ink-3)", alignItems: "center", cursor: "pointer",
            transition: "background .12s",
          }}
            onMouseEnter={e => e.currentTarget.style.background = "var(--paper-2)"}
            onMouseLeave={e => e.currentTarget.style.background = "transparent"}>
            <span className={"dot " + (s.status === "ok" ? "dot-ok" : s.status === "pause" ? "dot-warn" : "dot-bad")}/>
            <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
              <span className="avatar avatar-sq" style={{ width: 24, height: 24, fontSize: 10 }}>{s.name[0].toUpperCase()}</span>
              <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{s.name}</span>
            </div>
            <span className="mono" style={{ font: "12px var(--font-mono)", color: "var(--ink-5)" }}>{s.lastSync}</span>
            <span className="mono num" style={{ font: "14px var(--font-mono)", color: "var(--ink-9)" }}>{events24h}</span>
            <span className="mono num" style={{ font: "14px var(--font-mono)", color: errors > 0 ? "var(--bad)" : "var(--ink-4)" }}>{errors || "—"}</span>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 4, font: "12px var(--font-sans)", color: "var(--ink-9)", justifySelf: "end" }}>
              Логи <I.Arrow size={11}/>
            </span>
          </div>
        );
      })}
    </div>
  );

  const renderSiteDrilldown = () => {
    const site = SITES.find(s => s.id === selectedSite) || SITES[0];
    const siteEvents = [
      {
        type: "update", icon: "edit", scope: "Phone",
        target: "+48 22 555 33 11",
        who: { name: "Test Admin", avatar: "TA", role: "admin" },
        date: site.name + " · 18:54:23", ago: "5 хв тому", ip: "194.30.122.18",
        changes: [
          { field: "Роль",   from: "Головний", to: "Резерв" },
          { field: "Порядок", from: "—",       to: "#2" },
        ],
      },
      {
        type: "failover", icon: "bolt", scope: "Failover",
        target: "+48 00 000 00 00",
        who: { name: "System · auto", avatar: "S", role: "system" },
        date: "18:42:11", ago: "12 хв тому",
        reason: "SIM block · health check failed 3/3",
        changes: [
          { field: "Активний", from: "+48 00 000 00 00", to: "+48 99 999 99 99" },
        ],
      },
      {
        type: "create", icon: "plus", scope: "Phone",
        target: "+49 30 555 12 34",
        who: { name: "Olha Boyko", avatar: "OB", role: "manager" },
        date: "16:18:02", ago: "3 год тому", ip: "94.158.61.4",
        changes: [
          { field: "Гео",   from: null, to: "Тільки DE" },
          { field: "Роль", from: null, to: "Головний" },
        ],
      },
      {
        type: "update", icon: "edit", scope: "Phone visibility",
        target: "+48 99 999 99 99",
        who: { name: "Olha Boyko", avatar: "OB", role: "manager" },
        date: "14:08:30", ago: "5 год тому", ip: "94.158.61.4",
        changes: [
          { field: "Видимість", from: "Сховано", to: "Видимий" },
        ],
      },
      {
        type: "delete", icon: "trash", scope: "Phone",
        target: "061 333-22-11",
        who: { name: "Іван Петренко", avatar: "ІП", role: "admin" },
        date: "12 трав · 14:11:32", ago: "вчора", ip: "194.30.122.18",
        changes: [
          { field: "Видалено", from: "061 333-22-11", to: null },
        ],
      },
    ];

    const TypeIcon = ({ type, size = 12 }) => {
      if (type === "edit")  return <I.Edit size={size}/>;
      if (type === "bolt")  return <I.Bolt size={size}/>;
      if (type === "plus")  return <I.Plus size={size}/>;
      if (type === "trash") return <I.Trash size={size}/>;
      return <I.Edit size={size}/>;
    };
    const typeColor = t => ({ update: "var(--info)", failover: "var(--bad)", create: "var(--ok)", delete: "var(--bad)" }[t] || "var(--ink-5)");
    const typeBg    = t => ({ update: "var(--info-soft)", failover: "var(--bad-soft)", create: "var(--ok-soft)", delete: "var(--bad-soft)" }[t] || "var(--ink-2)");
    const typeLabel = t => ({ update: "Зміна", failover: "Failover", create: "Створено", delete: "Видалено" }[t]);

    return (
      <>
        <button onClick={() => setSelectedSite(null)} style={{ display: "inline-flex", alignItems: "center", gap: 6, font: "13px var(--font-sans)", color: "var(--ink-5)", marginBottom: 16, cursor: "pointer" }}>
          <I.ArrowL size={13}/> Усі сайти
        </button>
        <header style={{ display: "flex", alignItems: "center", gap: 14, marginBottom: 24, paddingBottom: 16, borderBottom: "1px solid var(--ink-3)" }}>
          <span className="avatar avatar-sq" style={{ width: 36, height: 36, fontSize: 14 }}>{site.name[0].toUpperCase()}</span>
          <div>
            <div className="mono" style={{ font: "15px var(--font-mono)", color: "var(--ink-9)" }}>{site.name}</div>
            <div style={{ marginTop: 4, font: "12px var(--font-sans)", color: "var(--ink-5)" }}>
              <span className={"dot " + (site.status === "ok" ? "dot-ok" : site.status === "pause" ? "dot-warn" : "dot-bad")}/>
              {site.status === "ok" ? "Активний" : site.status === "pause" ? "Пауза" : "Помилка"}
              <span style={{ color: "var(--ink-4)", marginLeft: 8 }}>· {siteEvents.length} подій сьогодні</span>
            </div>
          </div>
          <div style={{ flex: 1 }}/>
          <button className="btn btn-secondary btn-sm"><I.Export size={12}/> Експорт</button>
        </header>

        {/* Timeline of changes with full diff */}
        <div style={{ position: "relative" }}>
          <div style={{ position: "absolute", left: 19, top: 24, bottom: 24, width: 1, background: "var(--ink-3)" }}/>
          {siteEvents.map((e, i) => (
            <article key={i} className="card" style={{ position: "relative", padding: 0, marginBottom: 12, marginLeft: 48, overflow: "hidden" }}>
              <span style={{
                position: "absolute", left: -38, top: 18,
                width: 28, height: 28, borderRadius: 999,
                background: typeBg(e.type), color: typeColor(e.type),
                display: "inline-flex", alignItems: "center", justifyContent: "center",
                border: "2px solid var(--paper)", zIndex: 1,
              }}>
                <TypeIcon type={e.icon} size={13}/>
              </span>

              <header style={{ padding: "14px 18px", display: "flex", alignItems: "center", gap: 12, borderBottom: "1px solid var(--ink-3)" }}>
                <span className="pill" style={{ background: typeBg(e.type), color: typeColor(e.type) }}>
                  <span className="dot" style={{ margin: 0, background: typeColor(e.type) }}/>
                  {typeLabel(e.type)}
                </span>
                <span style={{ font: "12px var(--font-mono)", color: "var(--ink-5)" }}>{e.scope}</span>
                <span className="mono" style={{ font: "13.5px var(--font-mono)", color: "var(--ink-9)" }}>{e.target}</span>
                <div style={{ flex: 1 }}/>
                <span className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-4)" }}>{e.ago}</span>
              </header>

              <div style={{ padding: "16px 18px" }}>
                <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                  {e.changes.map((c, k) => (
                    <div key={k} style={{ display: "grid", gridTemplateColumns: "140px 1fr 20px 1fr", gap: 12, alignItems: "center" }}>
                      <span className="eyebrow" style={{ fontSize: 10 }}>{c.field}</span>
                      <span style={{
                        padding: "4px 10px", borderRadius: 4,
                        background: c.from === null ? "transparent" : "var(--bad-soft)",
                        color: c.from === null ? "var(--ink-4)" : "var(--bad)",
                        font: "12.5px var(--font-mono)",
                        textDecoration: c.from !== null && c.to !== null ? "line-through" : "none",
                        border: c.from === null ? "1px dashed var(--ink-3)" : "none",
                        textAlign: c.from === null ? "center" : "left",
                      }}>{c.from === null ? "пусто" : c.from}</span>
                      <I.Arrow size={12} style={{ color: "var(--ink-4)", justifySelf: "center" }}/>
                      <span style={{
                        padding: "4px 10px", borderRadius: 4,
                        background: c.to === null ? "transparent" : "var(--ok-soft)",
                        color: c.to === null ? "var(--ink-4)" : "var(--ok)",
                        font: "12.5px var(--font-mono)",
                        border: c.to === null ? "1px dashed var(--ink-3)" : "none",
                        textAlign: c.to === null ? "center" : "left",
                      }}>{c.to === null ? "видалено" : c.to}</span>
                    </div>
                  ))}
                </div>

                {e.reason && (
                  <div style={{ marginTop: 12, padding: "10px 12px", borderRadius: 4, background: "var(--paper-2)", borderLeft: "2px solid " + typeColor(e.type) }}>
                    <div className="eyebrow" style={{ fontSize: 10, marginBottom: 4 }}>Причина</div>
                    <div style={{ font: "12.5px/1.5 var(--font-sans)", color: "var(--ink-7)" }}>{e.reason}</div>
                  </div>
                )}
              </div>

              <footer style={{ padding: "12px 18px", display: "flex", alignItems: "center", gap: 12, borderTop: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
                <span className="avatar" style={{
                  width: 22, height: 22, fontSize: 10,
                  background: e.who.role === "system" ? "var(--ink-4)" : "var(--ink-9)",
                  color: "var(--paper)",
                }}>{e.who.avatar}</span>
                <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-9)" }}>{e.who.name}</span>
                <span style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{e.who.role}</span>
                <div style={{ flex: 1 }}/>
                {e.ip && <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>IP {e.ip}</span>}
                <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-5)" }}>{e.date}</span>
                {(e.type === "update" || e.type === "delete" || e.type === "failover") && (
                  <button className="btn btn-ghost btn-sm" style={{ height: 24, padding: "0 10px", fontSize: 11 }}>
                    <I.Refresh size={10}/> Rollback
                  </button>
                )}
              </footer>
            </article>
          ))}
        </div>
      </>
    );
  };

  return (
    <div style={{ flex: 1, overflowY: "auto" }}>
      <Topbar crumbs={selectedSite ? ["Логи", "Сайти", SITES.find(s => s.id === selectedSite)?.name] : ["Логи"]} actions={
        <button className="btn btn-secondary btn-sm"><I.Export size={13}/> Експорт</button>
      }/>
      <PageHead title="Логи" sub="Системні події, зміни даних, активність по сайтах."/>

      <div style={{ padding: "0 40px 64px" }}>
        {/* Sub-tabs */}
        {!selectedSite && (
          <div className="tabs" style={{ marginBottom: 24 }}>
            <button onClick={() => setTab("system")} className={"tab " + (tab === "system" ? "active" : "")}>
              Системні <span className="tab-n">412</span>
            </button>
            <button onClick={() => setTab("data")} className={"tab " + (tab === "data" ? "active" : "")}>
              По даних <span className="tab-n">1240</span>
            </button>
            <button onClick={() => setTab("sites")} className={"tab " + (tab === "sites" ? "active" : "")}>
              По сайтах <span className="tab-n">{SITES.length}</span>
            </button>
          </div>
        )}

        {selectedSite
          ? renderSiteDrilldown()
          : tab === "system" ? renderSystemTable()
          : tab === "data"   ? renderDataTable()
          : renderSitesList()}
      </div>
    </div>
  );
};

const SettingsPage = () => {
  const [section, setSection] = React.useState("countries");
  const [countries, setCountries] = React.useState(Object.entries(COUNTRIES).map(([code, c]) => ({ code, ...c, dial: { PL: "+48", UA: "+380", DE: "+49", US: "+1", GB: "+44", FR: "+33" }[code] || "" })));
  const [adding, setAdding] = React.useState(false);
  const [newCountry, setNewCountry] = React.useState({ code: "", name: "", flag: "🏳️", dial: "+" });

  const sections = [
    { k: "workspace",    l: "Workspace", icon: "settings", desc: "Назва, регіон, логотип" },
    { k: "countries",    l: "Довідник країн", icon: "globe", desc: `${countries.length} країн` },
    { k: "categories",   l: "Категорії даних", icon: "data",   desc: "Phones, prices…" },
    { k: "webhooks",     l: "Webhooks",        icon: "bolt",   desc: "3 активних" },
    { k: "api",          l: "API ключі",        icon: "key",    desc: "Tokens, scopes" },
  ];

  return (
    <div style={{ flex: 1, overflowY: "auto" }}>
      <Topbar crumbs={["Налаштування", sections.find(s => s.k === section)?.l]}/>
      <PageHead title="Налаштування" sub="Workspace-параметри, довідники, інтеграції."/>

      <div style={{ padding: "0 40px 64px", display: "grid", gridTemplateColumns: "240px 1fr", gap: 40 }}>
        {/* Sidebar nav */}
        <nav style={{ display: "flex", flexDirection: "column", gap: 2, alignSelf: "flex-start", position: "sticky", top: 0 }}>
          {sections.map(s => {
            const active = s.k === section;
            return (
              <button key={s.k} onClick={() => setSection(s.k)} style={{
                display: "flex", alignItems: "flex-start", gap: 10,
                padding: "10px 12px", borderRadius: 4,
                color: active ? "var(--ink-9)" : "var(--ink-5)",
                background: active ? "var(--card)" : "transparent",
                border: "1px solid " + (active ? "var(--ink-3)" : "transparent"),
                textAlign: "left", cursor: "pointer", transition: "all .12s",
              }}>
                <div style={{ flex: 1 }}>
                  <div style={{ font: "13.5px var(--font-sans)" }}>{s.l}</div>
                  <div style={{ marginTop: 3, font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{s.desc}</div>
                </div>
                {active && <I.Arrow size={12} style={{ marginTop: 2, color: "var(--ink-4)" }}/>}
              </button>
            );
          })}
        </nav>

        {/* Body */}
        <div>
          {section === "countries"    && <CountriesSection countries={countries} setCountries={setCountries} adding={adding} setAdding={setAdding} newCountry={newCountry} setNewCountry={setNewCountry}/>}
          {section === "categories"   && <CategoriesSection/>}
          {section === "workspace"    && <WorkspaceSection/>}
          {section === "webhooks"     && <WebhooksSection/>}
          {section === "api"          && <ApiSection/>}
        </div>
      </div>
    </div>
  );
};

/* ─── Countries CRUD ─── */
const CountriesSection = ({ countries, setCountries, adding, setAdding, newCountry, setNewCountry }) => {
  const remove = (code) => setCountries(cs => cs.filter(c => c.code !== code));
  const save = () => {
    if (!newCountry.code || !newCountry.name) return;
    setCountries(cs => [...cs, newCountry]);
    setNewCountry({ code: "", name: "", flag: "🏳️", dial: "+" });
    setAdding(false);
  };

  return (
    <>
      <header style={{ display: "flex", alignItems: "flex-end", marginBottom: 24 }}>
        <div style={{ flex: 1 }}>
          <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)" }}>Довідник країн</h2>
          <p style={{ marginTop: 8, font: "13.5px var(--font-sans)", color: "var(--ink-5)" }}>Використовується для гео-правил. ISO-2 + dial code.</p>
        </div>
        <button onClick={() => setAdding(true)} className="btn btn-primary btn-sm"><I.Plus size={13}/> Додати країну</button>
      </header>

      {adding && (
        <div className="card" style={{ padding: 18, marginBottom: 14, borderLeft: "2px solid var(--accent)", background: "var(--accent-soft)" }}>
          <div className="eyebrow" style={{ marginBottom: 14, color: "var(--accent)" }}>Нова країна</div>
          <div style={{ display: "grid", gridTemplateColumns: "80px 1fr 100px 100px", gap: 12 }}>
            <div>
              <label className="label">Прапор</label>
              <input className="input" value={newCountry.flag} onChange={e => setNewCountry({...newCountry, flag: e.target.value})} style={{ font: "20px var(--font-sans)" }}/>
            </div>
            <div>
              <label className="label">Назва</label>
              <input className="input" placeholder="напр. Іспанія" value={newCountry.name} onChange={e => setNewCountry({...newCountry, name: e.target.value})}/>
            </div>
            <div>
              <label className="label">ISO-2</label>
              <input className="input mono" placeholder="ES" maxLength={2} value={newCountry.code} onChange={e => setNewCountry({...newCountry, code: e.target.value.toUpperCase()})} style={{ font: "15px var(--font-mono)", textTransform: "uppercase" }}/>
            </div>
            <div>
              <label className="label">Dial</label>
              <input className="input mono" placeholder="+34" value={newCountry.dial} onChange={e => setNewCountry({...newCountry, dial: e.target.value})} style={{ font: "15px var(--font-mono)" }}/>
            </div>
          </div>
          <div style={{ marginTop: 16, display: "flex", gap: 8, justifyContent: "flex-end" }}>
            <button onClick={() => setAdding(false)} className="btn btn-ghost btn-sm">Скасувати</button>
            <button onClick={save} className="btn btn-primary btn-sm">Зберегти</button>
          </div>
        </div>
      )}

      <div className="card" style={{ overflow: "hidden" }}>
        <div style={{ display: "grid", gridTemplateColumns: "60px 1fr 90px 110px 90px 70px", gap: 16, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
          {["_", "Назва", "ISO-2", "Dial code", "Сайти", "_"].map((h, i) => (
            <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
          ))}
        </div>
        {countries.length === 0 && (
          <div style={{ padding: "48px 24px", textAlign: "center", color: "var(--ink-5)", font: "13px var(--font-sans)" }}>
            Список порожній. Додайте першу країну.
          </div>
        )}
        {countries.map((c, i) => (
          <div key={c.code} style={{ display: "grid", gridTemplateColumns: "60px 1fr 90px 110px 90px 70px", gap: 16, padding: "14px 18px", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center" }}>
            <span style={{ font: "22px var(--font-sans)" }}>{c.flag}</span>
            <span style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{c.name}</span>
            <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-7)", letterSpacing: "0.06em" }}>{c.code}</span>
            <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-7)" }}>{c.dial}</span>
            <span className="mono num" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{[5, 4, 2, 1, 1, 1][i] || 0}</span>
            <div style={{ display: "flex", justifyContent: "flex-end", gap: 4, color: "var(--ink-4)" }}>
              <button style={{ width: 24, height: 24, cursor: "pointer" }}><I.Edit size={13}/></button>
              <button onClick={() => remove(c.code)} style={{ width: 24, height: 24, cursor: "pointer" }}><I.Trash size={13}/></button>
            </div>
          </div>
        ))}
      </div>
    </>
  );
};

/* ─── Categories of data ─── */
const CategoriesSection = () => {
  const core = [
    { id: "phones",     label: "Телефони",       icon: "phone", desc: "Основні номери з гео і failover", n: 142, enabled: true,  required: true },
    { id: "messengers", label: "Месенджери",     icon: "chat",  desc: "Telegram, Viber, WhatsApp",       n: 56,  enabled: true,  required: true },
  ];
  const extra = [
    { id: "prices",     label: "Ціни",            icon: "tag",   desc: "Multi-currency, per geo",          n: 24,  enabled: true,  required: false },
    { id: "addresses",  label: "Адреси",          icon: "map",   desc: "Офіси, ПВЗ, склади",                 n: 38,  enabled: true,  required: false },
    { id: "socials",    label: "Соціальні мережі", icon: "share", desc: "Instagram, Facebook, TikTok",        n: 18,  enabled: true,  required: false },
    { id: "custom",     label: "Custom поля",      icon: "plus",  desc: "Власні типи даних",                   n: 6,   enabled: true,  required: false },
  ];

  const Row = ({ c, isCore }) => (
    <div style={{ display: "grid", gridTemplateColumns: "48px 1fr 60px 80px 80px", gap: 16, padding: "16px 18px", borderTop: "1px solid var(--ink-3)", alignItems: "center" }}>
      <span style={{
        width: 32, height: 32, borderRadius: 4,
        background: isCore ? "var(--accent-soft)" : "var(--ink-2)",
        color: isCore ? "var(--accent)" : "var(--ink-7)",
        display: "inline-flex", alignItems: "center", justifyContent: "center",
      }}>
        {c.icon === "phone" ? <I.Phone size={14}/>
        : c.icon === "chat"  ? <I.Chat size={14}/>
        : c.icon === "map"   ? <I.Map size={14}/>
        : <I.Plus size={14}/>}
      </span>
      <div>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{c.label}</span>
          {c.required && <span className="pill" style={{ height: 20, fontSize: 10, background: "var(--accent-soft)", color: "var(--accent)" }}>обов'язкове</span>}
        </div>
        <div style={{ marginTop: 4, font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{c.desc}</div>
      </div>
      <span className="mono num" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{c.n}</span>
      <Toggle on={c.enabled}/>
      <div style={{ display: "flex", justifyContent: "flex-end", gap: 4, color: "var(--ink-4)" }}>
        <button style={{ width: 24, height: 24, cursor: "pointer" }}><I.Edit size={13}/></button>
        {!c.required && <button style={{ width: 24, height: 24, cursor: "pointer" }}><I.Trash size={13}/></button>}
      </div>
    </div>
  );

  return (
    <>
      <header style={{ marginBottom: 24 }}>
        <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)" }}>Категорії даних</h2>
        <p style={{ marginTop: 8, font: "13.5px var(--font-sans)", color: "var(--ink-5)", maxWidth: 540 }}>
          Які типи контактів і даних зберігає workspace. <b style={{ color: "var(--ink-9)", fontWeight: 500 }}>Основні</b> — це те, що зазвичай показуєте відвідувачам (телефони і месенджери). <b style={{ color: "var(--ink-9)", fontWeight: 500 }}>Інші</b> — додаткові дані сайту.
        </p>
      </header>

      <div style={{ marginBottom: 24 }}>
        <div className="eyebrow" style={{ marginBottom: 12 }}>Основні · контактні</div>
        <div className="card" style={{ overflow: "hidden", borderLeft: "2px solid var(--accent)" }}>
          <div style={{ display: "grid", gridTemplateColumns: "48px 1fr 60px 80px 80px", gap: 16, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
            {["_", "Категорія", "Записів", "Активна", "_"].map((h, i) => (
              <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>
          {core.map(c => <Row key={c.id} c={c} isCore/>)}
        </div>
      </div>

      <div>
        <div className="eyebrow" style={{ marginBottom: 12 }}>Інші · додаткові</div>
        <div className="card" style={{ overflow: "hidden" }}>
          <div style={{ display: "grid", gridTemplateColumns: "48px 1fr 60px 80px 80px", gap: 16, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
            {["_", "Категорія", "Записів", "Активна", "_"].map((h, i) => (
              <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>
          {extra.map(c => <Row key={c.id} c={c} isCore={false}/>)}
        </div>
        <button style={{
          marginTop: 14, width: "100%", padding: 14, borderRadius: 4,
          border: "1px dashed var(--ink-3)", background: "transparent",
          color: "var(--ink-5)", font: "13px var(--font-sans)",
          display: "flex", alignItems: "center", justifyContent: "center", gap: 8,
          cursor: "pointer",
        }}>
          <I.Plus size={13}/> Додати свою категорію
        </button>
      </div>
    </>
  );
};

const Toggle = ({ on }) => (
  <span style={{
    width: 32, height: 18, borderRadius: 999,
    background: on ? "var(--ink-9)" : "var(--ink-3)",
    position: "relative", display: "inline-block", cursor: "pointer",
  }}>
    <span style={{
      position: "absolute", top: 2, left: on ? 16 : 2,
      width: 14, height: 14, borderRadius: 999,
      background: "var(--paper)",
      transition: "left .15s",
    }}/>
  </span>
);

/* ─── Stub sections ─── */
const WorkspaceSection = () => (
  <>
    <header style={{ marginBottom: 24 }}>
      <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)" }}>Workspace</h2>
      <p style={{ marginTop: 8, font: "13.5px var(--font-sans)", color: "var(--ink-5)" }}>Загальні параметри робочого простору.</p>
    </header>
    <div className="card" style={{ padding: 24, display: "flex", flexDirection: "column", gap: 24 }}>
      <div><label className="label">Назва workspace</label><input className="input" defaultValue="DataBridge"/></div>
      <div><label className="label">Регіон зберігання</label>
        <div style={{ display: "flex", gap: 8 }}>
          {["EU · Frankfurt", "US · Virginia", "AP · Singapore"].map((r, i) => (
            <button key={r} style={{
              flex: 1, padding: "12px 14px", borderRadius: 4,
              border: "1px solid " + (i === 0 ? "var(--ink-9)" : "var(--ink-3)"),
              background: i === 0 ? "var(--ink-9)" : "transparent",
              color: i === 0 ? "var(--paper)" : "var(--ink-9)",
              font: "13px var(--font-sans)", cursor: "pointer",
            }}>{r}</button>
          ))}
        </div>
      </div>
      <div><label className="label">URL workspace</label>
        <div style={{ display: "flex", alignItems: "baseline", gap: 6 }}>
          <span className="mono" style={{ color: "var(--ink-4)", font: "14px var(--font-mono)" }}>databridge.com/</span>
          <input className="input mono" defaultValue="acme-corp" style={{ font: "15px var(--font-mono)" }}/>
        </div>
      </div>
    </div>
  </>
);
const IntegrationsSection = () => (
  <>
    <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)", marginBottom: 24 }}>Інтеграції</h2>
    <div className="card" style={{ padding: 48, textAlign: "center", color: "var(--ink-5)", font: "13.5px var(--font-sans)" }}>Slack, Discord, Zapier — незабаром.</div>
  </>
);
const WebhooksSection = () => (
  <>
    <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)", marginBottom: 24 }}>Webhooks</h2>
    <div className="card" style={{ overflow: "hidden" }}>
      {["phone.created", "phone.failover", "site.error"].map((e, i) => (
        <div key={e} style={{ display: "grid", gridTemplateColumns: "1fr auto auto auto", gap: 12, padding: "14px 18px", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center" }}>
          <div>
            <div className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{e}</div>
            <div className="mono" style={{ marginTop: 3, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>https://hooks.acme.com/db/{e.split(".")[0]}</div>
          </div>
          <Toggle on/>
          <button style={{ color: "var(--ink-4)", cursor: "pointer" }}><I.Edit size={13}/></button>
          <button style={{ color: "var(--ink-4)", cursor: "pointer" }}><I.Trash size={13}/></button>
        </div>
      ))}
    </div>
  </>
);
const ApiSection = () => (
  <>
    <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)", marginBottom: 24 }}>API ключі</h2>
    <div className="card" style={{ overflow: "hidden" }}>
      {[
        { name: "Production server", key: "db_prod_2K7sX9m...", lastUsed: "1 хв" },
        { name: "CI bot",              key: "db_ci_4M8sX2k...",   lastUsed: "вчора" },
      ].map((t, i) => (
        <div key={i} style={{ display: "grid", gridTemplateColumns: "1fr 1fr 100px auto", gap: 12, padding: "16px 18px", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center" }}>
          <div>
            <div style={{ font: "13px var(--font-sans)", color: "var(--ink-9)" }}>{t.name}</div>
            <div className="mono" style={{ marginTop: 3, font: "12px var(--font-mono)", color: "var(--ink-5)" }}>{t.key}</div>
          </div>
          <span style={{ font: "12px var(--font-sans)", color: "var(--ink-5)" }}>Останнє: {t.lastUsed}</span>
          <button className="btn btn-secondary btn-sm">Копіювати</button>
          <button className="btn btn-ghost btn-sm" style={{ color: "var(--bad)" }}>Відкликати</button>
        </div>
      ))}
    </div>
  </>
);
const BillingSection = () => (
  <>
    <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)", marginBottom: 24 }}>Білінг</h2>
    <div className="card" style={{ padding: 24 }}>
      <div style={{ display: "flex", alignItems: "baseline", gap: 12 }}>
        <span className="mono num" style={{ font: "32px var(--font-mono)", color: "var(--ink-9)", letterSpacing: "-0.025em" }}>$90</span>
        <span style={{ font: "13px var(--font-sans)", color: "var(--ink-5)" }}>/ місяць · 6 seats × $15</span>
      </div>
      <div style={{ marginTop: 16, font: "12.5px var(--font-sans)", color: "var(--ink-5)" }}>Наступний платіж — <b style={{ color: "var(--ink-9)" }}>15 червня 2026</b>.</div>
    </div>
  </>
);
const SecuritySection = () => (
  <>
    <h2 style={{ font: "400 24px/1 var(--font-sans)", color: "var(--ink-9)", marginBottom: 24 }}>Безпека</h2>
    <div className="card" style={{ overflow: "hidden" }}>
      {[
        { l: "SSO · Google Workspace", desc: "Усі логіни через Google", on: true },
        { l: "2FA обов'язкове",          desc: "TOTP для всіх ролей", on: true },
        { l: "Audit log retention · 90 днів", desc: "Можна збільшити до року", on: false },
      ].map((r, i) => (
        <div key={i} style={{ display: "grid", gridTemplateColumns: "1fr auto", gap: 16, padding: "18px 20px", borderTop: i ? "1px solid var(--ink-3)" : "none", alignItems: "center" }}>
          <div>
            <div style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{r.l}</div>
            <div style={{ marginTop: 4, font: "12.5px var(--font-sans)", color: "var(--ink-5)" }}>{r.desc}</div>
          </div>
          <Toggle on={r.on}/>
        </div>
      ))}
    </div>
  </>
);

Object.assign(window, { GroupsPage, DataPage, TeamPage, LogsPage, SettingsPage });
