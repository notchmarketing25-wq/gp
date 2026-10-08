(() => {
const $app = document.getElementById('app');
const ST = { new: 'جديد', processing: 'قيد التجهيز', shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي' };
const PAY = { pending: 'غير مدفوع', paid: 'مدفوع', refunded: 'مسترد' };
let ME = null, SETTINGS = null, COLS = [];

// ---------- helpers ----------
function h(tag, props, ...kids) {
  const el = document.createElement(tag);
  for (const [k, v] of Object.entries(props || {})) {
    if (v == null || v === false) continue;
    if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v);
    else if (k === 'class') el.className = v;
    else if (k in el) el[k] = v; else el.setAttribute(k, v === true ? '' : v);
  }
  el.append(...kids.flat(Infinity).filter((x) => x != null && x !== false).map((x) => (x instanceof Node ? x : document.createTextNode(x))));
  return el;
}
const sym = () => SETTINGS?.store?.currency_symbol || 'ج.م';
const money = (n) => `${Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 })} ${sym()}`;
const date = (s) => new Date(String(s).replace(' ', 'T') + 'Z').toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' });
function toast(msg, bad) { const t = document.getElementById('toast'); t.textContent = msg; t.className = 'on' + (bad ? ' bad' : ''); clearTimeout(toast.t); toast.t = setTimeout(() => (t.className = ''), 2500); }
async function api(method, url, body) {
  const r = await fetch(url, { method, headers: body ? { 'Content-Type': 'application/json' } : { 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined });
  const d = await r.json().catch(() => ({}));
  if (r.status === 401 && ME) { ME = null; boot(); throw new Error(d.error); }
  if (!r.ok) throw new Error(d.error || 'حدث خطأ');
  return d;
}
const guarded = (fn) => async (...a) => { try { return await fn(...a); } catch (e) { toast(e.message, true); } };

function field(state, k, label, o = {}) {
  const t = o.type || 'text'; let input;
  if (t === 'check') return h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: !!state[k], onchange: (e) => (state[k] = e.target.checked) }), label);
  if (t === 'image') return imageField(state, k, label);
  if (t === 'textarea') { input = h('textarea', { rows: o.rows || 3, oninput: (e) => (state[k] = e.target.value) }); input.value = state[k] ?? ''; }
  else if (t === 'select') { input = h('select', { onchange: (e) => (state[k] = e.target.value) }, o.options.map(([v, l]) => h('option', { value: v }, l))); input.value = state[k] ?? ''; }
  else if (t === 'number') { input = h('input', { type: 'number', step: o.step || 'any', oninput: (e) => (state[k] = e.target.value === '' ? '' : Number(e.target.value)) }); input.value = state[k] ?? ''; }
  else { input = h('input', { type: t, oninput: (e) => (state[k] = e.target.value) }); input.value = state[k] ?? ''; if (o.dir) input.dir = o.dir; }
  return h('label', { class: 'fld' }, h('span', {}, label), input, o.hint ? h('small', {}, o.hint) : null);
}
const row = (...f) => h('div', { class: 'row' }, f);

async function uploadFile(file) {
  const data = await new Promise((res, rej) => { const fr = new FileReader(); fr.onload = () => res(fr.result); fr.onerror = rej; fr.readAsDataURL(file); });
  let out = data;
  if (/^data:image\/(png|jpeg|webp)/.test(data)) { // تصغير الصور الكبيرة
    const im = await new Promise((r) => { const i = new Image(); i.onload = () => r(i); i.src = data; });
    const s = Math.min(1, 1800 / Math.max(im.width, im.height));
    if (s < 1 || file.size > 1.5e6) {
      const c = h('canvas', { width: Math.round(im.width * s), height: Math.round(im.height * s) });
      c.getContext('2d').drawImage(im, 0, 0, c.width, c.height);
      out = c.toDataURL(file.type === 'image/png' ? 'image/png' : 'image/jpeg', 0.9);
    }
  }
  return (await api('POST', '/api/admin/upload', { data: out })).url;
}
function pickFiles(multi, cb) {
  const i = h('input', { type: 'file', accept: 'image/png,image/jpeg,image/webp,image/gif', multiple: multi, onchange: guarded(async () => { for (const f of i.files) cb(await uploadFile(f)); }) });
  i.click();
}
function imageField(state, k, label) {
  const box = h('div', { class: 'fld' });
  const draw = () => box.replaceChildren(h('span', {}, label), h('div', { class: 'one' },
    state[k] ? h('div', { class: 'imgbox' }, h('img', { src: state[k] })) : null,
    h('input', { placeholder: 'رابط الصورة أو ارفع صورة', value: state[k] || '', oninput: (e) => (state[k] = e.target.value) }),
    h('button', { class: 'btn', type: 'button', onclick: () => pickFiles(false, (u) => { state[k] = u; draw(); }) }, '⬆ رفع'),
    state[k] ? h('button', { class: 'btn bad', type: 'button', onclick: () => { state[k] = ''; draw(); } }, '✕') : null));
  draw(); return box;
}
function imagesField(state, k) {
  const box = h('div', { class: 'imgs' });
  const draw = () => box.replaceChildren(...state[k].map((u, i) => h('div', { class: 'imgbox' }, h('img', { src: u }),
    h('button', { type: 'button', onclick: () => { state[k].splice(i, 1); draw(); } }, '✕'),
    i > 0 ? h('button', { class: 'mv', type: 'button', title: 'تقديم', onclick: () => { state[k].splice(i - 1, 0, state[k].splice(i, 1)[0]); draw(); } }, '→') : null)),
    h('div', { class: 'upl', onclick: () => pickFiles(true, (u) => { state[k].push(u); draw(); }) }, '+ رفع صور'));
  draw(); return box;
}
function modal(title, body, onSave) {
  const m = h('div', { class: 'mod', onmousedown: (e) => e.target === m && m.remove() }, h('div', {}, h('h2', {}, title), body,
    h('div', { class: 'acts' }, h('button', { class: 'btn', onclick: () => m.remove() }, 'إلغاء'),
      h('button', { class: 'btn pri', onclick: guarded(async () => { await onSave(); m.remove(); }) }, 'حفظ'))));
  document.body.append(m);
}
const pill = (s, map = ST) => h('span', { class: 'pill ' + s }, map[s] || s);
const head = (title, ...acts) => h('div', { class: 'top' }, h('h1', {}, title), h('div', { class: 'sp2' }, acts));
const tbl = (cols, rows, onClick) => h('div', { class: 'card flush' }, rows.length ? h('table', {}, h('thead', {}, h('tr', {}, cols.map((c) => h('th', {}, c[0])))),
  h('tbody', {}, rows.map((r) => h('tr', { class: onClick ? 'click' : '', onclick: onClick && (() => onClick(r)) }, cols.map((c) => h('td', {}, c[1](r))))))) : h('p', { class: 'muted', style: 'padding:30px;text-align:center' }, 'لا توجد بيانات'));

// ---------- pages ----------
async function dashboard() {
  const s = await api('GET', '/api/admin/stats');
  const max = Math.max(1, ...s.series.map((x) => x.s));
  const chart = h('div', { style: 'display:flex;align-items:end;gap:6px;height:150px' }, s.series.map((x) =>
    h('div', { title: `${x.d}: ${money(x.s)} (${x.n} طلب)`, style: `flex:1;background:var(--accent);border-radius:4px 4px 0 0;opacity:.85;height:${Math.max(3, (x.s / max) * 100)}%` })));
  return h('div', {}, head('الرئيسية'),
    ME.defaultPassword ? h('div', { class: 'alert' }, '⚠️ ما زالت كلمة المرور الافتراضية مستخدمة. ', h('a', { href: '#/settings', style: 'text-decoration:underline' }, 'غيّرها الآن')) : null,
    h('div', { class: 'grid g4' }, [['مبيعات اليوم', money(s.today_sales)], ['إجمالي المبيعات', money(s.total_sales)], ['الطلبات', `${s.orders} (${s.new_orders} جديد)`], ['متوسط الطلب', money(s.avg)]]
      .map(([l, v]) => h('div', { class: 'card stat' }, h('small', {}, l), h('b', {}, v)))),
    h('div', { class: 'grid g3' }, h('div', { class: 'card' }, h('h2', {}, 'المبيعات آخر 14 يوم'), chart),
      h('div', { class: 'card' }, h('h2', {}, 'الأكثر مبيعًا'), s.top.map((p) => h('div', { class: 'sp2', style: 'justify-content:space-between;padding:4px 0' }, h('a', { href: `#/products/${p.id}` }, p.title), h('b', {}, p.sold))))),
    h('div', { class: 'grid g2' }, h('div', { class: 'card' }, h('h2', {}, 'آخر الطلبات'), s.recent.length ? s.recent.map((o) => h('a', { class: 'sp2', href: `#/orders/${o.id}`, style: 'justify-content:space-between;padding:5px 0' }, `#${o.number} ${o.name}`, pill(o.status), money(o.total))) : h('p', { class: 'muted' }, 'لا توجد طلبات بعد')),
      h('div', { class: 'card' }, h('h2', {}, 'مخزون منخفض'), s.low_stock.length ? s.low_stock.map((p) => h('a', { class: 'sp2', href: `#/products/${p.id}`, style: 'justify-content:space-between;padding:5px 0' }, p.title, h('b', { style: p.stock <= 0 ? 'color:var(--bad)' : '' }, p.stock))) : h('p', { class: 'muted' }, 'كل شيء متوفر ✓'))));
}

async function orders() {
  let status = '', q = '';
  const box = h('div');
  const load = guarded(async () => {
    const list = await api('GET', `/api/admin/orders?status=${status}&q=${encodeURIComponent(q)}`);
    box.replaceChildren(tbl([['الطلب', (o) => '#' + o.number], ['التاريخ', (o) => date(o.created_at)], ['العميل', (o) => o.name], ['الإجمالي', (o) => money(o.total)], ['الدفع', (o) => pill(o.payment_status, PAY)], ['الحالة', (o) => pill(o.status)]], list, (o) => (location.hash = `#/orders/${o.id}`)));
  });
  const chips = h('div', { class: 'sp2' }, [['', 'الكل'], ...Object.entries(ST)].map(([v, l]) => h('button', { class: 'chip' + (v === status ? ' on' : ''), onclick: (e) => { status = v; chips.querySelectorAll('.chip').forEach((c) => c.classList.remove('on')); e.target.classList.add('on'); load(); } }, l)));
  const search = h('input', { placeholder: 'بحث بالاسم / الموبايل / رقم الطلب', oninput: (e) => { q = e.target.value; clearTimeout(search.t); search.t = setTimeout(load, 300); } });
  load();
  return h('div', {}, head('الطلبات'), h('div', { class: 'bar' }, chips, search), box);
}
async function orderDetail(id) {
  const o = await api('GET', `/api/admin/orders/${id}`), st = { status: o.status, payment_status: o.payment_status, admin_note: o.admin_note };
  return h('div', {}, head(`طلب #${o.number}`, h('button', { class: 'btn', onclick: () => print() }, '🖨 طباعة'), h('a', { class: 'btn', href: '#/orders' }, 'رجوع')),
    h('div', { class: 'grid g3' }, h('div', {},
      h('div', { class: 'card flush' }, h('table', {}, h('thead', {}, h('tr', {}, ['المنتج', 'السعر', 'الكمية', 'الإجمالي'].map((x) => h('th', {}, x)))),
        h('tbody', {}, o.items.map((i) => h('tr', {}, h('td', {}, h('img', { class: 'th', src: i.image }), ' ', i.title, i.variant ? ` (${i.variant})` : ''), h('td', {}, money(i.price)), h('td', {}, i.qty), h('td', {}, money(i.price * i.qty))))))),
      h('div', { class: 'card' }, h('div', { class: 'kv' }, h('span', {}, 'المجموع الفرعي'), h('span', {}, money(o.subtotal)), h('span', {}, 'الشحن'), h('span', {}, money(o.shipping)),
        o.discount ? [h('span', {}, `خصم ${o.discount_code}`), h('span', {}, '-' + money(o.discount))] : null, h('span', {}, h('b', {}, 'الإجمالي')), h('b', {}, money(o.total))))),
      h('div', {}, h('div', { class: 'card' }, h('h2', {}, 'العميل'), h('div', { class: 'kv' }, h('span', {}, 'الاسم'), h('span', {}, o.name), h('span', {}, 'الموبايل'), h('a', { href: `tel:${o.phone}` }, o.phone),
        h('span', {}, 'المدينة'), h('span', {}, o.city), h('span', {}, 'العنوان'), h('span', {}, o.address), o.email ? [h('span', {}, 'البريد'), h('span', {}, o.email)] : null, o.notes ? [h('span', {}, 'ملاحظات'), h('span', {}, o.notes)] : null),
        h('p', {}, h('a', { class: 'btn sm noprint', target: '_blank', href: `https://wa.me/${o.phone.replace(/\D/g, '').replace(/^0/, '20')}` }, 'واتساب'))),
      h('div', { class: 'card noprint' }, h('h2', {}, 'إدارة الطلب'), field(st, 'status', 'حالة الطلب', { type: 'select', options: Object.entries(ST) }), field(st, 'payment_status', 'حالة الدفع', { type: 'select', options: Object.entries(PAY) }),
        field(st, 'admin_note', 'ملاحظة داخلية', { type: 'textarea' }), h('button', { class: 'btn pri', onclick: guarded(async () => { await api('PUT', `/api/admin/orders/${id}`, st); toast('تم الحفظ'); }) }, 'حفظ')))));
}

async function products() {
  let q = ''; const box = h('div');
  const load = guarded(async () => {
    const list = await api('GET', `/api/admin/products?q=${encodeURIComponent(q)}`);
    box.replaceChildren(tbl([['', (p) => h('img', { class: 'th', src: p.images[0] || '/ph/charger.svg' })], ['المنتج', (p) => p.title], ['الحالة', (p) => pill(p.status, { active: 'نشط', draft: 'مسودة' })],
      ['المخزون', (p) => h('span', { style: p.stock <= 5 ? 'color:var(--bad);font-weight:700' : '' }, p.stock)], ['السعر', (p) => money(p.price)], ['المبيعات', (p) => p.sold]], list, (p) => (location.hash = `#/products/${p.id}`)));
  });
  load();
  return h('div', {}, head('المنتجات', h('a', { class: 'btn pri', href: '#/products/new' }, '+ منتج جديد')), h('div', { class: 'bar' }, h('input', { placeholder: 'بحث…', oninput: (e) => { q = e.target.value; clearTimeout(load.t); load.t = setTimeout(load, 300); } })), box);
}
async function productEdit(id) {
  const isNew = id === 'new';
  const p = isNew ? { title: '', title_en: '', description: '', description_en: '', vendor: 'NOTCH', price: '', compare_at: '', sku: '', stock: 0, status: 'active', images: [], variants: [], tags: [], collection_ids: [] } : await api('GET', `/api/admin/products/${id}`);
  const tags = { v: (p.tags || []).join(', ') };
  const vbox = h('div');
  const drawV = () => vbox.replaceChildren(...p.variants.map((v, i) => h('div', { class: 'rowline' }, field(v, 'title', 'الاسم (لون/حجم)'), field(v, 'price', 'السعر', { type: 'number' }), field(v, 'stock', 'المخزون', { type: 'number' }), field(v, 'sku', 'SKU'),
    h('button', { class: 'btn bad sm', onclick: () => { p.variants.splice(i, 1); drawV(); } }, '✕'))),
    h('button', { class: 'btn', onclick: () => { p.variants.push({ title: '', price: p.price || 0, stock: 0, sku: '' }); drawV(); } }, '+ إضافة نوع'));
  drawV();
  const colsBox = h('div', { class: 'sp2' }, COLS.map((c) => h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: p.collection_ids.includes(c.id), onchange: (e) => { p.collection_ids = e.target.checked ? [...p.collection_ids, c.id] : p.collection_ids.filter((x) => x !== c.id); } }), c.title)));
  const save = guarded(async () => {
    const body = { ...p, tags: tags.v.split(',').map((x) => x.trim()).filter(Boolean) };
    const r = isNew ? await api('POST', '/api/admin/products', body) : await api('PUT', `/api/admin/products/${id}`, body);
    toast('تم الحفظ'); if (isNew) location.hash = `#/products/${r.id}`;
  });
  return h('div', {}, head(isNew ? 'منتج جديد' : p.title, !isNew ? h('a', { class: 'btn', target: '_blank', href: `/products/${encodeURIComponent(p.handle)}` }, 'عرض ↗') : null,
    !isNew ? h('button', { class: 'btn bad', onclick: guarded(async () => { if (confirm('حذف المنتج نهائيًا؟')) { await api('DELETE', `/api/admin/products/${id}`); location.hash = '#/products'; } }) }, 'حذف') : null, h('button', { class: 'btn pri', onclick: save }, 'حفظ')),
    h('div', { class: 'grid g3' }, h('div', {},
      h('div', { class: 'card' }, field(p, 'title', 'اسم المنتج (عربي)'), field(p, 'title_en', 'اسم المنتج (English)', { dir: 'ltr' }), field(p, 'description', 'الوصف (عربي)', { type: 'textarea', rows: 6 }), field(p, 'description_en', 'Description (English)', { type: 'textarea', rows: 5 })),
      h('div', { class: 'card' }, h('h2', {}, 'الصور (الأولى هي الرئيسية)'), imagesField(p, 'images')),
      h('div', { class: 'card' }, h('h2', {}, 'الأنواع (ألوان / أحجام)'), h('p', { class: 'muted' }, 'اختياري. عند إضافة أنواع يصبح المخزون مجموع مخزون الأنواع.'), vbox)),
      h('div', {}, h('div', { class: 'card' }, field(p, 'status', 'الحالة', { type: 'select', options: [['active', 'نشط'], ['draft', 'مسودة (مخفي)']] }), field(p, 'vendor', 'العلامة'), field(p, 'tags', ''), ),
      h('div', { class: 'card' }, h('h2', {}, 'التسعير والمخزون'), row(field(p, 'price', `السعر (${sym()})`, { type: 'number' }), field(p, 'compare_at', 'السعر قبل الخصم', { type: 'number' })), row(field(p, 'sku', 'SKU'), field(p, 'stock', 'المخزون', { type: 'number', hint: 'يُهمل إن وُجدت أنواع' }))),
      h('div', { class: 'card' }, h('h2', {}, 'المجموعات'), colsBox, field(tags, 'v', 'وسوم (مفصولة بفاصلة)')))));
}

function simple({ name, title, cols, fields, blank, after }) {
  return async () => {
    const list = await api('GET', `/api/admin/${name}`); const box = h('div');
    const edit = (item) => { const s = { ...blank, ...item }; modal(item ? 'تعديل' : 'جديد', h('div', {}, fields.map(([k, l, o]) => field(s, k, l, o))), async () => {
      item ? await api('PUT', `/api/admin/${name}/${item.id}`, s) : await api('POST', `/api/admin/${name}`, s); toast('تم الحفظ'); await after?.(); route(); });
      if (item) document.querySelector('.acts').prepend(h('button', { class: 'btn bad', style: 'margin-inline-end:auto', onclick: guarded(async () => { if (confirm('حذف؟')) { await api('DELETE', `/api/admin/${name}/${item.id}`); await after?.(); document.querySelector('.mod')?.remove(); route(); } }) }, 'حذف')); };
    box.append(tbl(cols, list, edit));
    return h('div', {}, head(title, h('button', { class: 'btn pri', onclick: () => edit(null) }, '+ جديد')), box);
  };
}
const collections = simple({ name: 'collections', title: 'المجموعات', blank: { title: '', title_en: '', description: '', image: '', position: 0, visible: 1 }, after: async () => (COLS = await api('GET', '/api/admin/collections')),
  cols: [['', (c) => h('img', { class: 'th', src: c.image || '/ph/charger.svg' })], ['الاسم', (c) => c.title], ['الرابط', (c) => '/collections/' + c.handle], ['الترتيب', (c) => c.position], ['ظاهرة', (c) => (c.visible ? '✓' : '—')]],
  fields: [['title', 'الاسم (عربي)'], ['title_en', 'الاسم (English)'], ['description', 'الوصف', { type: 'textarea' }], ['image', 'الصورة', { type: 'image' }], ['position', 'الترتيب', { type: 'number' }], ['visible', 'ظاهرة في المتجر', { type: 'check' }]] });
const discounts = simple({ name: 'discounts', title: 'أكواد الخصم', blank: { code: '', type: 'percent', value: 10, min_subtotal: 0, max_uses: 0, active: 1 },
  cols: [['الكود', (d) => h('b', {}, d.code)], ['القيمة', (d) => (d.type === 'percent' ? d.value + '%' : money(d.value))], ['حد أدنى', (d) => money(d.min_subtotal)], ['الاستخدام', (d) => `${d.uses}${d.max_uses ? ' / ' + d.max_uses : ''}`], ['الحالة', (d) => pill(d.active ? 'active' : 'draft', { active: 'فعّال', draft: 'متوقف' })]],
  fields: [['code', 'الكود (مثال: SAVE10)', { dir: 'ltr' }], ['type', 'النوع', { type: 'select', options: [['percent', 'نسبة %'], ['fixed', 'مبلغ ثابت']] }], ['value', 'القيمة', { type: 'number' }], ['min_subtotal', 'حد أدنى للطلب', { type: 'number' }], ['max_uses', 'أقصى عدد استخدامات (0 = بلا حد)', { type: 'number' }], ['active', 'فعّال', { type: 'check' }]] });
const pages = simple({ name: 'pages', title: 'الصفحات', blank: { title: '', title_en: '', body: '', body_en: '', published: 1 },
  cols: [['العنوان', (p) => p.title], ['الرابط', (p) => '/pages/' + p.handle], ['الحالة', (p) => pill(p.published ? 'active' : 'draft', { active: 'منشورة', draft: 'مخفية' })]],
  fields: [['title', 'العنوان (عربي)'], ['title_en', 'Title (English)'], ['body', 'المحتوى (HTML مسموح)', { type: 'textarea', rows: 8 }], ['body_en', 'Content (English)', { type: 'textarea', rows: 6 }], ['published', 'منشورة', { type: 'check' }]] });

async function customers() {
  const list = await api('GET', '/api/admin/customers');
  return h('div', {}, head('العملاء'), tbl([['الاسم', (c) => c.name], ['الموبايل', (c) => c.phone], ['المدينة', (c) => c.city], ['الطلبات', (c) => c.orders], ['إجمالي الإنفاق', (c) => money(c.spent)], ['آخر طلب', (c) => date(c.last_order)]], list));
}

// ---------- theme editor ----------
const SEC_TYPES = { hero: 'بانر رئيسي', features: 'مميزات', collections: 'شبكة المجموعات', products: 'منتجات', banner: 'بانر ترويجي', text: 'نص حر' };
const SEC_FIELDS = {
  hero: [['title', 'العنوان'], ['title_en', 'Title'], ['subtitle', 'الوصف', { type: 'textarea' }], ['subtitle_en', 'Subtitle', { type: 'textarea' }], ['button', 'نص الزر'], ['button_en', 'Button'], ['link', 'رابط الزر', { dir: 'ltr' }], ['image', 'صورة الخلفية', { type: 'image' }], ['overlay', 'تعتيم الصورة %', { type: 'number' }]],
  collections: [['title', 'العنوان'], ['title_en', 'Title'], ['limit', 'العدد', { type: 'number' }]],
  products: [['title', 'العنوان'], ['title_en', 'Title'], ['collection', 'المجموعة', { type: 'select', options: () => [['', 'كل المنتجات'], ...COLS.map((c) => [c.handle, c.title])] }], ['sort', 'الترتيب', { type: 'select', options: [['sold', 'الأكثر مبيعًا'], ['new', 'الأحدث'], ['price_asc', 'السعر ↑'], ['price_desc', 'السعر ↓']] }], ['limit', 'العدد', { type: 'number' }]],
  banner: [['title', 'العنوان'], ['title_en', 'Title'], ['text', 'النص', { type: 'textarea' }], ['text_en', 'Text', { type: 'textarea' }], ['button', 'نص الزر'], ['button_en', 'Button'], ['link', 'الرابط', { dir: 'ltr' }], ['image', 'الصورة', { type: 'image' }]],
  text: [['title', 'العنوان'], ['title_en', 'Title'], ['text', 'المحتوى (HTML)', { type: 'textarea', rows: 5 }], ['text_en', 'Content', { type: 'textarea', rows: 4 }]],
};
async function theme() {
  const S = await api('GET', '/api/admin/settings'), T = S.theme; let tab = 'look';
  const frame = h('iframe', { src: '/' }), body = h('div'), tabs = h('div', { class: 'tabs' });
  const colorLabels = { primary: 'اللون الأساسي', accent: 'لون التمييز (الأزرار)', background: 'الخلفية', surface: 'خلفية البطاقات', text: 'لون النص', muted: 'نص ثانوي', header_bg: 'خلفية الهيدر', footer_bg: 'خلفية الفوتر' };
  const listEditor = (arr, fields, blank) => { const b = h('div'); const d = () => b.replaceChildren(...arr.map((it, i) => h('div', { class: 'rowline' }, fields.map(([k, l, o]) => field(it, k, l, o)), h('button', { class: 'btn bad sm', onclick: () => { arr.splice(i, 1); d(); } }, '✕'))), h('button', { class: 'btn', onclick: () => { arr.push({ ...blank }); d(); } }, '+ إضافة')); d(); return b; };
  const sections = () => {
    const wrap = h('div'); const open = new Set();
    const d = () => wrap.replaceChildren(...T.sections.map((s, i) => h('div', { class: 'sec' + (s.enabled === false ? ' off' : '') },
      h('div', { class: 'hd', onclick: () => { open.has(s.id) ? open.delete(s.id) : open.add(s.id); d(); } }, h('b', {}, `${SEC_TYPES[s.type] || s.type}${s.title ? ' — ' + s.title : ''}`),
        h('button', { class: 'btn sm', title: 'أعلى', onclick: (e) => { e.stopPropagation(); if (i) { T.sections.splice(i - 1, 0, T.sections.splice(i, 1)[0]); d(); } } }, '↑'),
        h('button', { class: 'btn sm', title: 'أسفل', onclick: (e) => { e.stopPropagation(); if (i < T.sections.length - 1) { T.sections.splice(i + 1, 0, T.sections.splice(i, 1)[0]); d(); } } }, '↓'),
        h('button', { class: 'btn sm', onclick: (e) => { e.stopPropagation(); s.enabled = s.enabled === false; d(); } }, s.enabled === false ? 'إظهار' : 'إخفاء'),
        h('button', { class: 'btn sm bad', onclick: (e) => { e.stopPropagation(); if (confirm('حذف القسم؟')) { T.sections.splice(i, 1); d(); } } }, '✕')),
      open.has(s.id) ? h('div', { class: 'bd' }, s.type === 'features' ? listEditor(s.items, [['icon', 'أيقونة'], ['title', 'العنوان'], ['title_en', 'Title'], ['text', 'النص'], ['text_en', 'Text']], { icon: '⭐', title: '', title_en: '', text: '', text_en: '' })
        : (SEC_FIELDS[s.type] || []).map(([k, l, o]) => field(s, k, l, o && typeof o.options === 'function' ? { ...o, options: o.options() } : o))) : null)),
      h('select', { onchange: (e) => { const t = e.target.value; if (!t) return; T.sections.push({ id: 's' + Date.now(), type: t, enabled: true, ...(t === 'features' ? { items: [] } : { limit: 4, title: 'عنوان جديد' }) }); open.add(T.sections.at(-1).id); d(); } },
        h('option', { value: '' }, '+ إضافة قسم…'), Object.entries(SEC_TYPES).map(([v, l]) => h('option', { value: v }, l))));
    d(); return wrap;
  };
  const draw = () => {
    tabs.replaceChildren(...[['look', 'المظهر'], ['nav', 'القوائم والفوتر'], ['home', 'أقسام الرئيسية']].map(([k, l]) => h('button', { class: tab === k ? 'on' : '', onclick: () => { tab = k; draw(); } }, l)));
    body.replaceChildren(tab === 'look' ? h('div', {}, h('div', { class: 'card' }, h('h2', {}, 'الألوان'), row(Object.entries(colorLabels).map(([k, l]) => field(T.colors, k, l, { type: 'color' })))),
      h('div', { class: 'card' }, field(T, 'logo', 'الشعار (Logo)', { type: 'image' }), row(field(T, 'logo_height', 'ارتفاع الشعار px', { type: 'number' }), field(T, 'font', 'الخط', { type: 'select', options: ['Cairo', 'Tajawal', 'Almarai', 'Inter', 'Poppins'].map((f) => [f, f]) }),
        field(T, 'radius', 'استدارة الزوايا px', { type: 'number' }), field(T, 'product_grid_cols', 'أعمدة المنتجات', { type: 'select', options: [[3, '3'], [4, '4'], [5, '5']] })), field(T, 'header_sticky', 'تثبيت الهيدر عند التمرير', { type: 'check' })))
      : tab === 'nav' ? h('div', {}, h('div', { class: 'card' }, h('h2', {}, 'قائمة الهيدر'), listEditor(T.menu, [['label', 'الاسم'], ['label_en', 'Label'], ['url', 'الرابط', { dir: 'ltr' }]], { label: '', label_en: '', url: '/' })),
        h('div', { class: 'card' }, field(T, 'footer_text', 'نص الفوتر'), field(T, 'footer_text_en', 'Footer text')))
      : sections());
  };
  draw();
  const save = guarded(async () => { if (T.product_grid_cols) T.product_grid_cols = Number(T.product_grid_cols); await api('PUT', '/api/admin/settings', { theme: T }); toast('تم الحفظ'); frame.src = '/?t=' + Date.now(); });
  return h('div', {}, head('تخصيص المتجر', h('a', { class: 'btn', target: '_blank', href: '/' }, 'فتح ↗'), h('button', { class: 'btn pri', onclick: save }, 'حفظ')), h('div', { class: 'theme' }, h('div', {}, tabs, body), frame));
}

async function settings() {
  const S = await api('GET', '/api/admin/settings'), s = S.store, acc = { email: ME.email, current: '', password: '' };
  const save = guarded(async () => { await api('PUT', '/api/admin/settings', { store: s }); SETTINGS = await api('GET', '/api/admin/settings'); toast('تم الحفظ'); });
  return h('div', {}, head('الإعدادات', h('button', { class: 'btn pri', onclick: save }, 'حفظ')),
    h('div', { class: 'grid g2' }, h('div', {},
      h('div', { class: 'card' }, h('h2', {}, 'المتجر'), row(field(s, 'name', 'اسم المتجر'), field(s, 'name_en', 'Store name')), row(field(s, 'tagline', 'الشعار النصي'), field(s, 'tagline_en', 'Tagline')), field(s, 'meta_description', 'وصف SEO', { type: 'textarea' })),
      h('div', { class: 'card' }, h('h2', {}, 'شريط الإعلان'), field(s, 'announcement', 'النص (فارغ = إخفاء)'), field(s, 'announcement_en', 'Text (English)')),
      h('div', { class: 'card' }, h('h2', {}, 'التواصل'), row(field(s, 'phone', 'الهاتف'), field(s, 'whatsapp', 'واتساب (بالكود الدولي)'), field(s, 'email', 'البريد')), field(s, 'address', 'العنوان'),
        row(...['facebook', 'instagram', 'tiktok', 'youtube'].map((k) => field(s.social, k, k, { dir: 'ltr' }))))),
      h('div', {}, h('div', { class: 'card' }, h('h2', {}, 'الشحن والعملة'), row(field(s, 'shipping_fee', 'رسوم الشحن', { type: 'number' }), field(s, 'free_shipping_over', 'شحن مجاني فوق (0 = لا)', { type: 'number' })),
        row(field(s, 'currency', 'رمز العملة (EGP)'), field(s, 'currency_symbol', 'الرمز بالعربي'), field(s, 'currency_symbol_en', 'Symbol (EN)')), field(s, 'track_stock', 'تتبع المخزون ومنع بيع النافد', { type: 'check' })),
        h('div', { class: 'card' }, h('h2', {}, 'اللغة'), field(s, 'default_lang', 'اللغة الافتراضية', { type: 'select', options: [['ar', 'العربية'], ['en', 'English']] }), field(s, 'allow_lang_switch', 'السماح للزائر بتغيير اللغة', { type: 'check' })),
        h('div', { class: 'card' }, h('h2', {}, 'حسابي'), field(acc, 'email', 'البريد', { type: 'email' }), field(acc, 'current', 'كلمة المرور الحالية', { type: 'password' }), field(acc, 'password', 'كلمة مرور جديدة (8+ أحرف، اتركها فارغة للإبقاء)', { type: 'password' }),
          h('button', { class: 'btn', onclick: guarded(async () => { await api('POST', '/api/admin/account', acc); ME = await api('GET', '/api/admin/me'); toast('تم تحديث الحساب'); }) }, 'تحديث الحساب')))));
}

// ---------- shell / routing ----------
const ROUTES = [['/', '🏠', 'الرئيسية', dashboard], ['/orders', '🧾', 'الطلبات', orders], ['/products', '🏷️', 'المنتجات', products], ['/collections', '📚', 'المجموعات', collections],
  ['/customers', '👥', 'العملاء', customers], ['/discounts', '🎟️', 'الخصومات', discounts], ['/pages', '📄', 'الصفحات', pages], ['/theme', '🎨', 'تخصيص المتجر', theme], ['/settings', '⚙️', 'الإعدادات', settings]];
let main, shell;
async function route() {
  const path = location.hash.slice(1) || '/', [, a, b] = path.split('/');
  shell.classList.remove('open');
  shell.querySelectorAll('.side a[data-p]').forEach((x) => x.classList.toggle('on', (x.dataset.p === '/' ? path === '/' : path.startsWith(x.dataset.p))));
  main.replaceChildren(h('p', { class: 'muted' }, 'جارٍ التحميل…'));
  try {
    let view;
    if (a === 'orders' && b) view = await orderDetail(b); else if (a === 'products' && b) view = await productEdit(b);
    else view = await (ROUTES.find((r) => r[0] === '/' + (a || ''))?.[3] || dashboard)();
    main.replaceChildren(view); scrollTo(0, 0);
  } catch (e) { main.replaceChildren(h('div', { class: 'alert' }, e.message)); }
}
function login() {
  const s = { email: '', password: '' }, err = h('p', { style: 'color:var(--bad)' });
  const go = async () => { try { await api('POST', '/api/login', s); boot(); } catch (e) { err.textContent = e.message; } };
  $app.replaceChildren(h('div', { class: 'login' }, h('div', { class: 'card' }, h('h1', {}, 'NOTCH Tech'), h('p', { class: 'muted' }, 'تسجيل دخول لوحة التحكم'), h('br'),
    field(s, 'email', 'البريد الإلكتروني', { type: 'email' }), field(s, 'password', 'كلمة المرور', { type: 'password' }), err, h('button', { class: 'btn pri', style: 'width:100%;justify-content:center', onclick: go }, 'دخول'))));
  $app.addEventListener('keydown', (e) => e.key === 'Enter' && go(), { once: true });
}
async function boot() {
  try { ME = await api('GET', '/api/admin/me'); } catch { ME = null; return login(); }
  [SETTINGS, COLS] = await Promise.all([api('GET', '/api/admin/settings'), api('GET', '/api/admin/collections')]);
  main = h('main'); shell = h('div', { class: 'shell' }, h('aside', { class: 'side' }, h('div', { class: 'brand' }, SETTINGS.store.name, h('small', {}, 'لوحة التحكم')),
    ROUTES.map(([p, i, l]) => h('a', { href: '#' + p, 'data-p': p }, i, l)), h('div', { class: 'sp' }), h('hr'), h('a', { href: '/', target: '_blank' }, '↗ عرض المتجر'),
    h('a', { href: '#', onclick: async (e) => { e.preventDefault(); await api('POST', '/api/logout', {}); ME = null; login(); } }, '⎋ تسجيل الخروج')),
    h('div', {}, h('div', { style: 'padding:10px 16px' }, h('button', { class: 'btn burger', onclick: () => shell.classList.toggle('open') }, '☰ القائمة')), main));
  $app.replaceChildren(shell); route();
}
addEventListener('hashchange', () => ME && route());
boot();
})();
