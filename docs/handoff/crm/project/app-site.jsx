/* DataBridge · Site Detail (with tabs, geo filter, drawer) */

const SiteDetail = ({ siteId, nav }) => {
  const site = SITES.find(s => s.id === siteId) || SITES[0];
  const group = GROUPS.find(g => g.id === site.group);
  const [tab, setTab] = React.useState("data");

  return (
    <div style={{ flex: 1, overflowY: "auto", position: "relative" }}>
      <Topbar crumbs={[
        <button key="back" onClick={() => nav("sites")} style={{ font: "13.5px var(--font-sans)", color: "var(--ink-5)", cursor: "pointer" }}>Сайти</button>,
        site.name,
      ]} actions={
        <>
          <button className="btn btn-secondary btn-sm" onClick={() => nav("sites")}><I.ArrowL size={13}/> Назад</button>
          <button className="btn btn-secondary btn-sm"><I.Refresh size={13}/> Sync</button>
          <button className="btn btn-primary btn-sm"><I.Plus size={13}/> Додати</button>
        </>
      }/>

      <PageHead
        eyebrow={<><span style={{ width: 6, height: 6, borderRadius: 999, background: group.color, display: "inline-block", marginRight: 6, verticalAlign: 2 }}/>{group.name} · <span className="dot dot-ok"/>Активний</>}
        title={site.name}
        sub="Кілька номерів одночасно. Кожен видимий за своїм гео-правилом — нижче переключіть «Перегляд» щоб побачити, що бачить відвідувач з певної країни."
      />

      <div style={{ padding: "0 40px" }}>
        <div className="tabs">
          {[
            { k: "overview", l: "Огляд" },
            { k: "data",     l: "Дані", n: DEMO_PHONES.length },
            { k: "activity", l: "Активність" },
            { k: "settings", l: "Налаштування" },
          ].map(t => (
            <button key={t.k} onClick={() => setTab(t.k)} className={"tab " + (tab === t.k ? "active" : "")}>
              {t.l} {t.n != null && <span className="tab-n">{t.n}</span>}
            </button>
          ))}
        </div>
      </div>

      {tab === "overview" && <OverviewTab/>}
      {tab === "data"     && <DataTab/>}
      {tab === "activity" && <ActivityTab/>}
      {tab === "settings" && <SettingsTab/>}
    </div>
  );
};

/* ─── Overview tab ─── */
const OverviewTab = () => {
  const [view, setView] = React.useState("cards"); // cards | table
  const zones = [
    { key: "PL",    label: "Польща",      flag: "🇵🇱", code: "PL" },
    { key: "UA",    label: "Україна",     flag: "🇺🇦", code: "UA" },
    { key: "WORLD", label: "Решта світу", flag: "🌐", code: "—" },
  ];

  return (
    <div style={{ padding: "32px 40px 64px" }}>
      <header style={{ display: "flex", alignItems: "flex-end", marginBottom: 8 }}>
        <div style={{ flex: 1 }}>
          <h3 style={{ font: "400 22px/1 var(--font-sans)", color: "var(--ink-9)" }}>Що бачать відвідувачі</h3>
          <p style={{ marginTop: 8, font: "14px var(--font-sans)", color: "var(--ink-5)" }}>
            Сайт має {DEMO_PHONES.length} номерів + {DEMO_MSGS.length} месенджерів. Кожна країна бачить свій пул.
          </p>
        </div>
        <div style={{ display: "flex", gap: 4, padding: 3, background: "var(--ink-2)", borderRadius: 8 }}>
          <button onClick={() => setView("cards")} style={{
            display: "inline-flex", alignItems: "center", gap: 6,
            padding: "6px 12px", borderRadius: 5, cursor: "pointer",
            color: view === "cards" ? "var(--ink-9)" : "var(--ink-5)",
            background: view === "cards" ? "var(--card)" : "transparent",
            boxShadow: view === "cards" ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
            font: "500 12.5px var(--font-sans)",
          }}><I.Layout size={12}/> Картки</button>
          <button onClick={() => setView("table")} style={{
            display: "inline-flex", alignItems: "center", gap: 6,
            padding: "6px 12px", borderRadius: 5, cursor: "pointer",
            color: view === "table" ? "var(--ink-9)" : "var(--ink-5)",
            background: view === "table" ? "var(--card)" : "transparent",
            boxShadow: view === "table" ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
            font: "500 12.5px var(--font-sans)",
          }}><I.List size={12}/> Таблиця</button>
        </div>
      </header>

      {view === "cards" && (
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 14, marginTop: 20 }}>
          {zones.map(z => <GeoCard key={z.key} zone={z}/>)}
        </div>
      )}

      {view === "table" && <GeoTable zones={zones}/>}
    </div>
  );
};

const GeoCard = ({ zone }) => {
  const [openKind, setOpenKind] = React.useState(null); // 'phone' | 'chat' | null
  const country = zone.key === "WORLD" ? "ZZ" : zone.key;
  const phones = phonesForGeo(country);
  const msgs   = msgsForGeo(country);
  const primaryPh  = phones.find(p => p.role === "primary") || phones[0];
  const backupPh   = phones.filter(p => p !== primaryPh);
  const primaryMsg = msgs.find(m => m.role === "primary") || msgs[0];
  const backupMsg  = msgs.filter(m => m !== primaryMsg);
  const hiddenN    = (DEMO_PHONES.length - phones.length) + (DEMO_MSGS.length - msgs.length);

  return (
    <article className="card" style={{ padding: 18 }}>
      <header style={{ display: "flex", alignItems: "center", gap: 10 }}>
        <span style={{ font: "20px" }}>{zone.flag}</span>
        <span style={{ font: "13.5px var(--font-sans)", color: "var(--ink-9)" }}>{zone.label}</span>
        <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)", letterSpacing: "0.06em" }}>{zone.code}</span>
        <div style={{ flex: 1 }}/>
        <span className="dot dot-ok"/>
      </header>

      {/* Phone primary + reserve toggle icon */}
      <div style={{ marginTop: 18 }}>
        <div className="eyebrow" style={{ fontSize: 10 }}>Телефон</div>
        <div style={{ marginTop: 8, display: "flex", alignItems: "center", gap: 8 }}>
          <I.Phone size={14} style={{ color: "var(--ink-5)" }}/>
          <span className="mono" style={{ font: "400 17px var(--font-mono)", color: "var(--ink-9)", flex: 1 }}>{primaryPh ? primaryPh.n : "—"}</span>
          {backupPh.length > 0 && (
            <button onClick={() => setOpenKind(openKind === "phone" ? null : "phone")} style={{
              display: "inline-flex", alignItems: "center", gap: 4,
              padding: "3px 8px", borderRadius: 999,
              background: openKind === "phone" ? "var(--ink-9)" : "var(--paper-2)",
              color: openKind === "phone" ? "var(--paper)" : "var(--ink-7)",
              boxShadow: openKind === "phone" ? "none" : "inset 0 0 0 1px var(--ink-3)",
              font: "11px var(--font-mono)", cursor: "pointer",
              transition: "all .12s",
            }} title="Показати резервні номери">
              +{backupPh.length} <I.ChevD size={10} style={{ transform: openKind === "phone" ? "rotate(180deg)" : "rotate(0)", transition: "transform .15s" }}/>
            </button>
          )}
        </div>
        <div style={{ marginTop: 4, font: "12px var(--font-sans)", color: "var(--ink-5)", paddingLeft: 22 }}>{primaryPh ? primaryPh.label : ""}</div>

        {openKind === "phone" && (
          <div className="fade-in" style={{ marginTop: 10, marginLeft: 22, padding: "10px 12px", background: "var(--paper-2)", borderRadius: 4, border: "1px solid var(--ink-3)" }}>
            <div className="eyebrow" style={{ fontSize: 10, marginBottom: 8 }}>Резерв · {backupPh.length}</div>
            {backupPh.map((b, k) => (
              <div key={b.id} style={{ display: "flex", alignItems: "center", gap: 8, padding: "5px 0" }}>
                <span className="mono" style={{ font: "10.5px var(--font-mono)", color: "var(--ink-4)", width: 16 }}>#{k + 1}</span>
                <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{b.n}</span>
                <span style={{ font: "11px var(--font-sans)", color: "var(--ink-5)", marginLeft: "auto" }}>{b.label}</span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Messenger primary + reserve toggle icon */}
      {primaryMsg && (
        <div style={{ marginTop: 16, paddingTop: 14, borderTop: "1px solid var(--ink-3)" }}>
          <div className="eyebrow" style={{ fontSize: 10 }}>Месенджер</div>
          <div style={{ marginTop: 8, display: "flex", alignItems: "center", gap: 8 }}>
            <I.Chat size={14} style={{ color: "var(--ink-5)" }}/>
            <span className="mono" style={{ font: "400 14px var(--font-mono)", color: "var(--ink-9)" }}>{primaryMsg.n}</span>
            <span style={{ font: "10.5px var(--font-mono)", padding: "2px 6px", borderRadius: 3, background: "var(--ink-2)", color: "var(--ink-5)" }}>{primaryMsg.kind}</span>
            <div style={{ flex: 1 }}/>
            {backupMsg.length > 0 && (
              <button onClick={() => setOpenKind(openKind === "chat" ? null : "chat")} style={{
                display: "inline-flex", alignItems: "center", gap: 4,
                padding: "3px 8px", borderRadius: 999,
                background: openKind === "chat" ? "var(--ink-9)" : "var(--paper-2)",
                color: openKind === "chat" ? "var(--paper)" : "var(--ink-7)",
                boxShadow: openKind === "chat" ? "none" : "inset 0 0 0 1px var(--ink-3)",
                font: "11px var(--font-mono)", cursor: "pointer",
                transition: "all .12s",
              }} title="Показати резервні месенджери">
                +{backupMsg.length} <I.ChevD size={10} style={{ transform: openKind === "chat" ? "rotate(180deg)" : "rotate(0)", transition: "transform .15s" }}/>
              </button>
            )}
          </div>

          {openKind === "chat" && (
            <div className="fade-in" style={{ marginTop: 10, marginLeft: 22, padding: "10px 12px", background: "var(--paper-2)", borderRadius: 4, border: "1px solid var(--ink-3)" }}>
              <div className="eyebrow" style={{ fontSize: 10, marginBottom: 8 }}>Резерв · {backupMsg.length}</div>
              {backupMsg.map((b, k) => (
                <div key={b.id} style={{ display: "flex", alignItems: "center", gap: 8, padding: "5px 0" }}>
                  <span className="mono" style={{ font: "10.5px var(--font-mono)", color: "var(--ink-4)", width: 16 }}>#{k + 1}</span>
                  <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{b.n}</span>
                  <span style={{ font: "10px var(--font-mono)", padding: "1px 5px", borderRadius: 3, background: "var(--ink-2)", color: "var(--ink-5)", marginLeft: "auto" }}>{b.kind}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {hiddenN > 0 && (
        <div style={{ marginTop: 12, paddingTop: 12, borderTop: "1px solid var(--ink-3)", font: "12px var(--font-sans)", color: "var(--ink-4)", display: "flex", alignItems: "center", gap: 6 }}>
          <I.EyeOff size={11}/>{hiddenN} {hiddenN === 1 ? "запис прихований гео-правилом" : "записів приховано гео-правилом"}
        </div>
      )}
    </article>
  );
};

const GeoTable = ({ zones }) => {
  const [expanded, setExpanded] = React.useState(null); // 'PL-phone', 'PL-chat', etc.

  return (
    <div className="card" style={{ overflow: "hidden", marginTop: 20 }}>
      <div style={{ display: "grid", gridTemplateColumns: "200px 1fr 1fr", gap: 16, padding: "12px 18px", background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)" }}>
        {["Країна", "Телефон", "Месенджер"].map(h => (
          <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h}</span>
        ))}
      </div>
      {zones.map((z, i) => {
        const country = z.key === "WORLD" ? "ZZ" : z.key;
        const phones = phonesForGeo(country);
        const msgs   = msgsForGeo(country);
        const primaryPh  = phones.find(p => p.role === "primary") || phones[0];
        const primaryMsg = msgs.find(m => m.role === "primary") || msgs[0];
        const backupPh   = phones.filter(p => p !== primaryPh);
        const backupMsg  = msgs.filter(m => m !== primaryMsg);
        const phKey = z.key + "-phone";
        const msgKey = z.key + "-chat";
        const phOpen = expanded === phKey;
        const msgOpen = expanded === msgKey;

        return (
          <React.Fragment key={z.key}>
            <div style={{
              display: "grid", gridTemplateColumns: "200px 1fr 1fr",
              gap: 16, padding: "16px 18px",
              borderTop: i ? "1px solid var(--ink-3)" : "none",
              alignItems: "center",
            }}>
              <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                <span style={{ font: "20px" }}>{z.flag}</span>
                <div>
                  <div style={{ font: "13.5px var(--font-sans)", color: "var(--ink-9)" }}>{z.label}</div>
                  <div className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)", marginTop: 2 }}>{z.code}</div>
                </div>
              </div>
              {/* phone column */}
              <div>
                <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
                  <I.Phone size={13} style={{ color: "var(--ink-5)" }}/>
                  <span className="mono" style={{ font: "14px var(--font-mono)", color: "var(--ink-9)", flex: 1 }}>{primaryPh ? primaryPh.n : "—"}</span>
                  {backupPh.length > 0 && (
                    <button onClick={() => setExpanded(phOpen ? null : phKey)} style={{
                      display: "inline-flex", alignItems: "center", gap: 4,
                      padding: "3px 8px", borderRadius: 999,
                      background: phOpen ? "var(--ink-9)" : "var(--paper-2)",
                      color: phOpen ? "var(--paper)" : "var(--ink-7)",
                      boxShadow: phOpen ? "none" : "inset 0 0 0 1px var(--ink-3)",
                      font: "11px var(--font-mono)", cursor: "pointer",
                      transition: "all .12s",
                    }}>+{backupPh.length} <I.ChevD size={10} style={{ transform: phOpen ? "rotate(180deg)" : "rotate(0)", transition: "transform .15s" }}/></button>
                  )}
                </div>
                {primaryPh && <div style={{ marginTop: 3, font: "11.5px var(--font-sans)", color: "var(--ink-5)", paddingLeft: 21 }}>{primaryPh.label}</div>}
              </div>
              {/* msg column */}
              <div>
                {primaryMsg ? (
                  <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
                    <I.Chat size={13} style={{ color: "var(--ink-5)" }}/>
                    <span className="mono" style={{ font: "13px var(--font-mono)", color: "var(--ink-9)" }}>{primaryMsg.n}</span>
                    <span style={{ font: "10.5px var(--font-mono)", padding: "1px 6px", borderRadius: 3, background: "var(--ink-2)", color: "var(--ink-5)" }}>{primaryMsg.kind}</span>
                    <div style={{ flex: 1 }}/>
                    {backupMsg.length > 0 && (
                      <button onClick={() => setExpanded(msgOpen ? null : msgKey)} style={{
                        display: "inline-flex", alignItems: "center", gap: 4,
                        padding: "3px 8px", borderRadius: 999,
                        background: msgOpen ? "var(--ink-9)" : "var(--paper-2)",
                        color: msgOpen ? "var(--paper)" : "var(--ink-7)",
                        boxShadow: msgOpen ? "none" : "inset 0 0 0 1px var(--ink-3)",
                        font: "11px var(--font-mono)", cursor: "pointer",
                        transition: "all .12s",
                      }}>+{backupMsg.length} <I.ChevD size={10} style={{ transform: msgOpen ? "rotate(180deg)" : "rotate(0)", transition: "transform .15s" }}/></button>
                    )}
                  </div>
                ) : <span style={{ font: "12px var(--font-sans)", color: "var(--ink-4)" }}>—</span>}
              </div>
            </div>

            {(phOpen || msgOpen) && (
              <div className="fade-in" style={{
                gridColumn: "1 / -1",
                padding: "12px 18px 16px",
                paddingLeft: phOpen ? 234 : (200 + 16 + 16) + "px",
                background: "var(--paper-2)",
                borderTop: "1px solid var(--ink-3)",
              }}>
                <div className="eyebrow" style={{ fontSize: 10, marginBottom: 8 }}>Резерв · {phOpen ? backupPh.length : backupMsg.length}</div>
                {(phOpen ? backupPh : backupMsg).map((b, k) => (
                  <div key={b.id} style={{ display: "flex", alignItems: "center", gap: 8, padding: "4px 0" }}>
                    <span className="mono" style={{ font: "10.5px var(--font-mono)", color: "var(--ink-4)", width: 16 }}>#{k + 1}</span>
                    {phOpen ? <I.Phone size={11} style={{ color: "var(--ink-4)" }}/> : <I.Chat size={11} style={{ color: "var(--ink-4)" }}/>}
                    <span className="mono" style={{ font: "12.5px var(--font-mono)", color: "var(--ink-7)" }}>{b.n}</span>
                    {!phOpen && b.kind && <span style={{ font: "10px var(--font-mono)", padding: "1px 5px", borderRadius: 3, background: "var(--ink-2)", color: "var(--ink-5)" }}>{b.kind}</span>}
                    <span style={{ font: "11px var(--font-sans)", color: "var(--ink-5)", marginLeft: "auto" }}>{b.label}</span>
                  </div>
                ))}
              </div>
            )}
          </React.Fragment>
        );
      })}
    </div>
  );
};

/* ─── Data tab — interactive, with reserves nested under primary ─── */
const DataTab = () => {
  const [category, setCategory] = React.useState("phones");
  const [geo, setGeo] = React.useState("all");
  const [openId, setOpenId] = React.useState(null);
  const [addReserveFor, setAddReserveFor] = React.useState(null); // primary phone id
  const [expandedPrimary, setExpandedPrimary] = React.useState(new Set([1, 3])); // open by default
  const open = DEMO_PHONES.find(p => p.id === openId);
  const addingTo = DEMO_PHONES.find(p => p.id === addReserveFor);

  const inViewFn = (p) => {
    if (geo === "all") return true;
    if (geo === "PL")    return phonesForGeo("PL").includes(p);
    if (geo === "UA")    return phonesForGeo("UA").includes(p);
    if (geo === "WORLD") return phonesForGeo("ZZ").includes(p);
    return true;
  };

  const primaries = DEMO_PHONES.filter(p => p.parentId === null);
  const reservesOf = (id) => DEMO_PHONES.filter(p => p.parentId === id);
  const allInView = DEMO_PHONES.filter(inViewFn);
  const hiddenN = DEMO_PHONES.length - allInView.length;

  const togglePrimary = (id) => setExpandedPrimary(s => {
    const n = new Set(s);
    if (n.has(id)) n.delete(id); else n.add(id);
    return n;
  });

  const renderRow = (r, isReserve = false, reserveOrder = 0) => {
    const sel = r.id === openId;
    const inView = inViewFn(r);
    return (
      <div key={r.id} onClick={(e) => { e.stopPropagation(); setOpenId(r.id); }} style={{
        display: "grid",
        gridTemplateColumns: isReserve
          ? "24px 28px 1.4fr 1.5fr 1.2fr 110px 60px"
          : "32px 40px 1.4fr 1.5fr 1.2fr 110px 60px",
        gap: 12, alignItems: "center",
        padding: "14px 18px",
        paddingLeft: isReserve ? 50 : (sel ? 16 : 18),
        borderTop: "1px solid var(--ink-3)",
        background: sel ? "var(--accent-soft)" : (isReserve ? "var(--paper-2)" : "transparent"),
        borderLeft: sel ? "2px solid var(--accent)" : "2px solid transparent",
        cursor: "pointer",
        opacity: inView ? 1 : 0.45,
        transition: "background .12s",
      }}>
        {isReserve ? (
          <span style={{ font: "11px var(--font-mono)", color: "var(--ink-4)", letterSpacing: "0.04em" }}>↳</span>
        ) : (
          <I.Drag size={14} style={{ color: "var(--ink-4)" }}/>
        )}
        <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>
          {isReserve ? `#${reserveOrder}` : `#${r.order}`}
        </span>
        <span className="mono" style={{ font: isReserve ? "13.5px var(--font-mono)" : "14.5px var(--font-mono)", color: "var(--ink-9)" }}>{r.n}</span>
        <div>
          <div style={{ font: "13px var(--font-sans)", color: "var(--ink-7)" }}>{r.label}</div>
          {!inView && (
            <div style={{ marginTop: 3, font: "11px var(--font-sans)", color: "var(--ink-4)", display: "flex", alignItems: "center", gap: 4 }}>
              <I.EyeOff size={11}/> Не показується тут
            </div>
          )}
        </div>
        <span style={{ font: "12.5px var(--font-sans)", color: r.geoMode === "all" ? "var(--ink-5)" : "var(--ink-9)" }}>
          {geoLabel(r)}
        </span>
        <span style={{ font: "13px var(--font-sans)" }}>
          {r.role === "primary" && <><span className="dot dot-ok"/>Головний</>}
          {r.role === "backup"  && <><span className="dot dot-info"/>Резерв</>}
          {r.role === "hidden"  && <span style={{ color: "var(--ink-4)" }}><span className="dot"/>Сховано</span>}
        </span>
        <I.Arrow size={13} style={{ color: "var(--ink-4)", justifySelf: "end" }}/>
      </div>
    );
  };

  return (
    <>
      <div style={{ padding: "24px 40px 40px" }}>
        {/* Category tabs */}
        <div style={{ marginBottom: 20 }}>
          <div className="eyebrow" style={{ marginBottom: 10 }}>Категорія даних</div>
          <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
            {[
              { k: "phones",     l: "Телефони",   n: DEMO_PHONES.length, ic: I.Phone, core: true },
              { k: "messengers", l: "Месенджери", n: DEMO_MSGS.length,   ic: I.Chat,  core: true },
              { k: "_sep" },
              { k: "prices",     l: "Ціни",       n: DEMO_PRICES.length, ic: I.Tag,   core: false },
              { k: "addresses",  l: "Адреси",     n: 2, ic: I.Map,   core: false },
              { k: "socials",    l: "Соц. мережі", n: 1, ic: I.Share, core: false },
              { k: "custom",     l: "Custom",     n: 0, ic: I.Plus,  core: false },
            ].map(t => {
              if (t.k === "_sep") return <span key="sep" style={{ width: 1, height: 24, background: "var(--ink-3)", margin: "0 4px", alignSelf: "center" }}/>;
              const a = category === t.k;
              const Ico = t.ic;
              return (
                <button key={t.k} onClick={() => setCategory(t.k)} style={{
                  display: "inline-flex", alignItems: "center", gap: 8,
                  height: 32, padding: "0 12px", borderRadius: 999,
                  background: a ? "var(--ink-9)" : "var(--card)",
                  color: a ? "var(--paper)" : "var(--ink-7)",
                  boxShadow: a ? "none" : "inset 0 0 0 1px var(--ink-3)",
                  font: "13px var(--font-sans)", cursor: "pointer", position: "relative",
                }}>
                  <Ico size={12}/>{t.l}
                  <span className="mono" style={{ font: "10.5px var(--font-mono)", opacity: 0.7 }}>{t.n}</span>
                  {t.core && !a && <span style={{ position: "absolute", top: -2, right: -2, width: 6, height: 6, borderRadius: 999, background: "var(--accent)" }}/>}
                </button>
              );
            })}
          </div>
          <div style={{ marginTop: 8, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>
            <span style={{ display: "inline-block", width: 6, height: 6, borderRadius: 999, background: "var(--accent)", marginRight: 6 }}/>
            <b style={{ color: "var(--ink-7)", fontWeight: 500 }}>Основні</b> — телефони і месенджери. Інші — ціни, адреси, соцмережі.
          </div>
        </div>

        {category === "messengers" && <MessengersTable/>}
        {category === "prices"     && <PricesTable/>}
        {!["phones","messengers","prices"].includes(category) && (
          <div className="card" style={{ padding: 48, textAlign: "center" }}>
            <div className="eyebrow" style={{ marginBottom: 12 }}>{category}</div>
            <div style={{ font: "14px var(--font-sans)", color: "var(--ink-5)", marginBottom: 20 }}>
              Та сама модель: гео-правила, видимість, історія змін.
            </div>
            <button className="btn btn-primary btn-sm"><I.Plus size={12}/> Додати запис</button>
          </div>
        )}

        {category === "phones" && (<>
        {/* Geo selector */}
        <div style={{ display: "flex", alignItems: "center", gap: 14, marginBottom: 20 }}>
          <span className="eyebrow">Перегляд</span>
          <div style={{ display: "flex", gap: 4, padding: 3, background: "var(--ink-2)", borderRadius: 8 }}>
            {[
              { k: "all",   l: "Усі " + DEMO_PHONES.length },
              { k: "WORLD", l: "🌐 Світ" },
              { k: "PL",    l: "🇵🇱 PL" },
              { k: "UA",    l: "🇺🇦 UA" },
            ].map(g => (
              <button key={g.k} onClick={() => setGeo(g.k)} style={{
                padding: "6px 12px", borderRadius: 5, font: "500 12.5px var(--font-sans)",
                color: geo === g.k ? "var(--ink-9)" : "var(--ink-5)",
                background: geo === g.k ? "var(--card)" : "transparent",
                boxShadow: geo === g.k ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
                cursor: "pointer",
              }}>{g.l}</button>
            ))}
          </div>
          <div style={{ flex: 1 }}/>
          {geo !== "all" && (
            <span style={{ font: "12.5px var(--font-mono)", color: "var(--ink-5)" }}>
              {hiddenN > 0 ? `${hiddenN} приховано гео-правилом` : "усі видимі"}
            </span>
          )}
        </div>

        {/* Hint */}
        <div style={{ marginBottom: 20, padding: "14px 18px", borderRadius: 4, background: "var(--accent-soft)", border: "1px solid var(--accent)", color: "#7a3818" }}>
          <div style={{ font: "11px var(--font-mono)", letterSpacing: "0.16em", textTransform: "uppercase", marginBottom: 6, color: "var(--accent)" }}>Як це працює</div>
          <div style={{ font: "13.5px/1.55 var(--font-sans)" }}>
            Резервні номери прив'язані до конкретного <b>головного</b> та показані з відступом під ним. Натисніть <b>+ резерв</b> щоб додати запасний до будь-якого головного.
          </div>
        </div>

        {/* Table — primaries with nested reserves */}
        <div className="card" style={{ overflow: "hidden" }}>
          <div style={{
            display: "grid", gridTemplateColumns: "32px 40px 1.4fr 1.5fr 1.2fr 110px 60px",
            gap: 12, padding: "12px 18px",
            background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)",
          }}>
            {["_d", "_o", "Номер", "Мітка", "Гео-правило", "Роль", "_a"].map(h => (
              <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h.startsWith("_") ? "" : h}</span>
            ))}
          </div>

          {primaries.map(prim => {
            const reserves = reservesOf(prim.id);
            const isExpanded = expandedPrimary.has(prim.id);
            return (
              <React.Fragment key={prim.id}>
                {renderRow(prim)}
                {/* Reserve toggle bar */}
                {reserves.length > 0 && (
                  <div style={{
                    display: "flex", alignItems: "center", gap: 8,
                    padding: "8px 18px 8px 50px",
                    borderTop: "1px solid var(--ink-3)",
                    background: "var(--paper-2)",
                  }}>
                    <button onClick={() => togglePrimary(prim.id)} style={{
                      display: "inline-flex", alignItems: "center", gap: 6,
                      font: "11px var(--font-mono)", color: "var(--ink-5)", letterSpacing: "0.06em", textTransform: "uppercase",
                      cursor: "pointer",
                    }}>
                      <I.ChevD size={11} style={{ transform: isExpanded ? "rotate(0)" : "rotate(-90deg)", transition: "transform .15s" }}/>
                      Резерв · {reserves.length}
                    </button>
                    <div style={{ flex: 1 }}/>
                    <button onClick={(e) => { e.stopPropagation(); setAddReserveFor(prim.id); }} style={{
                      display: "inline-flex", alignItems: "center", gap: 4,
                      padding: "4px 10px", borderRadius: 999,
                      background: "var(--card)",
                      color: "var(--ink-7)",
                      boxShadow: "inset 0 0 0 1px var(--ink-3)",
                      font: "11px var(--font-mono)", cursor: "pointer",
                      transition: "all .12s",
                    }}
                      onMouseEnter={e => { e.currentTarget.style.boxShadow = "inset 0 0 0 1px var(--ink-9)"; e.currentTarget.style.color = "var(--ink-9)"; }}
                      onMouseLeave={e => { e.currentTarget.style.boxShadow = "inset 0 0 0 1px var(--ink-3)"; e.currentTarget.style.color = "var(--ink-7)"; }}
                    >
                      <I.Plus size={11}/> Додати резерв
                    </button>
                  </div>
                )}
                {/* Reserves themselves */}
                {isExpanded && reserves.map((r, k) => renderRow(r, true, k + 1))}
                {/* If no reserves — show add affordance */}
                {reserves.length === 0 && (
                  <button onClick={() => setAddReserveFor(prim.id)} style={{
                    display: "flex", alignItems: "center", gap: 8,
                    width: "100%",
                    padding: "10px 18px 10px 50px",
                    borderTop: "1px solid var(--ink-3)",
                    background: "var(--paper-2)",
                    color: "var(--ink-5)", font: "12px var(--font-sans)",
                    cursor: "pointer",
                  }}>
                    <I.Plus size={11}/> Додати резерв для цього номера
                  </button>
                )}
              </React.Fragment>
            );
          })}

          {/* Standalone hidden phones (no parent, role=hidden) */}
          {DEMO_PHONES.filter(p => p.parentId === null && p.role === "hidden").map(p => renderRow(p))}

          <button style={{ width: "100%", padding: "14px 18px", borderTop: "1px solid var(--ink-3)", background: "transparent", color: "var(--ink-5)", font: "13px var(--font-sans)", display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
            <I.Plus size={13}/> Додати головний номер
          </button>
        </div>
        </>)}
      </div>

      {open && <PhoneDrawer phone={open} onClose={() => setOpenId(null)}/>}
      {addingTo && <AddReserveDrawer parent={addingTo} onClose={() => setAddReserveFor(null)}/>}
    </>
  );
};

/* ─── Messenger kind badge ─── */
const MsgBadge = ({ kind, size = 22 }) => {
  const k = MSG_KINDS[kind] || { color: "var(--ink-5)", short: "??", label: kind };
  return (
    <span title={k.label} style={{
      display: "inline-flex", alignItems: "center", justifyContent: "center",
      width: size, height: size, borderRadius: 5,
      background: k.color, color: "#fff",
      font: `500 ${Math.round(size * 0.42)}px var(--font-mono)`,
      letterSpacing: "0.02em",
      flex: "0 0 auto",
    }}>{k.short}</span>
  );
};

/* ─── Messengers table — same nested-reserve model as phones ─── */
const MessengersTable = () => {
  const [geo, setGeo] = React.useState("all");
  const [openId, setOpenId] = React.useState(null);
  const [expanded, setExpanded] = React.useState(new Set([101, 104]));

  const inViewFn = (m) => {
    if (geo === "all") return true;
    return msgsForGeo(geo === "WORLD" ? "ZZ" : geo).includes(m);
  };
  const primaries = DEMO_MSGS.filter(m => m.parentId === null);
  const reservesOf = (id) => DEMO_MSGS.filter(m => m.parentId === id);
  const allInView = DEMO_MSGS.filter(inViewFn);
  const hiddenN = DEMO_MSGS.length - allInView.length;

  const toggle = (id) => setExpanded(s => { const n = new Set(s); n.has(id) ? n.delete(id) : n.add(id); return n; });

  const Row = ({ r, isReserve = false, orderN = 0 }) => {
    const sel = r.id === openId;
    const inView = inViewFn(r);
    const k = MSG_KINDS[r.kind] || {};
    return (
      <div onClick={() => setOpenId(r.id)} style={{
        display: "grid",
        gridTemplateColumns: isReserve
          ? "24px 28px 32px 1.5fr 1.4fr 1.2fr 110px 60px"
          : "32px 40px 32px 1.5fr 1.4fr 1.2fr 110px 60px",
        gap: 12, alignItems: "center",
        padding: "14px 18px",
        paddingLeft: isReserve ? 50 : (sel ? 16 : 18),
        borderTop: "1px solid var(--ink-3)",
        background: sel ? "var(--accent-soft)" : (isReserve ? "var(--paper-2)" : "transparent"),
        borderLeft: sel ? "2px solid var(--accent)" : "2px solid transparent",
        cursor: "pointer",
        opacity: inView ? 1 : 0.45,
        transition: "background .12s",
      }}>
        {isReserve
          ? <span style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>↳</span>
          : <I.Drag size={14} style={{ color: "var(--ink-4)" }}/>}
        <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>
          {isReserve ? `#${orderN}` : `#${r.order}`}
        </span>
        <MsgBadge kind={r.kind} size={isReserve ? 20 : 24}/>
        <div>
          <span className="mono" style={{ font: isReserve ? "13px var(--font-mono)" : "14.5px var(--font-mono)", color: "var(--ink-9)" }}>{r.n}</span>
          <div style={{ marginTop: 2, font: "11.5px var(--font-sans)", color: "var(--ink-5)" }}>{k.label}</div>
        </div>
        <div>
          <div style={{ font: "13px var(--font-sans)", color: "var(--ink-7)" }}>{r.label}</div>
          {!inView && (
            <div style={{ marginTop: 3, font: "11px var(--font-sans)", color: "var(--ink-4)", display: "flex", alignItems: "center", gap: 4 }}>
              <I.EyeOff size={11}/> Не показується тут
            </div>
          )}
        </div>
        <span style={{ font: "12.5px var(--font-sans)", color: r.geoMode === "all" ? "var(--ink-5)" : "var(--ink-9)" }}>
          {geoLabel(r)}
        </span>
        <span style={{ font: "13px var(--font-sans)" }}>
          {r.role === "primary" && <><span className="dot dot-ok"/>Головний</>}
          {r.role === "backup"  && <><span className="dot dot-info"/>Резерв</>}
          {r.role === "hidden"  && <span style={{ color: "var(--ink-4)" }}><span className="dot"/>Сховано</span>}
        </span>
        <I.Arrow size={13} style={{ color: "var(--ink-4)", justifySelf: "end" }}/>
      </div>
    );
  };

  return (<>
    {/* Geo selector */}
    <div style={{ display: "flex", alignItems: "center", gap: 14, marginBottom: 20 }}>
      <span className="eyebrow">Перегляд</span>
      <div style={{ display: "flex", gap: 4, padding: 3, background: "var(--ink-2)", borderRadius: 8 }}>
        {[
          { k: "all",   l: "Усі " + DEMO_MSGS.length },
          { k: "WORLD", l: "🌐 Світ" },
          { k: "PL",    l: "🇵🇱 PL" },
          { k: "UA",    l: "🇺🇦 UA" },
        ].map(g => (
          <button key={g.k} onClick={() => setGeo(g.k)} style={{
            padding: "6px 12px", borderRadius: 5, font: "500 12.5px var(--font-sans)",
            color: geo === g.k ? "var(--ink-9)" : "var(--ink-5)",
            background: geo === g.k ? "var(--card)" : "transparent",
            boxShadow: geo === g.k ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
            cursor: "pointer",
          }}>{g.l}</button>
        ))}
      </div>
      <div style={{ flex: 1 }}/>
      {geo !== "all" && (
        <span style={{ font: "12.5px var(--font-mono)", color: "var(--ink-5)" }}>
          {hiddenN > 0 ? `${hiddenN} приховано гео-правилом` : "усі видимі"}
        </span>
      )}
    </div>

    {/* Hint */}
    <div style={{ marginBottom: 20, padding: "14px 18px", borderRadius: 4, background: "var(--accent-soft)", border: "1px solid var(--accent)", color: "#7a3818" }}>
      <div style={{ font: "11px var(--font-mono)", letterSpacing: "0.16em", textTransform: "uppercase", marginBottom: 6, color: "var(--accent)" }}>Як це працює</div>
      <div style={{ font: "13.5px/1.55 var(--font-sans)" }}>
        Один <b>головний месенджер</b> на гео-пул, інші — резерв. Можна змішувати Telegram / Viber / WhatsApp в одному пулі — клієнт обере зручний.
      </div>
    </div>

    {/* Mix overview chips */}
    <div style={{ display: "flex", gap: 8, marginBottom: 16, flexWrap: "wrap" }}>
      {Object.entries(MSG_KINDS).map(([k, v]) => {
        const n = DEMO_MSGS.filter(m => m.kind === k && m.visible).length;
        if (n === 0) return null;
        return (
          <span key={k} style={{
            display: "inline-flex", alignItems: "center", gap: 6,
            padding: "5px 10px 5px 6px", borderRadius: 999,
            background: "var(--card)", boxShadow: "inset 0 0 0 1px var(--ink-3)",
            font: "12px var(--font-sans)", color: "var(--ink-7)",
          }}>
            <MsgBadge kind={k} size={18}/>
            {v.label} <span className="mono" style={{ color: "var(--ink-5)", fontSize: 11 }}>{n}</span>
          </span>
        );
      })}
    </div>

    {/* Table */}
    <div className="card" style={{ overflow: "hidden" }}>
      <div style={{
        display: "grid", gridTemplateColumns: "32px 40px 32px 1.5fr 1.4fr 1.2fr 110px 60px",
        gap: 12, padding: "12px 18px",
        background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)",
      }}>
        {["_d", "_o", "_k", "Контакт", "Мітка", "Гео-правило", "Роль", "_a"].map(h => (
          <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h.startsWith("_") ? "" : h}</span>
        ))}
      </div>

      {primaries.map(prim => {
        const reserves = reservesOf(prim.id);
        const isExp = expanded.has(prim.id);
        return (
          <React.Fragment key={prim.id}>
            <Row r={prim}/>
            {reserves.length > 0 && (
              <div style={{ display: "flex", alignItems: "center", gap: 8, padding: "8px 18px 8px 50px", borderTop: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
                <button onClick={() => toggle(prim.id)} style={{
                  display: "inline-flex", alignItems: "center", gap: 6,
                  font: "11px var(--font-mono)", color: "var(--ink-5)", letterSpacing: "0.06em", textTransform: "uppercase",
                  cursor: "pointer",
                }}>
                  <I.ChevD size={11} style={{ transform: isExp ? "rotate(0)" : "rotate(-90deg)", transition: "transform .15s" }}/>
                  Резерв · {reserves.length}
                </button>
                <div style={{ flex: 1 }}/>
                <span style={{ font: "11px var(--font-mono)", color: "var(--ink-5)" }}>
                  {[...new Set(reserves.map(r => r.kind))].map(k => MSG_KINDS[k]?.short).join(" · ")}
                </span>
              </div>
            )}
            {isExp && reserves.map((r, k) => <Row key={r.id} r={r} isReserve orderN={k + 1}/>)}
            {reserves.length === 0 && (
              <button style={{
                display: "flex", alignItems: "center", gap: 8, width: "100%",
                padding: "10px 18px 10px 50px", borderTop: "1px solid var(--ink-3)",
                background: "var(--paper-2)", color: "var(--ink-5)", font: "12px var(--font-sans)", cursor: "pointer",
              }}>
                <I.Plus size={11}/> Додати резерв для цього контакту
              </button>
            )}
          </React.Fragment>
        );
      })}

      {DEMO_MSGS.filter(m => m.parentId === null && m.role === "hidden").map(m => <Row key={m.id} r={m}/>)}

      <button style={{ width: "100%", padding: "14px 18px", borderTop: "1px solid var(--ink-3)", background: "transparent", color: "var(--ink-5)", font: "13px var(--font-sans)", display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
        <I.Plus size={13}/> Додати головний месенджер
      </button>
    </div>
  </>);
};

/* ─── Prices table — multi-currency, grouped by SKU ─── */
const PricesTable = () => {
  const [geo, setGeo] = React.useState("all");
  const [openId, setOpenId] = React.useState(null);

  const skus = [...new Set(DEMO_PRICES.map(p => p.sku))];
  const inViewFn = (p) => {
    if (geo === "all") return true;
    return pricesForGeo(geo === "WORLD" ? "ZZ" : geo).includes(p);
  };
  const allInView = DEMO_PRICES.filter(inViewFn);
  const hiddenN = DEMO_PRICES.length - allInView.length;

  /* totals strip */
  const visibleCount = DEMO_PRICES.filter(p => p.visible).length;
  const currencyMix = [...new Set(DEMO_PRICES.filter(p => p.visible).map(p => p.currency))];

  const Row = ({ p }) => {
    const sel = p.id === openId;
    const inView = inViewFn(p);
    const c = CURRENCIES[p.currency] || {};
    const hasDiscount = p.old && p.old > p.price;
    const free = p.price === 0;
    return (
      <div onClick={() => setOpenId(p.id)} style={{
        display: "grid",
        gridTemplateColumns: "32px 40px 1.4fr 1.2fr 1.3fr 1.2fr 110px 60px",
        gap: 12, alignItems: "center",
        padding: "14px 18px",
        paddingLeft: sel ? 16 : 18,
        borderTop: "1px solid var(--ink-3)",
        background: sel ? "var(--accent-soft)" : "transparent",
        borderLeft: sel ? "2px solid var(--accent)" : "2px solid transparent",
        cursor: "pointer",
        opacity: inView ? 1 : 0.45,
        transition: "background .12s",
      }}>
        <I.Drag size={14} style={{ color: "var(--ink-4)" }}/>
        <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>#{p.order}</span>
        <div>
          <div style={{ font: "13px var(--font-sans)", color: "var(--ink-9)" }}>{p.name}</div>
          <div className="mono" style={{ marginTop: 2, font: "10.5px var(--font-mono)", color: "var(--ink-4)" }}>SKU · {p.sku}</div>
        </div>
        <div style={{ display: "flex", alignItems: "baseline", gap: 8 }}>
          <span className="mono" style={{ font: "400 18px var(--font-mono)", color: free ? "var(--ok)" : "var(--ink-9)" }}>
            {free ? "Безкоштовно" : fmtPrice(p)}
          </span>
          {hasDiscount && (
            <span className="mono" style={{ font: "12px var(--font-mono)", color: "var(--ink-4)", textDecoration: "line-through" }}>
              {fmtPrice({ ...p, price: p.old })}
            </span>
          )}
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span style={{ font: "16px" }}>{c.flag}</span>
          <span className="mono" style={{ font: "12px var(--font-mono)", color: "var(--ink-7)" }}>{p.currency}</span>
          <span style={{ font: "11.5px var(--font-sans)", color: "var(--ink-5)" }}>{p.unit}</span>
        </div>
        <span style={{ font: "12.5px var(--font-sans)", color: p.geoMode === "all" ? "var(--ink-5)" : "var(--ink-9)" }}>
          {geoLabel(p)}
        </span>
        <span style={{ font: "13px var(--font-sans)" }}>
          {p.role === "primary" && <><span className="dot dot-ok"/>Активна</>}
          {p.role === "hidden"  && <span style={{ color: "var(--ink-4)" }}><span className="dot"/>Архів</span>}
        </span>
        <I.Arrow size={13} style={{ color: "var(--ink-4)", justifySelf: "end" }}/>
      </div>
    );
  };

  return (<>
    {/* Geo + currency strip */}
    <div style={{ display: "flex", alignItems: "center", gap: 14, marginBottom: 20, flexWrap: "wrap" }}>
      <span className="eyebrow">Перегляд</span>
      <div style={{ display: "flex", gap: 4, padding: 3, background: "var(--ink-2)", borderRadius: 8 }}>
        {[
          { k: "all",   l: "Усі " + DEMO_PRICES.length },
          { k: "WORLD", l: "🌐 Світ" },
          { k: "PL",    l: "🇵🇱 PL" },
          { k: "UA",    l: "🇺🇦 UA" },
        ].map(g => (
          <button key={g.k} onClick={() => setGeo(g.k)} style={{
            padding: "6px 12px", borderRadius: 5, font: "500 12.5px var(--font-sans)",
            color: geo === g.k ? "var(--ink-9)" : "var(--ink-5)",
            background: geo === g.k ? "var(--card)" : "transparent",
            boxShadow: geo === g.k ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
            cursor: "pointer",
          }}>{g.l}</button>
        ))}
      </div>
      <div style={{ flex: 1 }}/>
      <span style={{ font: "12.5px var(--font-mono)", color: "var(--ink-5)" }}>
        {visibleCount} активних · {currencyMix.length} валют
      </span>
      {geo !== "all" && (
        <span style={{ font: "12.5px var(--font-mono)", color: "var(--ink-5)" }}>
          · {hiddenN > 0 ? `${hiddenN} приховано` : "усі видимі"}
        </span>
      )}
    </div>

    {/* Hint */}
    <div style={{ marginBottom: 20, padding: "14px 18px", borderRadius: 4, background: "var(--accent-soft)", border: "1px solid var(--accent)", color: "#7a3818" }}>
      <div style={{ font: "11px var(--font-mono)", letterSpacing: "0.16em", textTransform: "uppercase", marginBottom: 6, color: "var(--accent)" }}>Multi-currency</div>
      <div style={{ font: "13.5px/1.55 var(--font-sans)" }}>
        Один <b>SKU</b>, кілька цін під різні гео — клієнт у Польщі бачить PLN, у Україні — UAH, решта світу — EUR/USD. Стара ціна показується як <span style={{ textDecoration: "line-through" }}>перекреслена</span>.
      </div>
    </div>

    {/* Table grouped by SKU */}
    <div className="card" style={{ overflow: "hidden" }}>
      <div style={{
        display: "grid", gridTemplateColumns: "32px 40px 1.4fr 1.2fr 1.3fr 1.2fr 110px 60px",
        gap: 12, padding: "12px 18px",
        background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)",
      }}>
        {["_d", "_o", "Назва · SKU", "Ціна", "Валюта · одиниця", "Гео-правило", "Статус", "_a"].map(h => (
          <span key={h} className="eyebrow" style={{ fontSize: 10 }}>{h.startsWith("_") ? "" : h}</span>
        ))}
      </div>

      {skus.map((sku, i) => {
        const rows = DEMO_PRICES.filter(p => p.sku === sku);
        return (
          <React.Fragment key={sku}>
            <div style={{
              display: "flex", alignItems: "center", gap: 10,
              padding: "10px 18px",
              borderTop: i ? "1px solid var(--ink-3)" : "none",
              background: "var(--paper-2)",
            }}>
              <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)", letterSpacing: "0.06em" }}>SKU</span>
              <span className="mono" style={{ font: "12px var(--font-mono)", color: "var(--ink-9)" }}>{sku}</span>
              <span style={{ font: "12.5px var(--font-sans)", color: "var(--ink-5)" }}>· {rows[0].name}</span>
              <div style={{ flex: 1 }}/>
              <span style={{ font: "11px var(--font-mono)", color: "var(--ink-5)" }}>
                {rows.length} {rows.length === 1 ? "ціна" : "цін"}
              </span>
            </div>
            {rows.map(p => <Row key={p.id} p={p}/>)}
          </React.Fragment>
        );
      })}

      <button style={{ width: "100%", padding: "14px 18px", borderTop: "1px solid var(--ink-3)", background: "transparent", color: "var(--ink-5)", font: "13px var(--font-sans)", display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
        <I.Plus size={13}/> Додати ціну / новий SKU
      </button>
    </div>
  </>);
};

/* ─── Add reserve drawer ─── */
const AddReserveDrawer = ({ parent, onClose }) => {
  const [n, setN] = React.useState("");
  const [label, setLabel] = React.useState("");
  return (
    <Drawer
      title="Додати резерв"
      sub={<>До головного <span className="mono" style={{ color: "var(--ink-9)" }}>{parent.n}</span> · {parent.label}</>}
      onClose={onClose}
      footer={<>
        <button className="btn btn-ghost" onClick={onClose}>Скасувати</button>
        <button className="btn btn-primary" onClick={onClose}>Додати резерв</button>
      </>}
    >
      <div style={{ display: "flex", flexDirection: "column", gap: 28 }}>
        {/* Context — visual link to parent */}
        <div style={{ padding: "14px 16px", borderRadius: 4, background: "var(--paper-2)", border: "1px solid var(--ink-3)" }}>
          <div className="eyebrow" style={{ fontSize: 10, marginBottom: 8 }}>Резерв для</div>
          <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
            <I.Phone size={14} style={{ color: "var(--ink-5)" }}/>
            <span className="mono" style={{ font: "400 16px var(--font-mono)", color: "var(--ink-9)" }}>{parent.n}</span>
            <span style={{ font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{parent.label}</span>
          </div>
          <div style={{ marginTop: 8, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>
            Гео-правило: {geoLabel(parent)}
          </div>
        </div>

        <div>
          <label className="label">Номер резерву</label>
          <input className="input mono" placeholder="+48 ..." value={n} onChange={e => setN(e.target.value)} style={{ font: "400 20px var(--font-mono)" }}/>
        </div>

        <div>
          <label className="label">Мітка</label>
          <input className="input" placeholder="напр. PL резерв · 2" value={label} onChange={e => setLabel(e.target.value)}/>
        </div>

        <div>
          <label className="label">Порядок у черзі</label>
          <div style={{ display: "flex", gap: 6 }}>
            {[1, 2, 3, 4].map(o => (
              <button key={o} style={{
                width: 40, height: 40, borderRadius: 999,
                border: "1px solid " + (o === 2 ? "var(--ink-9)" : "var(--ink-3)"),
                background: o === 2 ? "var(--ink-9)" : "transparent",
                color: o === 2 ? "var(--paper)" : "var(--ink-7)",
                font: "13px var(--font-mono)", cursor: "pointer",
              }}>#{o}</button>
            ))}
          </div>
          <div style={{ marginTop: 8, font: "12px var(--font-sans)", color: "var(--ink-5)" }}>
            Який за порядком спрацює при failover. #1 — перший резерв.
          </div>
        </div>

        <div>
          <label className="label">Успадкувати від головного</label>
          <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
            {[
              { l: "Гео-правило", v: geoLabel(parent), on: true },
              { l: "Видимість",    v: "Видимий",          on: true },
            ].map((t, i) => (
              <label key={i} style={{ display: "flex", alignItems: "center", gap: 12, padding: "10px 12px", borderRadius: 4, border: "1px solid var(--ink-3)", cursor: "pointer" }}>
                <span style={{
                  width: 16, height: 16, borderRadius: 3,
                  background: t.on ? "var(--ink-9)" : "transparent",
                  border: "1px solid " + (t.on ? "var(--ink-9)" : "var(--ink-3)"),
                  display: "inline-flex", alignItems: "center", justifyContent: "center",
                  color: "var(--paper)", flex: "0 0 auto",
                }}>{t.on && <I.Check size={10} stroke={2.5}/>}</span>
                <span style={{ flex: 1, font: "13.5px var(--font-sans)", color: "var(--ink-9)" }}>{t.l}</span>
                <span className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>{t.v}</span>
              </label>
            ))}
          </div>
        </div>
      </div>
    </Drawer>
  );
};

/* ─── Phone editor drawer ─── */
const PhoneDrawer = ({ phone, onClose }) => {
  const [geoMode, setGeoMode] = React.useState(phone.geoMode);
  const [role,    setRole]    = React.useState(phone.role);

  return (
    <Drawer
      title="Редагувати телефон"
      sub={<>{phone.n} · {phone.label}</>}
      onClose={onClose}
      footer={<>
        <button className="btn btn-ghost" onClick={onClose}>Скасувати</button>
        <button className="btn btn-primary" onClick={onClose}>Зберегти</button>
      </>}
    >
      <div style={{ display: "flex", flexDirection: "column", gap: 28 }}>
        <div>
          <label className="label">Номер</label>
          <input className="input mono" defaultValue={phone.n} style={{ font: "400 20px var(--font-mono)" }}/>
        </div>
        <div>
          <label className="label">Мітка</label>
          <input className="input" defaultValue={phone.label}/>
        </div>

        <div>
          <label className="label">Гео-правило</label>
          <p style={{ marginTop: -4, marginBottom: 12, font: "12.5px/1.5 var(--font-sans)", color: "var(--ink-5)" }}>
            Сайт показує номер лише тим відвідувачам, що відповідають правилу.
          </p>
          <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
            {[
              { k: "all",    l: "🌐 Усім", d: "Будь-яка країна" },
              { k: "only",   l: "🇵🇱 Тільки в обраних", d: "Лише вказаним країнам" },
              { k: "except", l: "🇺🇦 Крім обраних", d: "Усім окрім вказаних" },
            ].map(g => {
              const sel = geoMode === g.k;
              return (
                <label key={g.k} onClick={() => setGeoMode(g.k)} style={{ display: "flex", alignItems: "flex-start", gap: 12, padding: "12px 14px", borderRadius: 4, border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"), background: sel ? "var(--paper-2)" : "transparent", cursor: "pointer" }}>
                  <span style={{ width: 14, height: 14, marginTop: 3, borderRadius: 999, border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"), display: "inline-flex", alignItems: "center", justifyContent: "center", flex: "0 0 auto" }}>
                    {sel && <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--ink-9)" }}/>}
                  </span>
                  <div>
                    <div style={{ font: "13.5px var(--font-sans)", color: sel ? "var(--ink-9)" : "var(--ink-7)" }}>{g.l}</div>
                    <div style={{ marginTop: 3, font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{g.d}</div>
                  </div>
                </label>
              );
            })}
          </div>

          {/* Country picker when only/except */}
          {(geoMode === "only" || geoMode === "except") && (
            <div style={{ marginTop: 14 }}>
              <div className="eyebrow" style={{ fontSize: 10, marginBottom: 10 }}>Країни</div>
              <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
                {Object.entries(COUNTRIES).map(([code, c]) => {
                  const sel = phone.countries.includes(code);
                  return (
                    <button key={code} style={{
                      display: "inline-flex", alignItems: "center", gap: 6,
                      height: 28, padding: "0 12px", borderRadius: 999,
                      background: sel ? "var(--ink-9)" : "var(--card)",
                      color: sel ? "var(--paper)" : "var(--ink-7)",
                      boxShadow: sel ? "none" : "inset 0 0 0 1px var(--ink-3)",
                      font: "12.5px var(--font-sans)", cursor: "pointer",
                    }}>
                      <span>{c.flag}</span>
                      <span>{code}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        <div>
          <label className="label">Роль</label>
          <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
            {[
              { k: "primary", l: "Головний", d: "Показуємо першим" },
              { k: "backup",  l: "Резерв",   d: "На випадок блокування primary" },
              { k: "hidden",  l: "Сховано",  d: "У базі, але не показуємо" },
            ].map(r => {
              const sel = role === r.k;
              return (
                <label key={r.k} onClick={() => setRole(r.k)} style={{ display: "flex", alignItems: "flex-start", gap: 12, padding: "12px 14px", borderRadius: 4, border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"), background: sel ? "var(--paper-2)" : "transparent", cursor: "pointer" }}>
                  <span style={{ width: 14, height: 14, marginTop: 3, borderRadius: 999, border: "1px solid " + (sel ? "var(--ink-9)" : "var(--ink-3)"), display: "inline-flex", alignItems: "center", justifyContent: "center", flex: "0 0 auto" }}>
                    {sel && <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--ink-9)" }}/>}
                  </span>
                  <div>
                    <div style={{ font: "13.5px var(--font-sans)", color: sel ? "var(--ink-9)" : "var(--ink-7)" }}>{r.l}</div>
                    <div style={{ marginTop: 3, font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{r.d}</div>
                  </div>
                </label>
              );
            })}
          </div>
        </div>
      </div>
    </Drawer>
  );
};

/* ─── Activity tab — detailed change log with before/after ─── */
const ActivityTab = () => {
  const [filter, setFilter] = React.useState("all");
  const events = [
    {
      type: "update", icon: "edit", scope: "Phone",
      who: { name: "Test Admin", avatar: "TA", role: "admin" },
      target: "+48 22 555 33 11",
      date: "13 трав 2026 · 18:54:23",
      ago: "5 хв тому",
      ip: "194.30.122.18",
      changes: [
        { field: "Роль",   from: "Головний",  to: "Резерв" },
        { field: "Порядок", from: "—",         to: "#2" },
        { field: "Прив'язано до", from: "—", to: "+48 00 000 00 00" },
      ],
    },
    {
      type: "failover", icon: "bolt", scope: "Failover",
      who: { name: "System · auto", avatar: "S", role: "system" },
      target: "+48 00 000 00 00",
      date: "13 трав 2026 · 18:42:11",
      ago: "12 хв тому",
      reason: "SIM block detected · health check failed (3/3 retries)",
      changes: [
        { field: "Активний номер", from: "+48 00 000 00 00", to: "+48 99 999 99 99" },
        { field: "Causa",           from: "—",                  to: "auto-failover · cascade #1" },
      ],
    },
    {
      type: "create", icon: "plus", scope: "Messenger",
      who: { name: "Olha Boyko", avatar: "OB", role: "manager" },
      target: "@demo_backup",
      date: "13 трав 2026 · 17:50:08",
      ago: "1 год тому",
      ip: "94.158.61.4",
      changes: [
        { field: "Платформа", from: null, to: "Telegram" },
        { field: "Гео",        from: null, to: "Тільки UA" },
        { field: "Роль",       from: null, to: "Резерв" },
      ],
    },
    {
      type: "alert", icon: "bolt", scope: "System",
      who: { name: "System", avatar: "S", role: "system" },
      target: "+48 00 000 00 00",
      date: "13 трав 2026 · 03:22:00",
      ago: "вчора",
      reason: "Перший SIM block · 200ms timeout × 3 retries",
      changes: [
        { field: "Стан", from: "online", to: "blocked" },
      ],
    },
    {
      type: "delete", icon: "trash", scope: "Phone",
      who: { name: "Іван Петренко", avatar: "ІП", role: "admin" },
      target: "061 333-22-11",
      date: "12 трав 2026 · 14:11:32",
      ago: "вчора",
      ip: "194.30.122.18",
      changes: [
        { field: "Видалено", from: "061 333-22-11", to: null },
      ],
    },
  ];

  const filtered = filter === "all" ? events : events.filter(e => e.type === filter);

  const TypeIcon = ({ type, size = 12 }) => {
    if (type === "edit")  return <I.Edit size={size}/>;
    if (type === "bolt")  return <I.Bolt size={size}/>;
    if (type === "plus")  return <I.Plus size={size}/>;
    if (type === "trash") return <I.Trash size={size}/>;
    return <I.Edit size={size}/>;
  };
  const typeColor = (t) => ({
    update:   "var(--info)",
    failover: "var(--bad)",
    create:   "var(--ok)",
    alert:    "var(--warn)",
    delete:   "var(--bad)",
  }[t] || "var(--ink-5)");
  const typeBg = (t) => ({
    update:   "var(--info-soft)",
    failover: "var(--bad-soft)",
    create:   "var(--ok-soft)",
    alert:    "var(--warn-soft)",
    delete:   "var(--bad-soft)",
  }[t] || "var(--ink-2)");
  const typeLabel = (t) => ({
    update: "Зміна", failover: "Failover", create: "Створено", alert: "Сповіщення", delete: "Видалено",
  }[t]);

  return (
    <div style={{ padding: "32px 40px 64px" }}>
      <header style={{ display: "flex", alignItems: "flex-end", marginBottom: 24 }}>
        <div style={{ flex: 1 }}>
          <h3 style={{ font: "400 22px/1 var(--font-sans)", color: "var(--ink-9)" }}>Журнал змін</h3>
          <p style={{ marginTop: 8, font: "14px var(--font-sans)", color: "var(--ink-5)" }}>
            Усі зміни на сайті — старі дані, нові, хто змінив і коли.
          </p>
        </div>
        <button className="btn btn-secondary btn-sm"><I.Export size={12}/> Експорт</button>
      </header>

      {/* Type filter */}
      <div style={{ display: "flex", gap: 6, marginBottom: 20, flexWrap: "wrap" }}>
        {[
          { k: "all",      l: "Усі",      n: events.length },
          { k: "update",   l: "Зміни",    n: events.filter(e => e.type === "update").length },
          { k: "failover", l: "Failover", n: events.filter(e => e.type === "failover").length },
          { k: "create",   l: "Створення", n: events.filter(e => e.type === "create").length },
          { k: "delete",   l: "Видалення", n: events.filter(e => e.type === "delete").length },
          { k: "alert",    l: "Сповіщення", n: events.filter(e => e.type === "alert").length },
        ].map(f => (
          <button key={f.k} onClick={() => setFilter(f.k)} style={{
            display: "inline-flex", alignItems: "center", gap: 6,
            height: 30, padding: "0 12px", borderRadius: 999,
            background: filter === f.k ? "var(--ink-9)" : "var(--card)",
            color: filter === f.k ? "var(--paper)" : "var(--ink-7)",
            boxShadow: filter === f.k ? "none" : "inset 0 0 0 1px var(--ink-3)",
            font: "12.5px var(--font-sans)", cursor: "pointer",
          }}>
            {f.l}
            <span className="mono" style={{ font: "10.5px var(--font-mono)", opacity: 0.7 }}>{f.n}</span>
          </button>
        ))}
      </div>

      {/* Timeline */}
      <div style={{ position: "relative" }}>
        {/* Vertical timeline line */}
        <div style={{ position: "absolute", left: 19, top: 24, bottom: 24, width: 1, background: "var(--ink-3)" }}/>

        {filtered.map((e, i) => (
          <article key={i} className="card" style={{ position: "relative", padding: 0, marginBottom: 12, marginLeft: 48, overflow: "hidden" }}>
            {/* Timeline marker */}
            <span style={{
              position: "absolute", left: -38, top: 18,
              width: 28, height: 28, borderRadius: 999,
              background: typeBg(e.type), color: typeColor(e.type),
              display: "inline-flex", alignItems: "center", justifyContent: "center",
              border: "2px solid var(--paper)",
              zIndex: 1,
            }}>
              <TypeIcon type={e.icon} size={13}/>
            </span>

            {/* Header */}
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

            {/* Changes — before/after */}
            <div style={{ padding: "16px 18px" }}>
              <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                {e.changes.map((c, k) => (
                  <div key={k} style={{ display: "grid", gridTemplateColumns: "140px 1fr 20px 1fr", gap: 12, alignItems: "center" }}>
                    <span className="eyebrow" style={{ fontSize: 10 }}>{c.field}</span>
                    {/* Before */}
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
                    {/* After */}
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

            {/* Footer — actor + meta */}
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

        <div style={{ textAlign: "center", marginTop: 20 }}>
          <button className="btn btn-secondary btn-sm">Завантажити старіші</button>
        </div>
      </div>
    </div>
  );
};

/* ─── Settings tab ─── */
const SettingsTab = () => {
  const [autoFailover, setAutoFailover] = React.useState(true);
  const [healthEvery,  setHealthEvery]  = React.useState("5min");
  const [threshold,    setThreshold]    = React.useState(3);
  const [notifyOn,     setNotifyOn]     = React.useState(true);

  /* per-site categories — defaults inherited from workspace */
  const [cats, setCats] = React.useState({
    phones: true, messengers: true, prices: true, addresses: true, socials: false, custom: false,
  });
  const toggleCat = (k) => setCats(c => ({ ...c, [k]: !c[k] }));

  const failoverLog = [
    { from: "+48 00 000 00 00",  to: "+48 99 999 99 99",   geo: "PL", reason: "manual · SIM block",     by: "Test Admin",  when: "13 трав 18:54", auto: false, ok: true },
    { from: "11111111111",        to: "+099 11 22 33",     geo: "UA", reason: "auto · 5xx · 3/3",        by: "System",       when: "10 трав 03:22", auto: true,  ok: true },
    { from: "+48 22 555 33 11",   to: "+48 71 222 11 00",   geo: "PL", reason: "auto · timeout · 3/3",    by: "System",       when: "08 трав 21:09", auto: true,  ok: true },
    { from: "@demo_main",          to: "@demo_support",     geo: "🌐", reason: "manual · перевірка",       by: "Olha B.",      when: "05 трав 12:40", auto: false, ok: true },
    { from: "+38 099 11 22 33",   to: "+38 073 000 11 22", geo: "UA", reason: "auto · no-answer · 3/3",   by: "System",       when: "02 трав 09:18", auto: true,  ok: false },
  ];

  const [sub, setSub] = React.useState("failover");
  const subTabs = [
    { k: "failover",   l: "Failover",        ic: I.Bolt,   n: 5 },
    { k: "categories", l: "Категорії даних", ic: I.Layout, n: 6 },
    { k: "api",        l: "API доступ",      ic: I.Key,    n: 1 },
  ];

  return (
    <div style={{ padding: "24px 40px 64px" }}>
      {/* Sub-tab nav */}
      <div style={{ display: "flex", gap: 2, marginBottom: 32, borderBottom: "1px solid var(--ink-3)" }}>
        {subTabs.map(t => {
          const a = sub === t.k;
          const Ic = t.ic;
          return (
            <button key={t.k} onClick={() => setSub(t.k)} style={{
              display: "inline-flex", alignItems: "center", gap: 8,
              padding: "12px 18px", marginBottom: -1,
              borderBottom: a ? "2px solid var(--ink-9)" : "2px solid transparent",
              color: a ? "var(--ink-9)" : "var(--ink-5)",
              font: a ? "500 13.5px var(--font-sans)" : "13.5px var(--font-sans)",
              cursor: "pointer", background: "transparent",
              transition: "color .12s",
            }}
              onMouseEnter={e => { if (!a) e.currentTarget.style.color = "var(--ink-9)"; }}
              onMouseLeave={e => { if (!a) e.currentTarget.style.color = "var(--ink-5)"; }}
            >
              <Ic size={13}/>{t.l}
              <span className="mono" style={{ font: "10.5px var(--font-mono)", color: "var(--ink-4)" }}>{t.n}</span>
            </button>
          );
        })}
      </div>

      <div style={{ maxWidth: 980 }}>

      {/* ─── Failover ─── */}
      {sub === "failover" && (
      <section>
        <header style={{ marginBottom: 24 }}>
          <div className="eyebrow" style={{ marginBottom: 8 }}>01 · Failover</div>
          <h3 style={{ font: "400 24px/1.15 var(--font-sans)", color: "var(--ink-9)" }}>SIM-керування та автоматичне перемикання</h3>
          <p style={{ marginTop: 8, font: "13.5px/1.55 var(--font-sans)", color: "var(--ink-5)", maxWidth: 620 }}>
            Якщо головний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …). Гео-правила резерву мають збігатися з головним.
          </p>
        </header>

        {/* Status strip */}
        <div className="card" style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", marginBottom: 20 }}>
          {[
            { l: "Статус",          v: <><span className="dot dot-ok"/>Стабільно</>, m: "усі головні відповідають" },
            { l: "Активних правил", v: "4",                                          m: "у 2 гео-пулах" },
            { l: "Резервів",         v: "5",                                          m: "у середньому 1.25 / пул" },
            { l: "Останній failover", v: "13 трав",                                  m: "manual · PL пул" },
          ].map((s, i) => (
            <div key={i} style={{
              padding: "16px 20px",
              borderLeft: i ? "1px solid var(--ink-3)" : "none",
            }}>
              <div className="eyebrow" style={{ fontSize: 10, marginBottom: 8 }}>{s.l}</div>
              <div style={{ font: "400 18px var(--font-sans)", color: "var(--ink-9)", display: "flex", alignItems: "center", gap: 6 }}>{s.v}</div>
              <div style={{ marginTop: 4, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>{s.m}</div>
            </div>
          ))}
        </div>

        {/* Configuration grid */}
        <div className="card" style={{ overflow: "hidden", marginBottom: 20 }}>
          <header style={{ padding: "14px 20px", display: "flex", alignItems: "center", gap: 12, borderBottom: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
            <span className="eyebrow">Правила перемикання</span>
            <div style={{ flex: 1 }}/>
            <button className="btn btn-accent btn-sm"><I.Bolt size={12}/> Тригер вручну</button>
          </header>

          {[
            {
              l: "Авто-failover",
              d: "Перемикати на резерв без участі оператора, коли health-check провалює поріг.",
              c: <Toggle on={autoFailover} onClick={() => setAutoFailover(v => !v)}/>,
            },
            {
              l: "Інтервал перевірки",
              d: "Як часто пінгуємо головний номер. Менший інтервал = швидша реакція, більше навантаження.",
              c: (
                <div style={{ display: "flex", gap: 4, padding: 3, background: "var(--ink-2)", borderRadius: 6 }}>
                  {[
                    { k: "1min", l: "1 хв" },
                    { k: "5min", l: "5 хв" },
                    { k: "15min", l: "15 хв" },
                  ].map(o => (
                    <button key={o.k} onClick={() => setHealthEvery(o.k)} style={{
                      padding: "5px 12px", borderRadius: 4, font: "500 12px var(--font-sans)",
                      color: healthEvery === o.k ? "var(--ink-9)" : "var(--ink-5)",
                      background: healthEvery === o.k ? "var(--card)" : "transparent",
                      boxShadow: healthEvery === o.k ? "0 1px 1px rgba(0,0,0,0.04)" : "none",
                      cursor: "pointer",
                    }}>{o.l}</button>
                  ))}
                </div>
              ),
            },
            {
              l: "Поріг провалів",
              d: "Скільки невдалих перевірок підряд має статись перед перемиканням.",
              c: (
                <div style={{ display: "flex", gap: 6 }}>
                  {[2, 3, 5, 10].map(n => (
                    <button key={n} onClick={() => setThreshold(n)} style={{
                      width: 36, height: 32, borderRadius: 4,
                      border: "1px solid " + (threshold === n ? "var(--ink-9)" : "var(--ink-3)"),
                      background: threshold === n ? "var(--ink-9)" : "transparent",
                      color: threshold === n ? "var(--paper)" : "var(--ink-7)",
                      font: "13px var(--font-mono)", cursor: "pointer",
                    }}>{n}</button>
                  ))}
                  <span style={{ alignSelf: "center", font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>підряд</span>
                </div>
              ),
            },
            {
              l: "Сповіщення",
              d: "Слати email + webhook коли спрацював failover.",
              c: <Toggle on={notifyOn} onClick={() => setNotifyOn(v => !v)}/>,
            },
          ].map((r, i) => (
            <div key={i} style={{
              display: "grid", gridTemplateColumns: "minmax(0, 1.4fr) minmax(220px, auto)",
              gap: 32, padding: "18px 20px",
              borderTop: i ? "1px solid var(--ink-3)" : "none",
              alignItems: "center",
            }}>
              <div>
                <div style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{r.l}</div>
                <div style={{ marginTop: 4, font: "12.5px/1.5 var(--font-sans)", color: "var(--ink-5)" }}>{r.d}</div>
              </div>
              <div style={{ justifySelf: "end" }}>{r.c}</div>
            </div>
          ))}
        </div>

        {/* Log table — aligned columns */}
        <div className="card" style={{ overflow: "hidden" }}>
          <header style={{ padding: "14px 20px", display: "flex", alignItems: "center", gap: 12, borderBottom: "1px solid var(--ink-3)", background: "var(--paper-2)" }}>
            <span className="eyebrow">Журнал перемикань</span>
            <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-4)" }}>{failoverLog.length} подій · 30 днів</span>
            <div style={{ flex: 1 }}/>
            <button className="btn btn-ghost btn-sm" style={{ height: 26, padding: "0 10px", fontSize: 11 }}>Експорт CSV</button>
          </header>

          <div style={{
            display: "grid",
            gridTemplateColumns: "60px minmax(0, 1fr) 24px minmax(0, 1fr) 1fr 90px 100px",
            gap: 16, padding: "10px 20px",
            background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)",
          }}>
            {["Гео", "З", "_", "На", "Причина", "Коли", "_"].map((h, i) => (
              <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>

          {failoverLog.map((f, i) => (
            <div key={i} style={{
              display: "grid",
              gridTemplateColumns: "60px minmax(0, 1fr) 24px minmax(0, 1fr) 1fr 90px 100px",
              gap: 16, alignItems: "center", padding: "14px 20px",
              borderTop: "1px solid var(--ink-3)",
            }}>
              <span style={{ font: "14px var(--font-sans)" }}>{f.geo}</span>
              <span className="mono" style={{
                font: "13px var(--font-mono)", color: "var(--ink-4)",
                textDecoration: "line-through",
                overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap",
              }}>{f.from}</span>
              <I.Arrow size={13} style={{ color: "var(--ink-5)" }}/>
              <span className="mono" style={{
                font: "13px var(--font-mono)", color: "var(--ink-9)",
                overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap",
              }}>{f.to}</span>
              <div style={{ display: "flex", alignItems: "center", gap: 6, minWidth: 0 }}>
                <span style={{
                  flex: "0 0 auto",
                  font: "9.5px var(--font-mono)", letterSpacing: "0.06em", textTransform: "uppercase",
                  padding: "2px 6px", borderRadius: 3,
                  background: f.auto ? "var(--ink-9)" : "var(--accent-soft)",
                  color: f.auto ? "var(--paper)" : "var(--accent)",
                }}>{f.auto ? "AUTO" : "MANUAL"}</span>
                <span style={{
                  font: "12px var(--font-mono)", color: "var(--ink-5)",
                  overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap",
                }}>{f.reason}</span>
              </div>
              <span className="mono" style={{ font: "11.5px var(--font-mono)", color: "var(--ink-4)" }}>{f.when}</span>
              <div style={{ display: "flex", justifyContent: "flex-end", gap: 6 }}>
                {f.ok
                  ? <span style={{ font: "11px var(--font-mono)", color: "var(--ok)" }}>✓ rollback</span>
                  : <button className="btn btn-ghost btn-sm" style={{ height: 24, padding: "0 8px", fontSize: 11 }}>Rollback</button>}
              </div>
            </div>
          ))}
        </div>
      </section>
      )}

      {/* ─── Per-site categories ─── */}
      {sub === "categories" && (
      <section>
        <header style={{ marginBottom: 24 }}>
          <div className="eyebrow" style={{ marginBottom: 8 }}>02 · Категорії даних</div>
          <h3 style={{ font: "400 24px/1.15 var(--font-sans)", color: "var(--ink-9)" }}>Що саме зберігаємо для цього сайту</h3>
          <p style={{ marginTop: 8, font: "13.5px/1.55 var(--font-sans)", color: "var(--ink-5)", maxWidth: 620 }}>
            Категорії беруться з <button onClick={() => {}} style={{ color: "var(--ink-9)", textDecoration: "underline", textDecorationColor: "var(--ink-4)", textUnderlineOffset: 3, cursor: "pointer" }}>загальних налаштувань workspace</button>, але кожен сайт вмикає лише потрібне. Вимкнена тут категорія не з'являється у вкладці <b style={{ fontWeight: 500, color: "var(--ink-9)" }}>Дані</b>.
          </p>
        </header>

        <div className="card" style={{ overflow: "hidden" }}>
          <div style={{
            display: "grid", gridTemplateColumns: "44px minmax(0, 1.6fr) 80px minmax(0, 1.2fr) 60px",
            gap: 16, padding: "10px 20px",
            background: "var(--paper-2)", borderBottom: "1px solid var(--ink-3)",
          }}>
            {["_", "Категорія", "Записів", "Джерело", "_"].map((h, i) => (
              <span key={i} className="eyebrow" style={{ fontSize: 10 }}>{h === "_" ? "" : h}</span>
            ))}
          </div>

          {[
            { id: "phones",     label: "Телефони",        icon: I.Phone, n: DEMO_PHONES.length,    src: "Workspace · обов'язкова",  required: true,  core: true },
            { id: "messengers", label: "Месенджери",      icon: I.Chat,  n: DEMO_MSGS.length,      src: "Workspace · обов'язкова",  required: true,  core: true },
            { id: "prices",     label: "Ціни",             icon: I.Tag,   n: DEMO_PRICES.length,    src: "Workspace · додаткова",    required: false, core: false },
            { id: "addresses",  label: "Адреси",           icon: I.Map,   n: 2,                     src: "Workspace · додаткова",    required: false, core: false },
            { id: "socials",    label: "Соц. мережі",      icon: I.Share, n: 1,                     src: "Workspace · додаткова",    required: false, core: false },
            { id: "custom",     label: "Custom поля",      icon: I.Plus,  n: 0,                     src: "Локально · цей сайт",       required: false, core: false },
          ].map((c, i) => {
            const Ic = c.icon;
            const on = cats[c.id];
            return (
              <div key={c.id} style={{
                display: "grid", gridTemplateColumns: "44px minmax(0, 1.6fr) 80px minmax(0, 1.2fr) 60px",
                gap: 16, alignItems: "center", padding: "14px 20px",
                borderTop: i ? "1px solid var(--ink-3)" : "none",
                opacity: on || c.required ? 1 : 0.55,
              }}>
                <span style={{
                  width: 32, height: 32, borderRadius: 4,
                  background: c.core ? "var(--accent-soft)" : "var(--ink-2)",
                  color: c.core ? "var(--accent)" : "var(--ink-7)",
                  display: "inline-flex", alignItems: "center", justifyContent: "center",
                }}>
                  <Ic size={14}/>
                </span>
                <div>
                  <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
                    <span style={{ font: "14px var(--font-sans)", color: "var(--ink-9)" }}>{c.label}</span>
                    {c.required && <span className="pill" style={{ height: 18, fontSize: 9.5, background: "var(--accent-soft)", color: "var(--accent)" }}>обов'язкове</span>}
                  </div>
                  <div style={{ marginTop: 3, font: "11.5px var(--font-mono)", color: "var(--ink-5)" }}>
                    {on ? "видно у вкладці Дані" : "приховано"}
                  </div>
                </div>
                <span className="mono num" style={{ font: "14px var(--font-mono)", color: "var(--ink-9)" }}>{c.n}</span>
                <span style={{ font: "12px var(--font-sans)", color: "var(--ink-5)" }}>{c.src}</span>
                <div style={{ justifySelf: "end" }}>
                  {c.required
                    ? <span title="Категорія заблокована на рівні workspace" style={{ color: "var(--ink-4)" }}><I.Lock size={14}/></span>
                    : <Toggle on={on} onClick={() => toggleCat(c.id)}/>}
                </div>
              </div>
            );
          })}
        </div>

        <button style={{
          marginTop: 14, width: "100%", padding: 14, borderRadius: 4,
          border: "1px dashed var(--ink-3)", background: "transparent",
          color: "var(--ink-5)", font: "13px var(--font-sans)",
          display: "flex", alignItems: "center", justifyContent: "center", gap: 8,
          cursor: "pointer",
        }}>
          <I.Plus size={13}/> Додати локальну категорію (тільки для цього сайту)
        </button>
      </section>
      )}

      {/* ─── API key ─── */}
      {sub === "api" && (
      <section>
        <header style={{ marginBottom: 24 }}>
          <div className="eyebrow" style={{ marginBottom: 8 }}>03 · API доступ</div>
          <h3 style={{ font: "400 24px/1.15 var(--font-sans)", color: "var(--ink-9)" }}>Ключ цього сайту</h3>
        </header>
        <div className="card" style={{ padding: 18, display: "flex", alignItems: "center", gap: 14 }}>
          <I.Key size={18} style={{ color: "var(--ink-5)" }}/>
          <span className="mono" style={{ font: "14px var(--font-mono)", color: "var(--ink-9)", flex: 1 }}>db_live_2K7sX9m…a8Z3</span>
          <span className="mono" style={{ font: "11px var(--font-mono)", color: "var(--ink-5)" }}>останнє використання · 5 хв</span>
          <button className="btn btn-secondary btn-sm">Перегенерувати</button>
          <button className="btn btn-danger btn-sm">Відкликати</button>
        </div>
      </section>
      )}

      </div>
    </div>
  );
};

/* lightweight toggle — local to site settings */
const Toggle = ({ on, onClick }) => (
  <span onClick={onClick} style={{
    width: 36, height: 20, borderRadius: 999,
    background: on ? "var(--ink-9)" : "var(--ink-3)",
    position: "relative", display: "inline-block", cursor: "pointer",
    transition: "background .15s",
  }}>
    <span style={{
      position: "absolute", top: 2, left: on ? 18 : 2,
      width: 16, height: 16, borderRadius: 999,
      background: "var(--paper)",
      transition: "left .15s",
    }}/>
  </span>
);

window.SiteDetail = SiteDetail;
