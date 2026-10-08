import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const DATA_DIR = process.env.DATA_DIR || path.join(ROOT, 'data');
export const UPLOAD_DIR = process.env.UPLOAD_DIR || path.join(ROOT, 'uploads');
fs.mkdirSync(DATA_DIR, { recursive: true });
fs.mkdirSync(UPLOAD_DIR, { recursive: true });

// SQLite المدمج في Node (22.13+)، وإلا better-sqlite3 (للاستضافات بإصدار Node أقدم مثل Hostinger)
async function openDb(file) {
  try {
    const { DatabaseSync } = await import('node:sqlite');
    return new DatabaseSync(file);
  } catch (e) {
    try { return new (createRequire(import.meta.url)('better-sqlite3'))(file); } catch {
      throw new Error(`SQLite غير متاح (Node ${process.version}). استخدم Node 22.13+ أو شغّل npm install لتثبيت better-sqlite3.\n${e.message}`);
    }
  }
}
export const db = await openDb(path.join(DATA_DIR, 'store.db'));
try { db.exec('PRAGMA journal_mode=WAL'); } catch { /* بعض أنظمة الملفات المشتركة لا تدعم WAL */ }
db.exec('PRAGMA foreign_keys=ON;');

db.exec(`
CREATE TABLE IF NOT EXISTS users(
  id INTEGER PRIMARY KEY, email TEXT UNIQUE NOT NULL, name TEXT, salt TEXT NOT NULL, hash TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS settings(key TEXT PRIMARY KEY, value TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS collections(
  id INTEGER PRIMARY KEY, handle TEXT UNIQUE NOT NULL, title TEXT NOT NULL, title_en TEXT DEFAULT '',
  description TEXT DEFAULT '', description_en TEXT DEFAULT '', image TEXT DEFAULT '', position INTEGER DEFAULT 0,
  visible INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS products(
  id INTEGER PRIMARY KEY, handle TEXT UNIQUE NOT NULL, title TEXT NOT NULL, title_en TEXT DEFAULT '',
  description TEXT DEFAULT '', description_en TEXT DEFAULT '', vendor TEXT DEFAULT 'NOTCH',
  price REAL NOT NULL DEFAULT 0, compare_at REAL DEFAULT 0, sku TEXT DEFAULT '', stock INTEGER DEFAULT 0,
  status TEXT DEFAULT 'active', images TEXT DEFAULT '[]', variants TEXT DEFAULT '[]', tags TEXT DEFAULT '[]',
  collection_ids TEXT DEFAULT '[]', created_at TEXT DEFAULT CURRENT_TIMESTAMP, sold INTEGER DEFAULT 0
);
CREATE TABLE IF NOT EXISTS orders(
  id INTEGER PRIMARY KEY, number INTEGER UNIQUE NOT NULL, token TEXT UNIQUE NOT NULL,
  name TEXT, phone TEXT, email TEXT DEFAULT '', city TEXT DEFAULT '', address TEXT DEFAULT '', notes TEXT DEFAULT '',
  items TEXT NOT NULL, subtotal REAL, shipping REAL, discount REAL DEFAULT 0, total REAL, discount_code TEXT DEFAULT '',
  payment_method TEXT DEFAULT 'cod', payment_status TEXT DEFAULT 'pending', status TEXT DEFAULT 'new',
  admin_note TEXT DEFAULT '', created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS discounts(
  id INTEGER PRIMARY KEY, code TEXT UNIQUE NOT NULL, type TEXT DEFAULT 'percent', value REAL NOT NULL,
  min_subtotal REAL DEFAULT 0, max_uses INTEGER DEFAULT 0, uses INTEGER DEFAULT 0, active INTEGER DEFAULT 1
);
CREATE TABLE IF NOT EXISTS pages(
  id INTEGER PRIMARY KEY, handle TEXT UNIQUE NOT NULL, title TEXT NOT NULL, title_en TEXT DEFAULT '',
  body TEXT DEFAULT '', body_en TEXT DEFAULT '', published INTEGER DEFAULT 1
);
`);

export const all = (sql, ...p) => db.prepare(sql).all(...p).map((r) => ({ ...r }));
export const get = (sql, ...p) => { const r = db.prepare(sql).get(...p); return r ? { ...r } : undefined; };
export const run = (sql, ...p) => db.prepare(sql).run(...p);
export const tx = (fn) => {
  db.exec('BEGIN');
  try { const r = fn(); db.exec('COMMIT'); return r; } catch (e) { db.exec('ROLLBACK'); throw e; }
};

// ---------- settings ----------
export const DEFAULT_SETTINGS = {
  store: {
    name: 'NOTCH Tech', name_en: 'NOTCH Tech',
    tagline: 'تقنية بتفرق… من تصميم وتصنيع NOTCH', tagline_en: 'Tech that makes a difference — designed & made by NOTCH',
    currency: 'EGP', currency_symbol: 'ج.م', currency_symbol_en: 'EGP',
    phone: '', whatsapp: '', email: '', address: '',
    shipping_fee: 60, free_shipping_over: 1500,
    announcement: 'شحن مجاني للطلبات فوق 1500 ج.م • ضمان سنتين على كل المنتجات',
    announcement_en: 'Free shipping over 1500 EGP • 2-year warranty on everything',
    social: { facebook: '', instagram: '', tiktok: '', youtube: '' },
    meta_description: 'NOTCH Tech – شواحن، كابلات، باور بانك، سماعات وإكسسوارات موبايل بجودة عالية.',
    default_lang: 'ar', allow_lang_switch: true, track_stock: true,
  },
  theme: {
    colors: { primary: '#0b0b0f', accent: '#ff4d2e', background: '#ffffff', surface: '#f5f5f7', text: '#1d1d1f', muted: '#6e6e73', header_bg: '#ffffff', footer_bg: '#0b0b0f' },
    font: 'Cairo', radius: 14, logo: '', logo_height: 34, header_sticky: true, product_grid_cols: 4,
    menu: [
      { label: 'الرئيسية', label_en: 'Home', url: '/' },
      { label: 'كل المنتجات', label_en: 'All products', url: '/collections/all' },
      { label: 'من نحن', label_en: 'About', url: '/pages/about' },
      { label: 'تواصل معنا', label_en: 'Contact', url: '/pages/contact' },
    ],
    footer_text: 'صُنع بشغف في مصر.', footer_text_en: 'Made with passion in Egypt.',
    sections: [
      { id: 's1', type: 'hero', enabled: true, title: 'تقنية أنيقة. أداء بلا حدود.', title_en: 'Elegant tech. Limitless performance.',
        subtitle: 'شواحن سريعة، باور بانك، سماعات وإكسسوارات بتصميم NOTCH.', subtitle_en: 'Fast chargers, power banks, audio & accessories by NOTCH.',
        button: 'تسوّق الآن', button_en: 'Shop now', link: '/collections/all', image: '', overlay: 0 },
      { id: 's2', type: 'features', enabled: true, items: [
        { icon: '🚚', title: 'توصيل لكل المحافظات', title_en: 'Nationwide delivery', text: 'خلال 2-5 أيام عمل', text_en: '2–5 business days' },
        { icon: '🛡️', title: 'ضمان سنتين', title_en: '2-year warranty', text: 'استبدال فوري للعيوب', text_en: 'Instant replacement' },
        { icon: '💵', title: 'الدفع عند الاستلام', title_en: 'Cash on delivery', text: 'افحص ثم ادفع', text_en: 'Inspect then pay' },
        { icon: '⚡', title: 'جودة مصنّعة', title_en: 'Factory quality', text: 'اختبارات صارمة', text_en: 'Strict testing' } ] },
      { id: 's3', type: 'collections', enabled: true, title: 'تسوّق حسب الفئة', title_en: 'Shop by category', limit: 6 },
      { id: 's4', type: 'products', enabled: true, title: 'الأكثر مبيعًا', title_en: 'Best sellers', collection: '', sort: 'sold', limit: 8 },
      { id: 's5', type: 'banner', enabled: true, title: 'ضمان NOTCH', title_en: 'The NOTCH promise', text: 'كل منتج بيعدّي على اختبارات جودة قبل ما يوصلك.', text_en: 'Every product passes quality checks before it reaches you.', button: 'اعرف أكتر', button_en: 'Learn more', link: '/pages/about', image: '' },
      { id: 's6', type: 'products', enabled: true, title: 'وصل حديثًا', title_en: 'New arrivals', collection: '', sort: 'new', limit: 4 },
    ],
  },
};

export function getSetting(key) {
  const row = get('SELECT value FROM settings WHERE key=?', key);
  const saved = row ? JSON.parse(row.value) : {};
  return key === 'theme'
    ? { ...DEFAULT_SETTINGS.theme, ...saved, colors: { ...DEFAULT_SETTINGS.theme.colors, ...(saved.colors || {}) } }
    : { ...DEFAULT_SETTINGS[key], ...saved };
}
export function setSetting(key, value) {
  run('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value', key, JSON.stringify(value));
}

export const slugify = (s) =>
  String(s || '').toLowerCase().trim().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '') || 'item';
export function uniqueHandle(table, base, exceptId = 0) {
  let h = slugify(base), n = 1;
  while (get(`SELECT id FROM ${table} WHERE handle=? AND id<>?`, h, exceptId)) h = `${slugify(base)}-${++n}`;
  return h;
}
