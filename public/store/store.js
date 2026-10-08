(() => {
  const NT = window.NT, $ = (s, r = document) => r.querySelector(s);
  const fmt = (n) => `${Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 })} ${NT.sym}`;
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const load = () => { try { return JSON.parse(localStorage.getItem('nt_cart')) || []; } catch { return []; } };
  const save = (c) => { localStorage.setItem('nt_cart', JSON.stringify(c)); badge(); };
  function badge() {
    const n = load().reduce((s, i) => s + i.qty, 0), el = $('#cart-count');
    if (el) { el.textContent = n; el.hidden = !n; }
  }
  function toast(msg) { const t = $('#toast'); t.textContent = msg; t.classList.add('on'); setTimeout(() => t.classList.remove('on'), 2000); }
  const post = async (url, body) => {
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const d = await r.json().catch(() => ({})); if (!r.ok) throw new Error(d.error || 'Error'); return d;
  };
  badge();

  // ---- product page ----
  const pd = $('#pdata');
  if (pd) {
    const p = JSON.parse(pd.textContent); let vi = p.variants.length ? 0 : -1;
    const qty = $('#qty'), q = () => Math.max(1, Math.min(99, parseInt(qty.value) || 1));
    $('#qm').onclick = () => (qty.value = Math.max(1, q() - 1)); $('#qp').onclick = () => (qty.value = q() + 1);
    $('#opts')?.addEventListener('click', (e) => {
      const b = e.target.closest('.opt'); if (!b || b.disabled) return;
      document.querySelectorAll('.opt').forEach((o) => o.classList.remove('on')); b.classList.add('on'); vi = +b.dataset.i;
      $('#pprice').textContent = fmt(p.variants[vi].price);
    });
    if (vi >= 0) { const f = p.variants.findIndex((v) => !p.track || v.stock > 0); if (f > 0) $(`.opt[data-i="${f}"]`).click(); else if (f === 0) $('#pprice').textContent = fmt(p.variants[0].price); }
    const add = () => {
      const v = vi >= 0 ? p.variants[vi] : null, cart = load(), key = (i) => `${i.id}|${i.variant}`;
      const item = { id: p.id, handle: p.handle, title: p.title, variant: v ? v.title : '', price: v ? v.price : p.price, image: p.image, qty: q() };
      const ex = cart.find((i) => key(i) === key(item)); if (ex) ex.qty += item.qty; else cart.push(item);
      save(cart);
    };
    $('#addbtn').onclick = () => { add(); toast(NT.t.added); };
    $('#buybtn').onclick = () => { add(); location.href = '/cart'; };
  }

  // ---- cart + checkout ----
  const app = $('#cart-full');
  if (app) {
    let code = '', priced = null;
    const render = async () => {
      const cart = load();
      $('#cart-empty').hidden = !!cart.length; app.hidden = !cart.length;
      if (!cart.length) return;
      $('#lines').innerHTML = cart.map((i, n) => `<div class="line"><img src="${esc(i.image)}" alt=""><div class="grow"><a href="/products/${encodeURIComponent(i.handle)}"><b>${esc(i.title)}</b></a><small>${esc(i.variant)}</small>
        <div class="qty" style="width:fit-content;margin-top:6px"><button data-m="${n}">−</button><input value="${i.qty}" readonly><button data-p="${n}">+</button></div></div>
        <div style="text-align:end"><b>${fmt(i.price * i.qty)}</b><br><button class="rm" data-r="${n}">${NT.t.remove}</button></div></div>`).join('');
      try {
        priced = await post('/api/cart/price', { items: cart.map(({ id, variant, qty }) => ({ id, variant, qty })), code });
        $('#perr').hidden = !priced.codeError; $('#perr').textContent = priced.codeError || '';
        if (priced.codeError) code = '';
        $('#sumrows').innerHTML = `<div class="sumrow"><span>${NT.t.subtotal}</span><span>${fmt(priced.subtotal)}</span></div>
          ${priced.discount ? `<div class="sumrow"><span>${NT.t.discount} (${esc(priced.code)})</span><span>-${fmt(priced.discount)}</span></div>` : ''}
          <div class="sumrow"><span>${NT.t.shipping}</span><span>${priced.shipping ? fmt(priced.shipping) : NT.t.free}</span></div>
          <div class="sumrow total"><span>${NT.t.total}</span><span>${fmt(priced.total)}</span></div>`;
        $('#cerr').hidden = true;
      } catch (e) { $('#cerr').hidden = false; $('#cerr').textContent = e.message; $('#sumrows').innerHTML = ''; }
    };
    $('#lines').addEventListener('click', (e) => {
      const b = e.target.closest('button'); if (!b) return; const c = load();
      if (b.dataset.m) c[b.dataset.m].qty = Math.max(1, c[b.dataset.m].qty - 1);
      if (b.dataset.p) c[b.dataset.p].qty = Math.min(99, c[b.dataset.p].qty + 1);
      if (b.dataset.r) c.splice(b.dataset.r, 1);
      save(c); render();
    });
    $('#applybtn').onclick = () => { code = $('#code').value.trim(); render(); };
    $('#checkout').addEventListener('submit', async (e) => {
      e.preventDefault(); const btn = $('#placebtn'); btn.disabled = true; btn.textContent = NT.t.placing;
      try {
        const f = Object.fromEntries(new FormData(e.target));
        const r = await post('/api/checkout', { ...f, code, items: load().map(({ id, variant, qty }) => ({ id, variant, qty })) });
        save([]); location.href = `/order/${r.token}`;
      } catch (err) { $('#cerr').hidden = false; $('#cerr').textContent = err.message; btn.disabled = false; btn.textContent = NT.t.place; }
    });
    render();
  }
})();
