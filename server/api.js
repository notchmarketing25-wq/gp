import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { all, get, run, tx, getSetting, setSetting, uniqueHandle, UPLOAD_DIR } from './db.js';
import { HttpError } from './http.js';
import { hashPassword, makeSession, readSession, verifyPassword } from './auth.js';

const J = (v, d = []) => { try { return typeof v === 'string' ? JSON.parse(v) : v ?? d; } catch { return d; } };
export const parseProduct = (p) => p && ({ ...p, images: J(p.images), variants: J(p.variants), tags: J(p.tags), collection_ids: J(p.collection_ids) });
const num = (v, d = 0) => (Number.isFinite(Number(v)) ? Number(v) : d);
const str = (v, max = 5000) => String(v ?? '').slice(0, max);

// ---------- generic CRUD resources ----------
const RESOURCES = {
  products: {
    table: 'products', order: 'id DESC', handleFrom: ['title_en', 'title'], search: ['title', 'title_en', 'sku'],
    fields: {
      title: (v) => str(v, 200), title_en: (v) => str(v, 200), description: (v) => str(v, 20000), description_en: (v) => str(v, 20000),
      vendor: (v) => str(v, 100), price: (v) => Math.max(0, num(v)), compare_at: (v) => Math.max(0, num(v)), sku: (v) => str(v, 100),
      stock: (v) => Math.trunc(num(v)), status: (v) => (v === 'draft' ? 'draft' : 'active'),
      images: (v) => JSON.stringify((Array.isArray(v) ? v : []).map((x) => str(x, 500)).filter(Boolean)),
      tags: (v) => JSON.stringify((Array.isArray(v) ? v : []).map((x) => str(x, 50)).filter(Boolean)),
      collection_ids: (v) => JSON.stringify((Array.isArray(v) ? v : []).map(Number).filter(Boolean)),
      variants: (v) => JSON.stringify((Array.isArray(v) ? v : []).filter((x) => x && x.title).map((x) => ({
        title: str(x.title, 100), price: Math.max(0, num(x.price)), stock: Math.trunc(num(x.stock)), sku: str(x.sku, 100) }))),
    },
    required: ['title'],
    post(row) { // المخزون = مجموع مخزون النسخ
      const vs = J(row.variants);
      if (vs.length) run('UPDATE products SET stock=? WHERE id=?', vs.reduce((s, v) => s + v.stock, 0), row.id);
    },
  },
  collections: {
    table: 'collections', order: 'position, id', handleFrom: ['title_en', 'title'],
    fields: { title: (v) => str(v, 200), title_en: (v) => str(v, 200), description: (v) => str(v), description_en: (v) => str(v),
      image: (v) => str(v, 500), position: (v) => Math.trunc(num(v)), visible: (v) => (v ? 1 : 0) },
    required: ['title'],
  },
  pages: {
    table: 'pages', order: 'id', handleFrom: ['title_en', 'title'],
    fields: { title: (v) => str(v, 200), title_en: (v) => str(v, 200), body: (v) => str(v, 100000), body_en: (v) => str(v, 100000), published: (v) => (v ? 1 : 0) },
    required: ['title'],
  },
  discounts: {
    table: 'discounts', order: 'id DESC', noHandle: true,
    fields: { code: (v) => str(v, 40).toUpperCase().replace(/\s+/g, ''), type: (v) => (v === 'fixed' ? 'fixed' : 'percent'), value: (v) => Math.max(0, num(v)),
      min_subtotal: (v) => Math.max(0, num(v)), max_uses: (v) => Math.max(0, Math.trunc(num(v))), active: (v) => (v ? 1 : 0) },
    required: ['code', 'value'],
  },
};

function crud(router, name, cfg, guard) {
  const base = `/api/admin/${name}`;
  const out = (r) => (name === 'products' ? parseProduct(r) : r);
  router.get(base, (ctx) => {
    guard(ctx);
    const q = ctx.query.q ? `%${ctx.query.q}%` : null;
    let sql = `SELECT * FROM ${cfg.table}`; const p = [];
    if (q && cfg.search) { sql += ` WHERE ${cfg.search.map((f) => `${f} LIKE ?`).join(' OR ')}`; p.push(...cfg.search.map(() => q)); }
    ctx.json(all(`${sql} ORDER BY ${cfg.order}`, ...p).map(out));
  });
  router.get(`${base}/:id`, (ctx) => {
    guard(ctx);
    const r = get(`SELECT * FROM ${cfg.table} WHERE id=?`, Number(ctx.params.id));
    if (!r) throw new HttpError(404, 'غير موجود');
    ctx.json(out(r));
  });
  const save = async (ctx, id) => {
    guard(ctx);
    const b = await ctx.body();
    const vals = {};
    for (const [k, fn] of Object.entries(cfg.fields)) if (b[k] !== undefined) vals[k] = fn(b[k]);
    if (!id) for (const k of cfg.required) if (!vals[k] && vals[k] !== 0) throw new HttpError(400, `الحقل مطلوب: ${k}`);
    if (!cfg.noHandle && (!id || b.handle !== undefined || b.title !== undefined)) {
      const base0 = (b.handle || '').trim() || (id ? null : cfg.handleFrom.map((k) => vals[k]).find(Boolean));
      if (base0) vals.handle = uniqueHandle(cfg.table, base0, id || 0);
    }
    try {
      if (id) {
        const keys = Object.keys(vals);
        if (keys.length) run(`UPDATE ${cfg.table} SET ${keys.map((k) => `${k}=?`).join(',')} WHERE id=?`, ...keys.map((k) => vals[k]), id);
      } else {
        const keys = Object.keys(vals);
        id = Number(run(`INSERT INTO ${cfg.table}(${keys.join(',')}) VALUES(${keys.map(() => '?').join(',')})`, ...keys.map((k) => vals[k])).lastInsertRowid);
      }
    } catch (e) {
      if (String(e.message).includes('UNIQUE')) throw new HttpError(409, 'القيمة مستخدمة بالفعل (كود/رابط مكرر)');
      throw e;
    }
    const row = get(`SELECT * FROM ${cfg.table} WHERE id=?`, id);
    cfg.post?.(row);
    ctx.json(out(get(`SELECT * FROM ${cfg.table} WHERE id=?`, id)));
  };
  router.post(base, (ctx) => save(ctx, 0));
  router.put(`${base}/:id`, (ctx) => save(ctx, Number(ctx.params.id)));
  router.del(`${base}/:id`, (ctx) => {
    guard(ctx);
    run(`DELETE FROM ${cfg.table} WHERE id=?`, Number(ctx.params.id));
    ctx.json({ ok: true });
  });
}

// ---------- checkout ----------
export function priceCart(rawItems, code) {
  const store = getSetting('store');
  if (!Array.isArray(rawItems) || !rawItems.length) throw new HttpError(400, 'السلة فارغة');
  const items = rawItems.slice(0, 50).map((it) => {
    const p = parseProduct(get("SELECT * FROM products WHERE id=? AND status='active'", Number(it.id)));
    if (!p) throw new HttpError(400, 'منتج غير متاح');
    const qty = Math.max(1, Math.min(99, Math.trunc(num(it.qty, 1))));
    let v = null;
    if (p.variants.length) {
      v = p.variants.find((x) => x.title === it.variant);
      if (!v) throw new HttpError(400, `اختر نوع المنتج: ${p.title}`);
    }
    const stock = v ? v.stock : p.stock;
    if (store.track_stock && stock < qty) throw new HttpError(400, `الكمية المتاحة من "${p.title}" ${Math.max(stock, 0)} فقط`);
    return { id: p.id, title: p.title, title_en: p.title_en, variant: v?.title || '', sku: v?.sku || p.sku, price: v ? v.price : p.price, qty, image: p.images[0] || '' };
  });
  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
  let discount = 0, applied = '';
  if (code) {
    const d = get('SELECT * FROM discounts WHERE code=? AND active=1', String(code).toUpperCase().trim());
    if (!d) throw new HttpError(400, 'كود الخصم غير صحيح');
    if (d.max_uses && d.uses >= d.max_uses) throw new HttpError(400, 'تم استنفاد كود الخصم');
    if (subtotal < d.min_subtotal) throw new HttpError(400, `الحد الأدنى لاستخدام الكود ${d.min_subtotal}`);
    discount = Math.min(subtotal, d.type === 'percent' ? Math.round(subtotal * d.value) / 100 : d.value);
    applied = d.code;
  }
  const shipping = store.free_shipping_over && subtotal - discount >= store.free_shipping_over ? 0 : store.shipping_fee;
  return { items, subtotal, discount, shipping, total: subtotal - discount + shipping, code: applied };
}

function createOrder(b) {
  const name = str(b.name, 120).trim(), phone = str(b.phone, 30).replace(/[^\d+]/g, ''), address = str(b.address, 500).trim();
  if (name.length < 2) throw new HttpError(400, 'الاسم مطلوب');
  if (phone.length < 8) throw new HttpError(400, 'رقم الهاتف غير صحيح');
  if (address.length < 5) throw new HttpError(400, 'العنوان مطلوب');
  return tx(() => {
    const cart = priceCart(b.items, b.code);
    for (const it of cart.items) {
      const p = parseProduct(get('SELECT * FROM products WHERE id=?', it.id));
      if (it.variant) {
        const vs = p.variants.map((v) => (v.title === it.variant ? { ...v, stock: v.stock - it.qty } : v));
        run('UPDATE products SET variants=?, stock=?, sold=sold+? WHERE id=?', JSON.stringify(vs), vs.reduce((s, v) => s + v.stock, 0), it.qty, p.id);
      } else run('UPDATE products SET stock=stock-?, sold=sold+? WHERE id=?', it.qty, it.qty, p.id);
    }
    if (cart.code) run('UPDATE discounts SET uses=uses+1 WHERE code=?', cart.code);
    const number = (get('SELECT MAX(number) m FROM orders')?.m || 1000) + 1;
    const token = crypto.randomBytes(12).toString('hex');
    run(`INSERT INTO orders(number,token,name,phone,email,city,address,notes,items,subtotal,shipping,discount,total,discount_code)
         VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)`, number, token, name, phone, str(b.email, 120), str(b.city, 80), address, str(b.notes, 1000),
      JSON.stringify(cart.items), cart.subtotal, cart.shipping, cart.discount, cart.total, cart.code);
    return { number, token, total: cart.total };
  });
}

const attempts = new Map();
const ORDER_STATUSES = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];

export function registerApi(router) {
  const guard = (ctx) => {
    const u = readSession(ctx.cookies.nt_session);
    if (!u) throw new HttpError(401, 'سجّل الدخول أولًا');
    if (ctx.req.method !== 'GET' && !String(ctx.req.headers['content-type'] || '').includes('application/json')) throw new HttpError(415, 'نوع الطلب غير مدعوم');
    ctx.user = u;
  };

  // ----- public -----
  router.post('/api/checkout', async (ctx) => ctx.json(createOrder(await ctx.body())));
  router.post('/api/cart/price', async (ctx) => {
    const b = await ctx.body();
    try { ctx.json({ ok: true, ...priceCart(b.items, b.code) }); }
    catch (e) { if (e instanceof HttpError && b.code) { try { return ctx.json({ ok: true, ...priceCart(b.items), codeError: e.message }); } catch {} } throw e; }
  });

  // ----- auth -----
  router.post('/api/login', async (ctx) => {
    const ip = String(ctx.req.headers['x-forwarded-for'] || '').split(',')[0].trim() || ctx.req.socket.remoteAddress; // خلف بروكسي الاستضافة
    const a = attempts.get(ip) || { n: 0, t: Date.now() };
    if (Date.now() - a.t > 15 * 60e3) { a.n = 0; a.t = Date.now(); }
    if (a.n >= 10) throw new HttpError(429, 'محاولات كثيرة، حاول بعد 15 دقيقة');
    const b = await ctx.body();
    const u = get('SELECT * FROM users WHERE email=?', str(b.email, 200).trim().toLowerCase());
    if (!u || !verifyPassword(str(b.password, 200), u)) { a.n++; attempts.set(ip, a); throw new HttpError(401, 'بيانات الدخول غير صحيحة'); }
    attempts.delete(ip);
    ctx.cookie('nt_session', makeSession(u.id), { maxAge: 14 * 86400 });
    ctx.json({ ok: true });
  });
  router.post('/api/logout', (ctx) => { ctx.cookie('nt_session', '', { maxAge: 0 }); ctx.json({ ok: true }); });
  router.get('/api/admin/me', (ctx) => {
    guard(ctx);
    const u = get('SELECT * FROM users WHERE id=?', ctx.user.id);
    ctx.json({ ...ctx.user, defaultPassword: verifyPassword('admin123', u) });
  });
  router.post('/api/admin/account', async (ctx) => {
    guard(ctx);
    const b = await ctx.body();
    const u = get('SELECT * FROM users WHERE id=?', ctx.user.id);
    if (!verifyPassword(str(b.current, 200), u)) throw new HttpError(400, 'كلمة المرور الحالية غير صحيحة');
    const email = str(b.email || u.email, 200).trim().toLowerCase();
    if (b.password) {
      if (String(b.password).length < 8) throw new HttpError(400, 'كلمة المرور 8 أحرف على الأقل');
      const { salt, hash } = hashPassword(String(b.password));
      run('UPDATE users SET email=?, salt=?, hash=? WHERE id=?', email, salt, hash, u.id);
    } else run('UPDATE users SET email=? WHERE id=?', email, u.id);
    ctx.json({ ok: true });
  });

  // ----- admin -----
  for (const [n, cfg] of Object.entries(RESOURCES)) crud(router, n, cfg, guard);

  router.get('/api/admin/stats', (ctx) => {
    guard(ctx);
    const live = "status<>'cancelled'";
    const t = get(`SELECT COUNT(*) n, COALESCE(SUM(total),0) s FROM orders WHERE ${live}`);
    const today = get(`SELECT COUNT(*) n, COALESCE(SUM(total),0) s FROM orders WHERE ${live} AND date(created_at)=date('now')`);
    const days = all(`SELECT date(created_at) d, COUNT(*) n, SUM(total) s FROM orders WHERE ${live} AND created_at>=date('now','-13 days') GROUP BY d`);
    const series = Array.from({ length: 14 }, (_, i) => {
      const d = new Date(Date.now() - (13 - i) * 864e5).toISOString().slice(0, 10);
      const r = days.find((x) => x.d === d); return { d, n: r?.n || 0, s: r?.s || 0 };
    });
    ctx.json({
      total_sales: t.s, orders: t.n, avg: t.n ? t.s / t.n : 0, today_sales: today.s, today_orders: today.n,
      new_orders: get("SELECT COUNT(*) n FROM orders WHERE status='new'").n, products: get('SELECT COUNT(*) n FROM products').n,
      series, recent: all('SELECT id,number,name,total,status,created_at FROM orders ORDER BY id DESC LIMIT 6'),
      low_stock: all("SELECT id,title,stock FROM products WHERE status='active' AND stock<=10 ORDER BY stock LIMIT 6"),
      top: all('SELECT id,title,sold,price FROM products ORDER BY sold DESC LIMIT 5'),
    });
  });

  router.get('/api/admin/orders', (ctx) => {
    guard(ctx);
    const { status, q } = ctx.query; let sql = 'SELECT * FROM orders WHERE 1=1'; const p = [];
    if (status && ORDER_STATUSES.includes(status)) { sql += ' AND status=?'; p.push(status); }
    if (q) { sql += ' AND (name LIKE ? OR phone LIKE ? OR CAST(number AS TEXT) LIKE ?)'; p.push(`%${q}%`, `%${q}%`, `%${q}%`); }
    ctx.json(all(`${sql} ORDER BY id DESC LIMIT 500`, ...p).map((o) => ({ ...o, items: J(o.items) })));
  });
  router.get('/api/admin/orders/:id', (ctx) => {
    guard(ctx);
    const o = get('SELECT * FROM orders WHERE id=?', Number(ctx.params.id));
    if (!o) throw new HttpError(404, 'الطلب غير موجود');
    ctx.json({ ...o, items: J(o.items) });
  });
  router.put('/api/admin/orders/:id', async (ctx) => {
    guard(ctx);
    const b = await ctx.body(), id = Number(ctx.params.id);
    const o = get('SELECT * FROM orders WHERE id=?', id);
    if (!o) throw new HttpError(404, 'الطلب غير موجود');
    const status = ORDER_STATUSES.includes(b.status) ? b.status : o.status;
    tx(() => { // إرجاع المخزون عند الإلغاء (مرة واحدة) وسحبه عند إعادة التفعيل
      if (status !== o.status && (status === 'cancelled' || o.status === 'cancelled')) {
        const dir = status === 'cancelled' ? 1 : -1;
        for (const it of J(o.items)) {
          const p = parseProduct(get('SELECT * FROM products WHERE id=?', it.id)); if (!p) continue;
          if (it.variant && p.variants.length) {
            const vs = p.variants.map((v) => (v.title === it.variant ? { ...v, stock: v.stock + dir * it.qty } : v));
            run('UPDATE products SET variants=?, stock=? WHERE id=?', JSON.stringify(vs), vs.reduce((s, v) => s + v.stock, 0), p.id);
          } else run('UPDATE products SET stock=stock+? WHERE id=?', dir * it.qty, p.id);
        }
      }
      run('UPDATE orders SET status=?, payment_status=?, admin_note=? WHERE id=?', status,
        ['pending', 'paid', 'refunded'].includes(b.payment_status) ? b.payment_status : o.payment_status, str(b.admin_note ?? o.admin_note, 2000), id);
    });
    ctx.json({ ok: true });
  });
  router.get('/api/admin/customers', (ctx) => {
    guard(ctx);
    ctx.json(all(`SELECT phone, MAX(name) name, MAX(email) email, MAX(city) city, COUNT(*) orders, SUM(CASE WHEN status<>'cancelled' THEN total ELSE 0 END) spent, MAX(created_at) last_order
                  FROM orders GROUP BY phone ORDER BY spent DESC`));
  });

  router.get('/api/admin/settings', (ctx) => { guard(ctx); ctx.json({ store: getSetting('store'), theme: getSetting('theme') }); });
  router.put('/api/admin/settings', async (ctx) => {
    guard(ctx);
    const b = await ctx.body();
    if (b.store) setSetting('store', { ...getSetting('store'), ...b.store });
    if (b.theme) {
      const t = b.theme;
      if (t.sections && !Array.isArray(t.sections)) throw new HttpError(400, 'sections غير صالح');
      setSetting('theme', { ...getSetting('theme'), ...t });
    }
    ctx.json({ ok: true });
  });

  router.post('/api/admin/upload', async (ctx) => {
    guard(ctx);
    const b = await ctx.body();
    const m = /^data:image\/(png|jpeg|webp|gif);base64,(.+)$/s.exec(String(b.data || ''));
    if (!m) throw new HttpError(400, 'الصيغ المسموحة: PNG, JPG, WEBP, GIF');
    const buf = Buffer.from(m[2], 'base64');
    if (buf.length > 6 * 1024 * 1024) throw new HttpError(413, 'الحد الأقصى 6MB');
    const file = `${Date.now().toString(36)}-${crypto.randomBytes(4).toString('hex')}.${m[1] === 'jpeg' ? 'jpg' : m[1]}`;
    fs.writeFileSync(path.join(UPLOAD_DIR, file), buf);
    ctx.json({ url: `/uploads/${file}` });
  });
}
