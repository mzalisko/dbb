/* DataBridge · App · fully interactive */

/* ─────────────────────── Router ─────────────────────── */
const App = () => {
  const [route, setRoute] = React.useState({ page: "dash" });
  const [theme, setTheme] = React.useState("light");
  const nav = (page, params = {}) => setRoute({ page, ...params });
  const toggleTheme = () => setTheme(t => t === "light" ? "dark" : "light");

  React.useEffect(() => {
    const root = document.querySelector('[data-dc-slot="app"]') || document.documentElement;
    root.setAttribute("data-theme", theme);
  }, [theme]);

  return (
    <div data-theme={theme} style={{ width: "100%", height: "100%", display: "flex", overflow: "hidden", background: "var(--paper)" }}>
      <Sidebar route={route} nav={nav} theme={theme} toggleTheme={toggleTheme} />
      <main style={{ flex: 1, minWidth: 0, display: "flex", flexDirection: "column", position: "relative" }}>
        {route.page === "dash" && <Dashboard nav={nav} />}
        {route.page === "sites" && <SitesPage nav={nav} />}
        {route.page === "site" && <SiteDetail siteId={route.siteId} nav={nav} />}
        {route.page === "groups" && <GroupsPage />}
        {route.page === "data" && <DataPage />}
        {route.page === "team" && <TeamPage />}
        {route.page === "logs" && <LogsPage />}
        {route.page === "settings" && <SettingsPage />}
      </main>
    </div>);

};

/* ─────────────────────── Sidebar ─────────────────────── */
const Sidebar = ({ route, nav, theme, toggleTheme }) => {
  const items = [
  { k: "dash", l: "Дашборд", i: I.Dash },
  { k: "sites", l: "Сайти", i: I.Sites, n: SITES.length },
  { k: "groups", l: "Групи сайтів", i: I.Groups, n: GROUPS.length },
  { k: "data", l: "Браузер даних", i: I.Data },
  { k: "team", l: "Команда", i: I.Team, n: TEAM.length },
  { k: "logs", l: "Логи", i: I.Logs },
  { k: "settings", l: "Налаштування", i: I.Settings }];

  const isActive = (k) => route.page === k || k === "sites" && route.page === "site";

  return (
    <aside style={{
      width: 220, flex: "0 0 220px",
      display: "flex", flexDirection: "column",
      padding: "28px 16px 20px",
      background: "var(--paper-2)",
      borderRight: "1px solid var(--ink-3)",
      position: "relative", zIndex: 2
    }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "0 6px 22px" }}>
        <span style={{ width: 30, height: 30, borderRadius: 6, background: "var(--ink-9)", color: "var(--paper)", display: "inline-flex", alignItems: "center", justifyContent: "center" }}>
          <I.Logo size={16} />
        </span>
        <div>
          <div style={{ font: "500 14px/1 var(--font-sans)", color: "var(--ink-9)" }}>DataBridge</div>
          <div style={{ font: "11px/1 var(--font-mono)", color: "var(--ink-5)", marginTop: 4 }}>CRM</div>
        </div>
      </div>

      <div style={{ display: "flex", alignItems: "center", gap: 8, height: 32, padding: "0 10px", background: "var(--card)", border: "1px solid var(--ink-3)", borderRadius: 6, color: "var(--ink-5)", marginBottom: 16, cursor: "text" }}>
        <I.Search size={13} />
        <span style={{ flex: 1, font: "12.5px var(--font-sans)" }}>Знайти…</span>
        <span style={{ font: "10.5px var(--font-mono)", padding: "2px 5px", borderRadius: 3, background: "var(--paper)" }}>⌘K</span>
      </div>

      <nav style={{ display: "flex", flexDirection: "column", gap: 2 }}>
        {items.map((it) => {
          const a = isActive(it.k);
          const Ico = it.i;
          return (
            <button key={it.k} onClick={() => nav(it.k)} style={{
              display: "flex", alignItems: "center", gap: 10,
              padding: "8px 10px", borderRadius: 4,
              color: a ? "var(--ink-9)" : "var(--ink-5)",
              font: "400 13.5px/1 var(--font-sans)",
              background: a ? "var(--card)" : "transparent",
              boxShadow: a ? "inset 0 0 0 1px var(--ink-3)" : "none",
              cursor: "pointer", textAlign: "left",
              transition: "all .12s"
            }}>
              <Ico size={14} style={{ color: a ? "var(--ink-9)" : "var(--ink-4)" }} />
              <span style={{ flex: 1 }}>{it.l}</span>
              {it.n != null && <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{it.n}</span>}
            </button>);

        })}
      </nav>

      <div style={{ flex: 1 }} />

      <div style={{ paddingTop: 16, borderTop: "1px solid var(--ink-3)", display: "flex", alignItems: "center", gap: 10 }}>
        <span style={{ position: "relative" }}>
          <span className="avatar" style={{ background: "var(--accent)", color: "var(--paper)" }}>TA</span>
          <span style={{ position: "absolute", right: -1, bottom: -1, width: 9, height: 9, borderRadius: 999, background: "var(--ok)", border: "2px solid var(--paper-2)" }} />
        </span>
        <div style={{ flex: 1 }}>
          <div style={{ font: "400 13px/1.2 var(--font-sans)", color: "var(--ink-9)" }}>Test Admin</div>
          <div className="mono" style={{ font: "11px/1 var(--font-mono)", color: "var(--ink-5)", marginTop: 3 }}>admin</div>
        </div>
        {toggleTheme && (
          <button onClick={toggleTheme} title={theme === "light" ? "Темна тема" : "Світла тема"} style={{
            width: 28, height: 28, borderRadius: 999,
            color: "var(--ink-5)", cursor: "pointer",
            display: "inline-flex", alignItems: "center", justifyContent: "center",
            transition: "color .12s",
          }}
          onMouseEnter={e => e.currentTarget.style.color = "var(--ink-9)"}
          onMouseLeave={e => e.currentTarget.style.color = "var(--ink-5)"}>
            {theme === "light" ? <I.Moon size={14}/> : <I.Sun size={14}/>}
          </button>
        )}
      </div>
    </aside>);

};

/* ─────────────────────── Topbar ─────────────────────── */
const Topbar = ({ crumbs, actions }) =>
<header style={{
  position: "relative", zIndex: 1,
  display: "flex", alignItems: "center", gap: 16,
  height: 56, padding: "0 32px",
  borderBottom: "1px solid var(--ink-3)",
  background: "var(--paper)"
}}>
    <div style={{ flex: 1, display: "flex", alignItems: "center", gap: 8 }}>
      {crumbs && crumbs.map((c, i) =>
    <React.Fragment key={i}>
          {i > 0 && <span style={{ color: "var(--ink-4)" }}>/</span>}
          {typeof c === "string" ?
      <span style={{ font: "13.5px var(--font-sans)", color: i === crumbs.length - 1 ? "var(--ink-9)" : "var(--ink-5)" }}>{c}</span> :
      c}
        </React.Fragment>
    )}
    </div>
    {actions}
    <button style={{ width: 32, height: 32, borderRadius: 6, color: "var(--ink-5)", display: "inline-flex", alignItems: "center", justifyContent: "center" }}>
      <I.Bell size={14} />
    </button>
  </header>;


/* ─────────────────────── Page head ─────────────────────── */
const PageHead = ({ eyebrow, title, sub, actions }) =>
<header style={{ padding: "40px 40px 28px" }} data-comment-anchor="5dec5e49c6-header-130-3">
    {eyebrow && <div className="eyebrow">{eyebrow}</div>}
    <div style={{ marginTop: eyebrow ? 14 : 0, display: "flex", alignItems: "flex-end", gap: 20 }}>
      <h1 style={{ font: "400 36px/1.05 var(--font-sans)", letterSpacing: "-0.03em", color: "var(--ink-9)", flex: 1 }}>{title}</h1>
      {actions && <div style={{ display: "flex", gap: 8, alignItems: "center", paddingBottom: 4 }}>{actions}</div>}
    </div>
    {sub && <p style={{ marginTop: 12, font: "14.5px/1.55 var(--font-sans)", color: "var(--ink-5)", maxWidth: 580 }}>{sub}</p>}
  </header>;


/* ─────────────────────── Drawer ─────────────────────── */
const Drawer = ({ title, sub, width = 480, footer, children, onClose }) =>
<>
    <div onClick={onClose} className="fade-in" style={{
    position: "absolute", inset: 0,
    background: "rgba(20, 18, 14, 0.32)",
    zIndex: 100, cursor: "pointer"
  }} />
    <aside className="slide-in" style={{
    position: "absolute", top: 0, right: 0, bottom: 0,
    width, maxWidth: "100%",
    background: "var(--card)",
    borderLeft: "1px solid var(--ink-3)",
    boxShadow: "-24px 0 60px -20px rgba(20,18,14,0.18)",
    display: "flex", flexDirection: "column",
    zIndex: 101
  }}>
      <header style={{ padding: "20px 24px 18px", borderBottom: "1px solid var(--ink-3)", display: "flex", alignItems: "flex-start", gap: 12, flexShrink: 0 }}>
        <div style={{ flex: 1, minWidth: 0 }}>
          <h2 style={{ font: "400 20px/1.2 var(--font-sans)", letterSpacing: "-0.022em", color: "var(--ink-9)" }}>{title}</h2>
          {sub && <p style={{ marginTop: 6, font: "12.5px/1.5 var(--font-sans)", color: "var(--ink-5)" }}>{sub}</p>}
        </div>
        <button onClick={onClose} style={{ width: 32, height: 32, borderRadius: 999, color: "var(--ink-5)", display: "inline-flex", alignItems: "center", justifyContent: "center" }}>
          <I.Close size={15} />
        </button>
      </header>
      <div style={{ flex: 1, padding: "24px", overflowY: "auto" }}>{children}</div>
      {footer && <footer style={{ padding: "14px 22px", borderTop: "1px solid var(--ink-3)", display: "flex", gap: 8, justifyContent: "flex-end", background: "var(--paper-2)", flexShrink: 0 }}>{footer}</footer>}
    </aside>
  </>;


/* ─────────────────────── Page: Dashboard ─────────────────────── */
const Dashboard = ({ nav }) =>
<div style={{ flex: 1, overflowY: "auto" }}>
    <Topbar crumbs={["Дашборд"]} actions={
  <button className="btn btn-primary btn-sm"><I.Plus size={13} /> Додати сайт</button>
  } />
    <PageHead
    eyebrow="Огляд"
    title={<><span style={{ color: "var(--ink-9)" }}>{SITES.length}</span> <span style={{ color: "var(--ink-4)" }}>сайтів</span></>}
    sub="Стан робочого простору. Натисніть на сайт щоб відкрити його." />
  
    <div style={{ padding: "0 40px 64px" }}>
      {/* Two-column */}
      <section style={{ marginTop: 8, display: "grid", gridTemplateColumns: "1.5fr 1fr", gap: 48 }}>
        <div>
          <header style={{ display: "flex", alignItems: "baseline", marginBottom: 12, paddingBottom: 12, borderBottom: "1px solid var(--ink-3)" }}>
            <h3 style={{ font: "400 18px/1 var(--font-sans)", color: "var(--ink-9)", flex: 1 }}>Сайти</h3>
            <button onClick={() => nav("sites")} className="btn btn-ghost btn-sm" style={{ padding: 0 }}>Усі →</button>
          </header>
          {SITES.slice(0, 6).map((s) =>
        <button key={s.id} onClick={() => nav("site", { siteId: s.id })} style={{
          display: "grid", gridTemplateColumns: "32px 1fr auto auto",
          gap: 14, padding: "14px 0",
          borderBottom: "1px solid var(--ink-3)",
          alignItems: "center", width: "100%", cursor: "pointer", textAlign: "left",
          transition: "background .12s",
          background: "transparent"
        }} onMouseEnter={(e) => e.currentTarget.style.background = "var(--paper-2)"}
        onMouseLeave={(e) => e.currentTarget.style.background = "transparent"}>
              <span className="avatar avatar-sq">{s.name[0].toUpperCase()}</span>
              <div>
                <div className="mono" style={{ font: "13.5px var(--font-mono)", color: "var(--ink-9)" }}>{s.name}</div>
                <div style={{ marginTop: 3, font: "12px var(--font-sans)", color: s.status === "error" ? "var(--bad)" : "var(--ink-5)" }}>
                  <span className={"dot " + (s.status === "ok" ? "dot-ok" : s.status === "pause" ? "dot-warn" : "dot-bad")} />
                  {s.status === "ok" ? "Активний" : s.status === "pause" ? "Пауза" : "Помилка sync"}
                </div>
              </div>
              <span className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-4)" }}>{s.lastSync}</span>
              <I.Arrow size={14} style={{ color: "var(--ink-4)" }} />
            </button>
        )}
        </div>

        <div>
          <header style={{ display: "flex", alignItems: "baseline", marginBottom: 12, paddingBottom: 12, borderBottom: "1px solid var(--ink-3)" }}>
            <h3 style={{ font: "400 18px/1 var(--font-sans)", color: "var(--ink-9)", flex: 1 }}>Системні логи</h3>
            <button onClick={() => nav("logs")} className="btn btn-ghost btn-sm" style={{ padding: 0 }}>Усі →</button>
          </header>
          {LOGS.slice(0, 7).map((e, i) =>
        <div key={i} style={{ padding: "12px 0", borderBottom: "1px solid var(--ink-3)" }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", gap: 12 }}>
                <div style={{ font: "13px/1.4 var(--font-sans)", color: "var(--ink-8)" }}>
                  <span className={"dot " + (e.status === "ok" ? "dot-ok" : "dot-bad")} />
                  <span className="mono" style={{ color: "var(--ink-9)", fontSize: 12.5 }}>{e.site}</span>
                  <span style={{ color: "var(--ink-5)", marginLeft: 6 }}>{e.action}</span>
                </div>
                <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{e.t}</span>
              </div>
            </div>
        )}
        </div>
      </section>
    </div>
  </div>;


/* ─────────────────────── Page: Sites ─────────────────────── */
const SitesPage = ({ nav }) => {
  const [groupFilter, setGroupFilter] = React.useState("all");
  const filtered = SITES.filter((s) => groupFilter === "all" || s.group === groupFilter);

  return (
    <div style={{ flex: 1, overflowY: "auto" }}>
      <Topbar crumbs={["Сайти"]} actions={
      <>
          <button className="btn btn-secondary btn-sm"><I.Export size={13} /> Експорт</button>
          <button className="btn btn-primary btn-sm"><I.Plus size={13} /> Додати сайт</button>
        </>
      } />
      <PageHead title={`Сайти · ${SITES.length}`} sub="Натисніть на картку щоб відкрити сайт зі всіма контактами." />

      {/* Group filter */}
      <div style={{ padding: "0 40px 24px", display: "flex", gap: 8 }}>
        {[{ k: "all", l: "Усі" }, ...GROUPS.map((g) => ({ k: g.id, l: g.name, c: g.color }))].map((g) =>
        <button key={g.k} onClick={() => setGroupFilter(g.k)} style={{
          display: "inline-flex", alignItems: "center", gap: 8,
          height: 32, padding: "0 14px", borderRadius: 999,
          background: groupFilter === g.k ? "var(--ink-9)" : "var(--card)",
          color: groupFilter === g.k ? "var(--paper)" : "var(--ink-7)",
          boxShadow: groupFilter === g.k ? "none" : "inset 0 0 0 1px var(--ink-3)",
          font: "13px var(--font-sans)", cursor: "pointer"
        }}>
            {g.c && <span style={{ width: 6, height: 6, borderRadius: 999, background: g.c }} />}
            {g.l}
          </button>
        )}
      </div>

      <div style={{ padding: "0 40px 64px", display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 14 }}>
        {filtered.map((s) => {
          const group = GROUPS.find((g) => g.id === s.group);
          return (
            <button key={s.id} onClick={() => nav("site", { siteId: s.id })} style={{
              textAlign: "left", padding: 18, background: "var(--card)",
              border: "1px solid var(--ink-3)", borderRadius: 4,
              cursor: "pointer", transition: "border-color .15s, transform .15s"
            }} onMouseEnter={(e) => {e.currentTarget.style.borderColor = "var(--ink-9)";}}
            onMouseLeave={(e) => {e.currentTarget.style.borderColor = "var(--ink-3)";}}>
              <div style={{ display: "flex", alignItems: "flex-start", gap: 10 }}>
                <span className="avatar avatar-sq">{s.name[0].toUpperCase()}</span>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div className="mono" style={{ font: "13.5px var(--font-mono)", color: "var(--ink-9)" }}>{s.name}</div>
                  <div style={{ marginTop: 4, font: "12px var(--font-sans)", color: s.status === "error" ? "var(--bad)" : "var(--ink-5)" }}>
                    <span className={"dot " + (s.status === "ok" ? "dot-ok" : s.status === "pause" ? "dot-warn" : "dot-bad")} />
                    {s.status === "ok" ? "Активний" : s.status === "pause" ? "Пауза" : "Помилка"}
                    <span style={{ color: "var(--ink-4)", marginLeft: 6 }}>· {s.lastSync}</span>
                  </div>
                </div>
                {s.fav && <span style={{ color: "var(--warn)" }}><I.Star size={14} /></span>}
              </div>
              {s.err &&
              <div style={{ marginTop: 12, padding: "8px 10px", borderLeft: "2px solid var(--bad)", background: "var(--bad-soft)", font: "12px var(--font-mono)", color: "var(--bad)" }}>{s.err}</div>
              }
              <div style={{ marginTop: 14, paddingTop: 12, borderTop: "1px solid var(--ink-3)", display: "flex", gap: 18, font: "12px var(--font-mono)", color: "var(--ink-5)" }}>
                <span><span style={{ width: 6, height: 6, borderRadius: 999, background: group.color, display: "inline-block", marginRight: 6 }} />{group.name}</span>
                <span>📞 {s.phones}</span>
                <span>💬 {s.msgs}</span>
              </div>
            </button>);

        })}
      </div>
    </div>);

};

window.App = App;
window.Drawer = Drawer;
window.Topbar = Topbar;
window.PageHead = PageHead;