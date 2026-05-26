/* DataBridge · Mock data (single source of truth) */

/* Messengers — same shape as phones: primaries + backups linked via parentId */
const DEMO_MSGS = [
  /* PL pool */
  { id: 101, kind: "telegram", n: "@demo_pl",       label: "PL · Telegram головний",  role: "primary", geoMode: "only",   countries: ["PL"], visible: true,  order: 1, parentId: null },
  { id: 102, kind: "whatsapp", n: "+48 22 555 33 11", label: "PL · WhatsApp резерв",    role: "backup",  geoMode: "only",   countries: ["PL"], visible: true,  order: 1, parentId: 101 },
  { id: 103, kind: "messenger",n: "m.me/demoPL",    label: "PL · Facebook Messenger", role: "backup",  geoMode: "only",   countries: ["PL"], visible: true,  order: 2, parentId: 101 },

  /* UA + Світ pool */
  { id: 104, kind: "telegram", n: "@demo_main",     label: "Головний (UA + Світ)",    role: "primary", geoMode: "except", countries: ["PL"], visible: true,  order: 1, parentId: null },
  { id: 105, kind: "viber",    n: "+38 099 11 22 33", label: "UA · Viber резерв",       role: "backup",  geoMode: "only",   countries: ["UA"], visible: true,  order: 1, parentId: 104 },
  { id: 106, kind: "whatsapp", n: "+38 073 000 11 22", label: "UA · WhatsApp резерв",    role: "backup",  geoMode: "only",   countries: ["UA"], visible: true,  order: 2, parentId: 104 },
  { id: 107, kind: "telegram", n: "@demo_support",  label: "Універсальний резерв",    role: "backup",  geoMode: "all",    countries: [],     visible: true,  order: 3, parentId: 104 },

  /* Archived */
  { id: 108, kind: "skype",    n: "live:demo.old",  label: "Старий Skype",             role: "hidden",  geoMode: "all",    countries: [],     visible: false, order: 9, parentId: null },
];

const MSG_KINDS = {
  telegram:  { label: "Telegram",  color: "#229ED9", short: "TG"  },
  whatsapp:  { label: "WhatsApp",  color: "#25D366", short: "WA"  },
  viber:     { label: "Viber",     color: "#7360F2", short: "VB"  },
  messenger: { label: "Messenger", color: "#0084FF", short: "FB"  },
  signal:    { label: "Signal",    color: "#3A76F0", short: "SG"  },
  skype:     { label: "Skype",     color: "#00AFF0", short: "SK"  },
};

function msgsForGeo(country) {
  return DEMO_MSGS.filter(m => {
    if (!m.visible) return false;
    if (m.geoMode === "all") return true;
    if (m.geoMode === "only") return m.countries.includes(country);
    if (m.geoMode === "except") return !m.countries.includes(country);
    return false;
  });
}


const COUNTRIES = {
  PL: { flag: "🇵🇱", name: "Польща" },
  UA: { flag: "🇺🇦", name: "Україна" },
  DE: { flag: "🇩🇪", name: "Німеччина" },
  US: { flag: "🇺🇸", name: "США" },
  GB: { flag: "🇬🇧", name: "Велика Британія" },
  FR: { flag: "🇫🇷", name: "Франція" },
};

const GROUPS = [
  { id: "prod",    name: "Production", color: "#5a8a3c", sites: 6 },
  { id: "staging", name: "Staging",    color: "#b87a1c", sites: 3 },
  { id: "demo",    name: "Demo",       color: "#c2552c", sites: 3 },
  { id: "archive", name: "Archived",   color: "#a39d8c", sites: 2 },
];

const SITES = [
  { id: "demo",      name: "demo-site.example", group: "prod",    status: "ok",    fav: true,  lastSync: "5 хв",  phones: 7, msgs: 3 },
  { id: "nord",      name: "nordwave.com",       group: "prod",    status: "ok",    fav: true,  lastSync: "20 хв", phones: 12, msgs: 2 },
  { id: "apex",      name: "apex-shop.com",      group: "prod",    status: "error", fav: false, lastSync: "12 хв", phones: 8, msgs: 4, err: "Connection refused" },
  { id: "lumen",     name: "lumen-io.com",       group: "prod",    status: "ok",    fav: false, lastSync: "2 год", phones: 6, msgs: 1 },
  { id: "kestrel",   name: "kestrel.so",          group: "prod",    status: "ok",    fav: false, lastSync: "3 год", phones: 4, msgs: 2 },
  { id: "northgate", name: "northgate.shop",      group: "prod",    status: "ok",    fav: false, lastSync: "вчора",   phones: 9, msgs: 3 },
  { id: "voltway",   name: "voltway.pro",         group: "staging", status: "pause", fav: false, lastSync: "пауза",    phones: 5, msgs: 2 },
  { id: "stone",     name: "stoneworks.dev",      group: "staging", status: "ok",    fav: false, lastSync: "вчора",   phones: 3, msgs: 1 },
];

/* Phones for demo-site — primaries with their backups linked via parentId */
const DEMO_PHONES = [
  { id: 1, n: "+48 00 000 00 00",  label: "Польща · головний",      role: "primary", geoMode: "only",   countries: ["PL"],     visible: true,  order: 1, parentId: null },
  { id: 2, n: "+48 99 999 99 99",  label: "PL резерв",                role: "backup",  geoMode: "only",   countries: ["PL"],     visible: true,  order: 1, parentId: 1 },
  { id: 8, n: "+48 71 222 11 00",  label: "PL резерв · 2",            role: "backup",  geoMode: "only",   countries: ["PL"],     visible: true,  order: 2, parentId: 1 },
  { id: 3, n: "11111111111",        label: "Головний (UA + Світ)",     role: "primary", geoMode: "except", countries: ["PL"],     visible: true,  order: 1, parentId: null },
  { id: 4, n: "+099 11 22 33",     label: "UA резерв",                role: "backup",  geoMode: "only",   countries: ["UA"],     visible: true,  order: 1, parentId: 3 },
  { id: 5, n: "073 111-22-33",     label: "UA резерв · 2",            role: "backup",  geoMode: "only",   countries: ["UA"],     visible: true,  order: 2, parentId: 3 },
  { id: 6, n: "+48 22 555 33 11",  label: "Універсальний резерв",     role: "backup",  geoMode: "all",    countries: [],         visible: true,  order: 3, parentId: 3 },
  { id: 7, n: "063 000-00-00",     label: "Колишній головний",        role: "hidden",  geoMode: "all",    countries: [],         visible: false, order: 5, parentId: null },
];

/* Compute which phones are visible for a given country / "world" */
function phonesForGeo(country) {
  return DEMO_PHONES.filter(p => {
    if (!p.visible) return false;
    if (p.geoMode === "all") return true;
    if (p.geoMode === "only") return p.countries.includes(country);
    if (p.geoMode === "except") return !p.countries.includes(country);
    return false;
  });
}
function geoLabel(p) {
  if (p.geoMode === "all") return "🌐 Усім";
  if (p.geoMode === "only") return "Тільки " + p.countries.map(c => COUNTRIES[c].flag).join("");
  if (p.geoMode === "except") return "Крім " + p.countries.map(c => COUNTRIES[c].flag).join("");
}

/* Prices — multi-currency, per geo, with sale/old prices */
const DEMO_PRICES = [
  { id: 201, sku: "WAVE-01",  name: "Підписка · Standard",  geoMode: "only",   countries: ["PL"], currency: "PLN", price: 149,   old: 199,  unit: "/міс", role: "primary", visible: true,  order: 1 },
  { id: 202, sku: "WAVE-01",  name: "Підписка · Standard",  geoMode: "only",   countries: ["UA"], currency: "UAH", price: 1290,  old: 1490, unit: "/міс", role: "primary", visible: true,  order: 2 },
  { id: 203, sku: "WAVE-01",  name: "Підписка · Standard",  geoMode: "except", countries: ["PL","UA"], currency: "EUR", price: 39,  old: null, unit: "/mo",  role: "primary", visible: true,  order: 3 },

  { id: 204, sku: "WAVE-02",  name: "Підписка · Pro",        geoMode: "only",   countries: ["PL"], currency: "PLN", price: 299,   old: null, unit: "/міс", role: "primary", visible: true,  order: 1 },
  { id: 205, sku: "WAVE-02",  name: "Підписка · Pro",        geoMode: "only",   countries: ["UA"], currency: "UAH", price: 2490,  old: null, unit: "/міс", role: "primary", visible: true,  order: 2 },
  { id: 206, sku: "WAVE-02",  name: "Підписка · Pro",        geoMode: "except", countries: ["PL","UA"], currency: "USD", price: 79,  old: null, unit: "/mo",  role: "primary", visible: true,  order: 3 },

  { id: 207, sku: "ONBOARD",  name: "Setup · одноразово",    geoMode: "all",    countries: [],     currency: "EUR", price: 0,     old: 149,  unit: "одноразово", role: "primary", visible: true, order: 1 },
  { id: 208, sku: "OLD-2024", name: "Стара тарифна сітка",   geoMode: "all",    countries: [],     currency: "EUR", price: 29,    old: null, unit: "/mo",  role: "hidden",  visible: false, order: 9 },
];

function pricesForGeo(country) {
  return DEMO_PRICES.filter(p => {
    if (!p.visible) return false;
    if (p.geoMode === "all") return true;
    if (p.geoMode === "only") return p.countries.includes(country);
    if (p.geoMode === "except") return !p.countries.includes(country);
    return false;
  });
}

const CURRENCIES = {
  PLN: { sym: "zł", flag: "🇵🇱", code: "PLN" },
  UAH: { sym: "₴",  flag: "🇺🇦", code: "UAH" },
  EUR: { sym: "€",  flag: "🇪🇺", code: "EUR" },
  USD: { sym: "$",  flag: "🇺🇸", code: "USD" },
  GBP: { sym: "£",  flag: "🇬🇧", code: "GBP" },
};

function fmtPrice(p) {
  const c = CURRENCIES[p.currency] || { sym: p.currency };
  const n = p.price.toLocaleString("uk-UA");
  return p.currency === "EUR" || p.currency === "USD" || p.currency === "GBP"
    ? `${c.sym}${n}`
    : `${n} ${c.sym}`;
}

const TEAM = [
  { id: 1, name: "Test Admin",     email: "admin@databridge.app",  role: "admin",   online: true,  last: "зараз" },
  { id: 2, name: "Іван Петренко",   email: "ivan@databridge.app",  role: "admin",   online: true,  last: "2 хв" },
  { id: 3, name: "Olha Boyko",     email: "olha@databridge.app",  role: "manager", online: true,  last: "15 хв" },
  { id: 4, name: "Sam Cooper",     email: "sam@databridge.app",    role: "manager", online: false, last: "2 год" },
  { id: 5, name: "Дмитро К.",       email: "dmytro@databridge.app", role: "viewer",  online: false, last: "вчора" },
];

const LOGS = [
  { t: "18:54:23", site: "demo-site.example",   action: "phone updated",     user: "Test Admin",   status: "ok" },
  { t: "18:42:11", site: "apex-shop.com",        action: "sync failed",       user: "System",        status: "error" },
  { t: "18:30:08", site: "nordwave.com",         action: "push success",      user: "Test Admin",   status: "ok" },
  { t: "18:15:42", site: "voltway.pro",           action: "sync ok",           user: "System",        status: "ok" },
  { t: "18:02:19", site: "lumen-io.com",          action: "push success",      user: "Olha B.",      status: "ok" },
  { t: "17:54:33", site: "kestrel.so",            action: "push success",      user: "System",        status: "ok" },
  { t: "17:48:00", site: "apex-shop.com",        action: "sync timeout",      user: "System",        status: "error" },
];

Object.assign(window, { COUNTRIES, GROUPS, SITES, DEMO_PHONES, DEMO_MSGS, MSG_KINDS, DEMO_PRICES, CURRENCIES, TEAM, LOGS, phonesForGeo, msgsForGeo, pricesForGeo, geoLabel, fmtPrice });
