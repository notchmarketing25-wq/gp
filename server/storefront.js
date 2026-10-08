import { all, get, getSetting } from './db.js';
import { parseProduct } from './api.js';
import { placeholderSvg } from './ph.js';

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const FONTS = ['Cairo', 'Tajawal', 'Almarai', 'Inter', 'Poppins'];
const safeUrl = (u) => (/^(\/|https?:\/\/|mailto:|tel:|#)/i.test(u || '') ? u : '/');

const DICT = {
  ar: { home: 'الرئيسية', cart: 'السلة', search: 'بحث', search_ph: 'ابحث عن منتج…', all: 'كل المنتجات', add: 'أضف إلى السلة', buy: 'اشترِ الآن', sold_out: 'نفد المخزون',
    sale: 'خصم', in_stock: 'متوفر', low: 'كمية محدودة', variant: 'النوع', qty: 'الكمية', related: 'منتجات مشابهة', sort: 'ترتيب', newest: 'الأحدث', price_asc: 'السعر: الأقل',
    price_desc: 'السعر: الأعلى', best: 'الأكثر مبيعًا', no_products: 'لا توجد منتجات هنا بعد.', results: 'نتائج البحث عن', view_all: 'عرض الكل', desc: 'الوصف',
    cart_empty: 'سلتك فارغة', continue: 'متابعة التسوّق', subtotal: 'المجموع الفرعي', shipping: 'الشحن', free: 'مجاني', discount: 'الخصم', total: 'الإجمالي',
    promo: 'كود الخصم', apply: 'تطبيق', checkout: 'بيانات الشحن', name: 'الاسم بالكامل', phone: 'رقم الموبايل', email: 'البريد (اختياري)', city: 'المحافظة / المدينة',
    address: 'العنوان بالتفصيل', notes: 'ملاحظات (اختياري)', cod: 'الدفع عند الاستلام', place: 'تأكيد الطلب', remove: 'حذف', added: 'تمت الإضافة للسلة',
    thanks: 'شكرًا لطلبك!', order_no: 'رقم الطلب', we_call: 'هنتواصل معاك قريبًا لتأكيد الشحن.', status: 'الحالة', collections: 'الفئات', follow: 'تابعنا',
    pages: 'روابط', lang: 'English', not_found: 'الصفحة غير موجودة', back: 'العودة للرئيسية', pick: 'اختر النوع', placing: 'جارٍ إرسال الطلب…',
    st: { new: 'جديد', processing: 'قيد التجهيز', shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي' }, rights: 'جميع الحقوق محفوظة' },
  en: { home: 'Home', cart: 'Cart', search: 'Search', search_ph: 'Search products…', all: 'All products', add: 'Add to cart', buy: 'Buy now', sold_out: 'Sold out',
    sale: 'OFF', in_stock: 'In stock', low: 'Low stock', variant: 'Option', qty: 'Quantity', related: 'You may also like', sort: 'Sort', newest: 'Newest', price_asc: 'Price: low to high',
    price_desc: 'Price: high to low', best: 'Best selling', no_products: 'No products here yet.', results: 'Search results for', view_all: 'View all', desc: 'Description',
    cart_empty: 'Your cart is empty', continue: 'Continue shopping', subtotal: 'Subtotal', shipping: 'Shipping', free: 'Free', discount: 'Discount', total: 'Total',
    promo: 'Discount code', apply: 'Apply', checkout: 'Shipping details', name: 'Full name', phone: 'Mobile number', email: 'Email (optional)', city: 'Governorate / City',
    address: 'Full address', notes: 'Notes (optional)', cod: 'Cash on delivery', place: 'Place order', remove: 'Remove', added: 'Added to cart',
    thanks: 'Thank you for your order!', order_no: 'Order number', we_call: 'We will contact you shortly to confirm shipping.', status: 'Status', collections: 'Collections', follow: 'Follow us',
    pages: 'Links', lang: 'العربية', not_found: 'Page not found', back: 'Back to home', pick: 'Choose option', placing: 'Placing order…',
    st: { new: 'New', processing: 'Processing', shipped: 'Shipped', delivered: 'Delivered', cancelled: 'Cancelled' }, rights: 'All rights reserved' },
};

function env(ctx) {
  const store = getSetting('store'), theme = getSetting('theme');
  let lang = ctx.cookies.lang === 'en' || ctx.cookies.lang === 'ar' ? ctx.cookies.lang : store.default_lang;
  if (!store.allow_lang_switch) lang = store.default_lang;
  const t = DICT[lang];
  const L = (o, f) => (lang === 'en' && o[`${f}_en`]) || o[f] || '';
  const sym = lang === 'en' ? store.currency_symbol_en || store.currency : store.currency_symbol;
  const fmt = (n) => `${Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 })} ${sym}`;
  return { store, theme, lang, t, L, fmt, sym, dir: lang === 'ar' ? 'rtl' : 'ltr' };
}

const img = (src) => esc(src || '/ph/charger.svg');
const href = (p) => `/products/${encodeURIComponent(p.handle)}`;

function card(p, e) {
  const sold = e.store.track_stock && p.stock <= 0;
  const off = p.compare_at > p.price ? Math.round((1 - p.price / p.compare_at) * 100) : 0;
  const from = p.variants.length && new Set(p.variants.map((v) => v.price)).size > 1 ? Math.min(...p.variants.map((v) => v.price)) : p.price;
  return `<a class="card" href="${href(p)}">
    <div class="card-img">${sold ? `<span class="badge dark">${e.t.sold_out}</span>` : off ? `<span class="badge">${off}% ${e.t.sale}</span>` : ''}
      <img loading="lazy" src="${img(p.images[0])}" alt="${esc(e.L(p, 'title'))}">${p.images[1] ? `<img class="alt" loading="lazy" src="${img(p.images[1])}" alt="">` : ''}</div>
    <div class="card-body"><h3>${esc(e.L(p, 'title'))}</h3>
    <div class="price">${from !== p.price ? '<small>' + (e.lang === 'ar' ? 'من' : 'From') + '</small> ' : ''}<b>${e.fmt(from)}</b>${p.compare_at > p.price ? `<s>${e.fmt(p.compare_at)}</s>` : ''}</div></div></a>`;
}

function productsQuery({ collection, sort = 'new', limit = 12, q = '', offset = 0 }) {
  const order = { new: 'id DESC', sold: 'sold DESC, id DESC', price_asc: 'price ASC', price_desc: 'price DESC' }[sort] || 'id DESC';
  let sql = "SELECT * FROM products WHERE status='active'"; const p = [];
  if (collection) { sql += ' AND EXISTS (SELECT 1 FROM json_each(products.collection_ids) WHERE value=?)'; p.push(collection); }
  if (q) { sql += ' AND (title LIKE ? OR title_en LIKE ? OR tags LIKE ? OR sku LIKE ?)'; p.push(...Array(4).fill(`%${q}%`)); }
  return all(`${sql} ORDER BY ${order} LIMIT ? OFFSET ?`, ...p, limit, offset).map(parseProduct);
}

function layout(ctx, e, { title, desc, body, image = '', path = '' }) {
  const { store, theme, t, L, lang } = e, c = theme.colors;
  const font = FONTS.includes(theme.font) ? theme.font : 'Cairo';
  const brand = L(store, 'name');
  const cols = all('SELECT * FROM collections WHERE visible=1 ORDER BY position,id LIMIT 8');
  const pages = all('SELECT handle,title,title_en FROM pages WHERE published=1');
  const social = Object.entries(store.social || {}).filter(([, v]) => v);
  const full = title ? `${title} – ${brand}` : `${brand} – ${L(store, 'tagline')}`;
  const logo = theme.logo ? `<img src="${esc(theme.logo)}" alt="${esc(brand)}" style="height:${Number(theme.logo_height) || 34}px">` : `<span class="logo-text">${esc(brand)}</span>`;
  const wa = store.whatsapp ? `<a class="wa" href="https://wa.me/${esc(store.whatsapp.replace(/\D/g, ''))}" target="_blank" rel="noopener" aria-label="WhatsApp">💬</a>` : '';
  return `<!doctype html><html lang="${lang}" dir="${e.dir}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(full)}</title><meta name="description" content="${esc(desc || store.meta_description)}">
<meta property="og:title" content="${esc(full)}"><meta property="og:description" content="${esc(desc || store.meta_description)}">${image ? `<meta property="og:image" content="${esc(image)}">` : ''}
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=${font}:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>:root{--primary:${esc(c.primary)};--accent:${esc(c.accent)};--bg:${esc(c.background)};--surface:${esc(c.surface)};--text:${esc(c.text)};--muted:${esc(c.muted)};--header-bg:${esc(c.header_bg)};--footer-bg:${esc(c.footer_bg)};--radius:${Number(theme.radius) || 12}px;--cols:${Number(theme.product_grid_cols) || 4};--font:'${font}',system-ui,sans-serif}</style>
<link rel="stylesheet" href="/store/store.css"></head><body>
${store.announcement ? `<div class="announce">${esc(L(store, 'announcement'))}</div>` : ''}
<header class="header${theme.header_sticky ? ' sticky' : ''}"><div class="wrap bar">
  <button class="burger" aria-label="menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
  <a class="logo" href="/">${logo}</a>
  <nav class="nav">${theme.menu.map((m) => `<a href="${esc(safeUrl(m.url))}">${esc(L(m, 'label'))}</a>`).join('')}</nav>
  <div class="icons">
    <form class="hsearch" action="/search"><input name="q" placeholder="${t.search_ph}" aria-label="${t.search}"></form>
    ${store.allow_lang_switch ? `<a class="ib" href="/lang/${lang === 'ar' ? 'en' : 'ar'}?r=${encodeURIComponent(ctx.url.pathname + ctx.url.search)}">${t.lang}</a>` : ''}
    <a class="ib cartbtn" href="/cart" aria-label="${t.cart}">🛒<span id="cart-count" class="count" hidden>0</span></a>
  </div></div></header>
<main>${body}</main>
<footer class="footer"><div class="wrap fgrid">
  <div><div class="logo-text">${esc(brand)}</div><p>${esc(L(store, 'tagline'))}</p><p class="muted">${esc(L(theme, 'footer_text'))}</p>
    ${social.length ? `<div class="social">${social.map(([k, v]) => `<a href="${esc(safeUrl(v))}" target="_blank" rel="noopener">${esc(k)}</a>`).join('')}</div>` : ''}</div>
  <div><h4>${t.collections}</h4>${cols.map((x) => `<a href="/collections/${encodeURIComponent(x.handle)}">${esc(L(x, 'title'))}</a>`).join('')}</div>
  <div><h4>${t.pages}</h4>${pages.map((x) => `<a href="/pages/${encodeURIComponent(x.handle)}">${esc(L(x, 'title'))}</a>`).join('')}
    ${store.phone ? `<a href="tel:${esc(store.phone)}">${esc(store.phone)}</a>` : ''}${store.email ? `<a href="mailto:${esc(store.email)}">${esc(store.email)}</a>` : ''}</div>
</div><div class="wrap copy">© ${new Date().getFullYear()} ${esc(brand)} · ${t.rights}</div></footer>
${wa}<div id="toast" class="toast"></div>
<script>window.NT=${JSON.stringify({ lang, sym: e.sym, t: { added: t.added, cart_empty: t.cart_empty, continue: t.continue, subtotal: t.subtotal, shipping: t.shipping, free: t.free, discount: t.discount, total: t.total, apply: t.apply, remove: t.remove, placing: t.placing, place: t.place, pick: t.pick } }).replace(/</g, '\\u003c')}</script>
<script src="/store/store.js" defer></script></body></html>`;
}

// ---------- homepage sections ----------
const SECTIONS = {
  hero: (s, e) => `<section class="hero" style="${s.image ? `background-image:linear-gradient(rgba(0,0,0,${(Number(s.overlay) || 0) / 100}),rgba(0,0,0,${(Number(s.overlay) || 0) / 100})),url('${esc(s.image)}')` : ''}">
    <div class="wrap hero-in"><h1>${esc(e.L(s, 'title'))}</h1><p>${esc(e.L(s, 'subtitle'))}</p>${s.button ? `<a class="btn accent lg" href="${esc(safeUrl(s.link))}">${esc(e.L(s, 'button'))}</a>` : ''}</div></section>`,
  features: (s, e) => `<section class="features wrap">${(s.items || []).map((i) => `<div class="feat"><span>${esc(i.icon)}</span><b>${esc(e.L(i, 'title'))}</b><small>${esc(e.L(i, 'text'))}</small></div>`).join('')}</section>`,
  collections: (s, e) => `<section class="wrap sec"><h2>${esc(e.L(s, 'title'))}</h2><div class="cgrid">${all('SELECT * FROM collections WHERE visible=1 ORDER BY position,id LIMIT ?', Number(s.limit) || 6)
    .map((c) => `<a class="ccard" href="/collections/${encodeURIComponent(c.handle)}"><img loading="lazy" src="${img(c.image)}" alt=""><span>${esc(e.L(c, 'title'))}</span></a>`).join('')}</div></section>`,
  products: (s, e) => {
    const col = s.collection ? get('SELECT id,handle FROM collections WHERE handle=?', s.collection) : null;
    const list = productsQuery({ collection: col?.id, sort: s.sort, limit: Number(s.limit) || 8 });
    if (!list.length) return '';
    return `<section class="wrap sec"><div class="sec-head"><h2>${esc(e.L(s, 'title'))}</h2><a href="/collections/${col ? encodeURIComponent(col.handle) : 'all'}">${e.t.view_all} ›</a></div><div class="grid">${list.map((p) => card(p, e)).join('')}</div></section>`;
  },
  banner: (s, e) => `<section class="wrap sec"><div class="banner" style="${s.image ? `background-image:linear-gradient(90deg,rgba(0,0,0,.65),rgba(0,0,0,.2)),url('${esc(s.image)}')` : ''}"><h2>${esc(e.L(s, 'title'))}</h2><p>${esc(e.L(s, 'text'))}</p>${s.button ? `<a class="btn light" href="${esc(safeUrl(s.link))}">${esc(e.L(s, 'button'))}</a>` : ''}</div></section>`,
  text: (s, e) => `<section class="wrap sec prose"><h2>${esc(e.L(s, 'title'))}</h2><div>${e.lang === 'en' && s.text_en ? s.text_en : s.text || ''}</div></section>`,
};

export function registerStore(router) {
  const notFound = (ctx) => { const e = env(ctx); ctx.html(layout(ctx, e, { title: e.t.not_found, body: `<div class="wrap empty"><h1>404</h1><p>${e.t.not_found}</p><a class="btn" href="/">${e.t.back}</a></div>` }), 404); };
  router.notFound = notFound;

  router.get('/', (ctx) => {
    const e = env(ctx);
    ctx.html(layout(ctx, e, { body: e.theme.sections.filter((s) => s.enabled !== false && SECTIONS[s.type]).map((s) => SECTIONS[s.type](s, e)).join('') }));
  });
  router.get('/lang/:l', (ctx) => {
    ctx.cookie('lang', ctx.params.l === 'en' ? 'en' : 'ar', { maxAge: 365 * 86400, httpOnly: false });
    const r = ctx.query.r || '/'; ctx.redirect(r.startsWith('/') && !r.startsWith('//') ? r : '/');
  });
  router.get('/ph/:file', (ctx) => ctx.send(200, 'image/svg+xml', placeholderSvg(ctx.params.file.replace(/\.svg$/, ''), ctx.query.c), { 'Cache-Control': 'public, max-age=86400' }));

  router.get('/collections', (ctx) => ctx.redirect('/collections/all'));
  router.get('/collections/:handle', (ctx) => {
    const e = env(ctx), { handle } = ctx.params;
    const col = handle === 'all' ? { id: 0, title: e.t.all, title_en: e.t.all, description: '' } : get('SELECT * FROM collections WHERE handle=?', handle);
    if (!col) return notFound(ctx);
    const sort = ctx.query.sort || 'new', page = Math.max(1, Number(ctx.query.page) || 1), per = 24;
    const list = productsQuery({ collection: col.id || 0, sort, limit: per + 1, offset: (page - 1) * per });
    const more = list.length > per; if (more) list.pop();
    const opts = [['new', e.t.newest], ['sold', e.t.best], ['price_asc', e.t.price_asc], ['price_desc', e.t.price_desc]];
    const qs = (o) => `?${new URLSearchParams({ sort, ...o })}`;
    ctx.html(layout(ctx, e, { title: e.L(col, 'title'), desc: e.L(col, 'description'), body: `<div class="wrap sec"><div class="crumbs"><a href="/">${e.t.home}</a> › ${esc(e.L(col, 'title'))}</div>
      <div class="sec-head"><div><h1>${esc(e.L(col, 'title'))}</h1><p class="muted">${esc(e.L(col, 'description'))}</p></div>
      <form><label class="muted">${e.t.sort}: <select name="sort" onchange="this.form.submit()">${opts.map(([v, l]) => `<option value="${v}"${v === sort ? ' selected' : ''}>${l}</option>`).join('')}</select></label></form></div>
      ${list.length ? `<div class="grid">${list.map((p) => card(p, e)).join('')}</div>` : `<p class="empty">${e.t.no_products}</p>`}
      <div class="pager">${page > 1 ? `<a class="btn" href="${qs({ page: page - 1 })}">‹</a>` : ''}${more ? `<a class="btn" href="${qs({ page: page + 1 })}">›</a>` : ''}</div></div>` }));
  });

  router.get('/search', (ctx) => {
    const e = env(ctx), q = (ctx.query.q || '').trim().slice(0, 100);
    const list = q ? productsQuery({ q, limit: 48 }) : [];
    ctx.html(layout(ctx, e, { title: e.t.search, body: `<div class="wrap sec"><form class="bigsearch" action="/search"><input name="q" value="${esc(q)}" placeholder="${e.t.search_ph}" autofocus><button class="btn">${e.t.search}</button></form>
      ${q ? `<h2>${e.t.results} “${esc(q)}”</h2>${list.length ? `<div class="grid">${list.map((p) => card(p, e)).join('')}</div>` : `<p class="empty">${e.t.no_products}</p>`}` : ''}</div>` }));
  });

  router.get('/products/:handle', (ctx) => {
    const e = env(ctx);
    const p = parseProduct(get("SELECT * FROM products WHERE handle=? AND status='active'", ctx.params.handle));
    if (!p) return notFound(ctx);
    const sold = e.store.track_stock && p.stock <= 0;
    const imgs = p.images.length ? p.images : ['/ph/charger.svg'];
    const rel = p.collection_ids[0] ? productsQuery({ collection: p.collection_ids[0], limit: 5 }).filter((x) => x.id !== p.id).slice(0, 4) : [];
    const off = p.compare_at > p.price ? Math.round((1 - p.price / p.compare_at) * 100) : 0;
    const data = { id: p.id, handle: p.handle, title: e.L(p, 'title'), price: p.price, image: imgs[0], variants: p.variants, track: e.store.track_stock, stock: p.stock };
    const desc = e.lang === 'en' && p.description_en ? p.description_en : p.description;
    ctx.html(layout(ctx, e, { title: e.L(p, 'title'), desc: desc.slice(0, 160), image: imgs[0], body: `<div class="wrap sec"><div class="crumbs"><a href="/">${e.t.home}</a> › <a href="/collections/all">${e.t.all}</a> › ${esc(e.L(p, 'title'))}</div>
      <div class="pdp"><div class="gallery"><div class="main"><img id="mainimg" src="${img(imgs[0])}" alt="${esc(e.L(p, 'title'))}"></div>
        ${imgs.length > 1 ? `<div class="thumbs">${imgs.map((s) => `<img src="${img(s)}" alt="" onclick="document.getElementById('mainimg').src=this.src">`).join('')}</div>` : ''}</div>
      <div class="info"><div class="muted">${esc(p.vendor)}</div><h1>${esc(e.L(p, 'title'))}</h1>
        <div class="price big"><b id="pprice">${e.fmt(p.price)}</b>${p.compare_at > p.price ? `<s>${e.fmt(p.compare_at)}</s><span class="badge">${off}% ${e.t.sale}</span>` : ''}</div>
        <p class="stock ${sold ? 'out' : p.stock <= 5 ? 'low' : 'ok'}">${sold ? e.t.sold_out : p.stock <= 5 && e.store.track_stock ? e.t.low : e.t.in_stock}</p>
        ${p.variants.length ? `<label class="field">${e.t.variant}<div class="opts" id="opts">${p.variants.map((v, i) => `<button type="button" class="opt${i === 0 ? ' on' : ''}" data-i="${i}"${e.store.track_stock && v.stock <= 0 ? ' disabled' : ''}>${esc(v.title)}</button>`).join('')}</div></label>` : ''}
        <div class="buyrow"><div class="qty"><button type="button" id="qm">−</button><input id="qty" value="1" inputmode="numeric"><button type="button" id="qp">+</button></div>
        <button class="btn accent lg grow" id="addbtn"${sold ? ' disabled' : ''}>${e.t.add}</button></div>
        <button class="btn lg block" id="buybtn"${sold ? ' disabled' : ''}>${e.t.buy}</button>
        <div class="prose desc"><h3>${e.t.desc}</h3>${esc(desc).replace(/\n/g, '<br>')}</div></div></div>
      ${rel.length ? `<h2 class="mt">${e.t.related}</h2><div class="grid">${rel.map((x) => card(x, e)).join('')}</div>` : ''}</div>
      <script type="application/json" id="pdata">${JSON.stringify(data).replace(/</g, '\\u003c')}</script>` }));
  });

  router.get('/cart', (ctx) => {
    const e = env(ctx), t = e.t;
    const f = (id, label, extra = '') => `<label class="field">${label}<input id="f-${id}" name="${id}" ${extra}></label>`;
    ctx.html(layout(ctx, e, { title: t.cart, body: `<div class="wrap sec"><h1>${t.cart}</h1>
      <div id="cart-empty" class="empty" hidden><p>${t.cart_empty}</p><a class="btn" href="/collections/all">${t.continue}</a></div>
      <div id="cart-full" class="cartgrid" hidden><div><div id="lines"></div>
        <form id="checkout" class="box"><h3>${t.checkout}</h3><div class="two">${f('name', t.name, 'required autocomplete="name"')}${f('phone', t.phone, 'required type="tel" autocomplete="tel"')}</div>
          <div class="two">${f('city', t.city, 'required autocomplete="address-level1"')}${f('email', t.email, 'type="email" autocomplete="email"')}</div>
          ${f('address', t.address, 'required autocomplete="street-address"')}${f('notes', t.notes)}
          <div class="pay">💵 <b>${t.cod}</b></div><div id="cerr" class="err" hidden></div>
          <button class="btn accent lg block" id="placebtn">${t.place}</button></form></div>
        <aside class="box sum"><div class="promo"><input id="code" placeholder="${t.promo}"><button type="button" class="btn" id="applybtn">${t.apply}</button></div><div id="perr" class="err" hidden></div><div id="sumrows"></div></aside></div></div>` }));
  });

  router.get('/order/:token', (ctx) => {
    const e = env(ctx), o = get('SELECT * FROM orders WHERE token=?', ctx.params.token);
    if (!o) return notFound(ctx);
    const items = JSON.parse(o.items);
    ctx.html(layout(ctx, e, { title: e.t.thanks, body: `<div class="wrap sec narrow"><div class="box center"><div class="tick">✓</div><h1>${e.t.thanks}</h1>
      <p>${e.t.order_no}: <b>#${o.number}</b> · ${e.t.status}: <b>${e.t.st[o.status] || o.status}</b></p><p class="muted">${e.t.we_call}</p>
      <div class="lines">${items.map((i) => `<div class="line"><img src="${img(i.image)}" alt=""><div class="grow"><b>${esc(e.lang === 'en' && i.title_en ? i.title_en : i.title)}</b><small>${esc(i.variant)} × ${i.qty}</small></div><b>${e.fmt(i.price * i.qty)}</b></div>`).join('')}</div>
      <div class="sumrow"><span>${e.t.shipping}</span><span>${o.shipping ? e.fmt(o.shipping) : e.t.free}</span></div>
      ${o.discount ? `<div class="sumrow"><span>${e.t.discount}</span><span>-${e.fmt(o.discount)}</span></div>` : ''}
      <div class="sumrow total"><span>${e.t.total}</span><span>${e.fmt(o.total)}</span></div><a class="btn" href="/">${e.t.continue}</a></div></div>` }));
  });

  router.get('/pages/:handle', (ctx) => {
    const e = env(ctx), pg = get('SELECT * FROM pages WHERE handle=? AND published=1', ctx.params.handle);
    if (!pg) return notFound(ctx);
    ctx.html(layout(ctx, e, { title: e.L(pg, 'title'), body: `<div class="wrap sec narrow prose"><h1>${esc(e.L(pg, 'title'))}</h1>${e.lang === 'en' && pg.body_en ? pg.body_en : pg.body}</div>` }));
  });

  router.get('/robots.txt', (ctx) => ctx.send(200, 'text/plain', `User-agent: *\nDisallow: /admin\nDisallow: /api\nSitemap: ${ctx.url.origin}/sitemap.xml\n`));
  router.get('/sitemap.xml', (ctx) => {
    const o = ctx.url.origin, u = ['/', '/collections/all',
      ...all('SELECT handle FROM collections WHERE visible=1').map((c) => `/collections/${encodeURIComponent(c.handle)}`),
      ...all("SELECT handle FROM products WHERE status='active'").map((p) => `/products/${encodeURIComponent(p.handle)}`),
      ...all('SELECT handle FROM pages WHERE published=1').map((p) => `/pages/${encodeURIComponent(p.handle)}`)];
    ctx.send(200, 'application/xml', `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${u.map((x) => `<url><loc>${esc(o + x)}</loc></url>`).join('')}</urlset>`);
  });
}
