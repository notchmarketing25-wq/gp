import { all, get, run, tx, getSetting, setSetting } from './db.js';

export function seedIfEmpty() {
  if (get('SELECT id FROM collections LIMIT 1') || get('SELECT id FROM products LIMIT 1')) return;
  tx(() => {
    setSetting('store', getSetting('store'));
    setSetting('theme', getSetting('theme'));
    const cols = [
      ['chargers', 'شواحن', 'Chargers', 'charger'], ['cables', 'كابلات', 'Cables', 'cable'],
      ['power-banks', 'باور بانك', 'Power banks', 'powerbank'], ['audio', 'سماعات وصوتيات', 'Audio', 'earbuds'],
      ['cases', 'جرابات وحمايات', 'Cases & protection', 'case'], ['wearables', 'ساعات ذكية', 'Smart watches', 'watch'],
    ];
    const ids = {};
    cols.forEach(([handle, title, title_en, kind], i) => {
      ids[handle] = Number(run('INSERT INTO collections(handle,title,title_en,image,position) VALUES(?,?,?,?,?)',
        handle, title, title_en, `/ph/${kind}.svg`, i).lastInsertRowid);
    });
    const P = [
      ['gan-65w-charger', 'شاحن NOTCH GaN‏ 65W', 'NOTCH GaN 65W Charger', 'charger', 899, 1099, 'chargers', 120, 'شاحن سريع صغير الحجم بثلاث مخارج (2×USB-C + USB-A) يشحن لابتوب وموبايل في نفس الوقت.', 'Compact fast charger with 3 ports (2×USB-C + USB-A) — charges a laptop and phone at once.'],
      ['gan-30w-charger', 'شاحن NOTCH سريع 30W', 'NOTCH 30W Fast Charger', 'charger', 449, 0, 'chargers', 200, 'شاحن PD‏ 30W مثالي للآيفون والأندرويد.', 'PD 30W charger, ideal for iPhone and Android.'],
      ['usb-c-cable-braided', 'كابل USB-C مضفّر 1.5م', 'Braided USB-C Cable 1.5m', 'cable', 199, 249, 'cables', 400, 'كابل نايلون مضفّر يتحمّل 100W و 20,000 ثنية.', 'Braided nylon cable rated 100W and 20,000 bends.'],
      ['lightning-cable', 'كابل Lightning‏ MFi معتمد', 'MFi Lightning Cable', 'cable', 249, 0, 'cables', 250, 'معتمد من Apple لشحن آمن وسريع.', 'Apple-certified for safe, fast charging.'],
      ['power-bank-20000', 'باور بانك NOTCH‏ 20,000 mAh', 'NOTCH 20,000 mAh Power Bank', 'powerbank', 1299, 1499, 'power-banks', 80, 'سعة ضخمة مع شحن سريع 22.5W وشاشة رقمية.', 'Huge capacity, 22.5W fast charging, digital display.'],
      ['power-bank-10000', 'باور بانك ميني 10,000 mAh', 'Mini Power Bank 10,000 mAh', 'powerbank', 749, 0, 'power-banks', 140, 'خفيف وبجيب البنطلون، مع كابل USB-C مدمج.', 'Pocket-sized with a built-in USB-C cable.'],
      ['buds-pro', 'سماعات NOTCH Buds Pro', 'NOTCH Buds Pro', 'earbuds', 1499, 1899, 'audio', 60, 'عزل ضوضاء نشط (ANC) وبطارية 30 ساعة مع العلبة.', 'Active noise cancelling and 30h battery with case.'],
      ['buds-air', 'سماعات NOTCH Buds Air', 'NOTCH Buds Air', 'earbuds', 799, 0, 'audio', 90, 'صوت نقي وزمن تأخير منخفض للألعاب.', 'Clear sound with low-latency gaming mode.'],
      ['speaker-mini', 'سبيكر بلوتوث NOTCH Boom', 'NOTCH Boom Bluetooth Speaker', 'speaker', 999, 1199, 'audio', 50, 'مقاوم للماء IPX7 وصوت 360°.', 'IPX7 waterproof with 360° sound.'],
      ['clear-case', 'جراب شفاف مضاد للصدمات', 'Clear Shockproof Case', 'case', 249, 0, 'cases', 300, 'حماية عسكرية وحواف مرفوعة للكاميرا والشاشة.', 'Military-grade protection with raised edges.'],
      ['car-holder', 'حامل موبايل للسيارة مغناطيسي', 'Magnetic Car Phone Holder', 'holder', 349, 0, 'cases', 110, 'تثبيت قوي ودوران 360°.', 'Strong grip with 360° rotation.'],
      ['smart-watch-s1', 'ساعة NOTCH Watch S1', 'NOTCH Watch S1', 'watch', 2299, 2699, 'wearables', 35, 'شاشة AMOLED، متابعة صحة ورياضة، بطارية 10 أيام.', 'AMOLED display, health & sport tracking, 10-day battery.'],
    ];
    const colors = ['Black|أسود', 'White|أبيض'];
    P.forEach(([handle, title, title_en, kind, price, cmp, col, stock, d, den], i) => {
      const variants = ['charger', 'case', 'earbuds', 'watch'].includes(kind)
        ? colors.map((c) => ({ title: c.split('|')[1], price, stock: Math.floor(stock / 2), sku: `${handle}-${c.split('|')[0].toLowerCase()}` })) : [];
      run(`INSERT INTO products(handle,title,title_en,description,description_en,price,compare_at,sku,stock,status,images,variants,tags,collection_ids,sold)
           VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
        handle, title, title_en, d, den, price, cmp, `NT-${1000 + i}`, stock, 'active',
        JSON.stringify([`/ph/${kind}.svg`]), JSON.stringify(variants), JSON.stringify([]), JSON.stringify([ids[col]]), (i * 37) % 90 + 10);
    });
    run("INSERT INTO discounts(code,type,value,min_subtotal) VALUES('WELCOME10','percent',10,500)");
    const pages = [
      ['about', 'من نحن', 'About us', '<p>NOTCH Tech علامة مصرية متخصصة في تصنيع الإلكترونيات وإكسسوارات الموبايل. نصمم ونصنّع منتجاتنا بمعايير جودة صارمة لنقدّم لك تجربة موثوقة.</p><p>عدّل هذه الصفحة من لوحة التحكم ← الصفحات.</p>', '<p>NOTCH Tech is an Egyptian brand that designs and manufactures electronics and mobile accessories to strict quality standards.</p>'],
      ['contact', 'تواصل معنا', 'Contact us', '<p>راسلنا على البريد أو واتساب وسنرد خلال 24 ساعة. (حدّث بيانات التواصل من الإعدادات)</p>', '<p>Email or WhatsApp us — we reply within 24h.</p>'],
      ['shipping-returns', 'الشحن والاسترجاع', 'Shipping & returns', '<p>التوصيل خلال 2–5 أيام عمل. الاسترجاع خلال 14 يومًا من الاستلام للمنتجات غير المستخدمة.</p>', '<p>Delivery in 2–5 business days. Returns within 14 days for unused items.</p>'],
    ];
    pages.forEach(([h, t, te, b, be]) => run('INSERT INTO pages(handle,title,title_en,body,body_en) VALUES(?,?,?,?,?)', h, t, te, b, be));
  });
}
