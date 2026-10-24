/* iCodic — vanilla JS replacing the React interactivity. No build step. */
(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const BASE = document.documentElement.dataset.base || '';
  const url = (p) => BASE + p;
  const post = (p, data) => fetch(url(p), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': CSRF }, body: JSON.stringify(data) }).then(r => r.json());

  /* Region auto-detect on first visit (replaces ipapi.co call) */
  if (!document.cookie.includes('icodic_region=')) {
    const TZ = { 'Europe/London': 'UK', 'America/Toronto': 'UK', 'America/Vancouver': 'UK', 'America/New_York': 'UK', 'America/Chicago': 'UK', 'America/Los_Angeles': 'UK', 'Asia/Dubai': 'UAE', 'Asia/Kolkata': 'IN', 'Asia/Calcutta': 'IN', 'Asia/Karachi': 'PK' };
    try {
      const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
      const r = TZ[tz];
      const cur = document.documentElement.dataset.region;
      document.cookie = 'icodic_region=' + (r || cur) + ';path=/;max-age=31536000;SameSite=Lax';
      if (r && r !== cur) location.reload();
    } catch (e) {}
  }

  /* Reveal on scroll */
  const rev = $$('.reveal');
  if ('IntersectionObserver' in window && rev.length) {
    const obs = new IntersectionObserver((es) => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); obs.unobserve(e.target); } }), { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
    rev.forEach(el => obs.observe(el));
  } else rev.forEach(el => el.classList.add('is-visible'));

  /* Header: prefs dropdown + mobile nav */
  const prefs = $('.prefs');
  if (prefs) {
    $('.prefs-btn', prefs).addEventListener('click', (e) => { e.stopPropagation(); prefs.classList.toggle('open'); });
    document.addEventListener('click', (e) => { if (!prefs.contains(e.target)) prefs.classList.remove('open'); });
  }
  const burger = $('.burger');
  if (burger) burger.addEventListener('click', () => { $('.mobile-nav').classList.toggle('open'); });

  /* Live clocks */
  const tick = () => $$('[data-tz]').forEach(el => {
    try { el.textContent = new Intl.DateTimeFormat('en-US', { timeZone: el.dataset.tz, hour: '2-digit', minute: '2-digit', hour12: false, weekday: 'short' }).format(new Date()); } catch (e) {}
  });
  tick(); setInterval(tick, 15000);

  /* Typewriter */
  const tw = $('[data-typewriter]');
  if (tw) {
    const text = tw.dataset.typewriter; let i = 0; tw.textContent = '';
    const cur = document.createElement('span'); cur.className = 'cursor'; cur.textContent = '|';
    tw.after(cur);
    const id = setInterval(() => { i++; tw.textContent = text.slice(0, i); if (i >= text.length) clearInterval(id); }, 38);
  }

  /* Orbit animation */
  $$('.orbit-track').forEach(track => {
    const nodes = $$('.orbit-node', track); const n = nodes.length;
    const radius = parseFloat(track.dataset.radius), dur = parseFloat(track.dataset.duration) * 1000, dir = parseFloat(track.dataset.dir);
    const wrap = track.closest('.orbit-wrap');
    const start = performance.now();
    const frame = (now) => {
      const size = wrap.clientWidth; const R = size * radius / 100;
      const base = ((now - start) / dur) * Math.PI * 2 * dir;
      nodes.forEach((el, k) => { const a = base + (k / n) * Math.PI * 2; el.style.transform = `translate(${Math.cos(a) * R}px, ${Math.sin(a) * R}px)`; });
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  });

  /* Software catalog: search + category filter (client side, data already region-filtered server side) */
  const catalog = $('#software');
  if (catalog) {
    const cards = $$('.product', catalog); const catBtns = $$('.cats button', catalog); const search = $('#hero-search');
    const emptyEl = $('.cat-empty', catalog); const grid = $('.cat-grid', catalog);
    let cat = catBtns.find(b => b.classList.contains('active'))?.dataset.cat || '';
    const apply = () => {
      const q = (search?.value || '').trim().toLowerCase();
      let shown = 0;
      cards.forEach(c => {
        const okQ = !q || c.dataset.search.includes(q);
        const okC = (q || c.closest('.acc')) ? true : (!cat || c.dataset.cat === cat); // mobile accordion shows every category
        const show = okQ && okC; c.hidden = !show; if (show) shown++;
      });
      catBtns.forEach(b => b.classList.toggle('active', !q && b.dataset.cat === cat));
      if (emptyEl) emptyEl.hidden = shown > 0;
      $$('.acc', catalog).forEach(a => { const vis = $$('.product:not([hidden])', a).length; a.hidden = vis === 0; const n = $('.acc-n', a); if (n) n.textContent = '(' + vis + ')'; });
    };
    catBtns.forEach(b => b.addEventListener('click', () => { cat = b.dataset.cat; if (search) search.value = ''; apply(); }));
    if (search) { search.addEventListener('input', apply); $('#hero-form')?.addEventListener('submit', (e) => { e.preventDefault(); apply(); catalog.scrollIntoView({ behavior: 'smooth' }); }); }
    apply();
  }

  /* Project archive scroller */
  $$('[data-scroll]').forEach(b => b.addEventListener('click', () => { $(b.dataset.target).scrollBy({ left: parseInt(b.dataset.scroll, 10), behavior: 'smooth' }); }));

  /* Order modal */
  const modal = $('#order-modal');
  if (modal) {
    const form = $('form', modal), done = $('.order-done', modal), err = $('.order-err', modal);
    const f = {}; ['customer_name', 'email', 'phone', 'company', 'quantity', 'notes', 'product_id', 'billing_period'].forEach(k => f[k] = form.elements[k]);
    let product = null; const rate = parseFloat(modal.dataset.rate), sym = modal.dataset.symbol, period = modal.dataset.period;
    const fmt = (usd) => sym + ' ' + Math.round(usd * rate).toLocaleString();
    const waBase = modal.dataset.wa;
    const waLink = () => {
      const t = `Hi iCodic, I'd like to inquire about ${product.name} (${product.brand}).\nName: ${f.customer_name.value || '-'}\nEmail: ${f.email.value || '-'}\nPhone: ${f.phone.value || '-'}\nCompany: ${f.company.value || '-'}\nQuantity: ${f.quantity.value}\nBilling: ${period}\nNotes: ${f.notes.value || '-'}`;
      return `https://wa.me/${waBase}?text=${encodeURIComponent(t)}`;
    };
    const refresh = () => {
      if (!product) return;
      const price = period === 'monthly' ? product.pm : product.py;
      $('.m-total', modal).textContent = fmt(price * (parseInt(f.quantity.value, 10) || 1));
      $('.m-seats', modal).textContent = 'incl. ' + (f.quantity.value || 1) + ' seat(s)';
      $$('.wa-link', modal).forEach(a => a.href = waLink());
    };
    const open = (btn) => {
      product = { id: btn.dataset.id, name: btn.dataset.name, brand: btn.dataset.brand, pm: parseFloat(btn.dataset.pm), py: parseFloat(btn.dataset.py) };
      form.reset(); f.product_id.value = product.id; f.billing_period.value = period; f.quantity.value = 1;
      $('.m-product', modal).textContent = product.name + ' · ' + product.brand;
      form.hidden = false; done.hidden = true; err.hidden = true; refresh();
      modal.classList.add('open'); document.body.style.overflow = 'hidden'; f.customer_name.focus();
    };
    const close = () => { modal.classList.remove('open'); document.body.style.overflow = ''; };
    $$('[data-order]').forEach(b => b.addEventListener('click', () => open(b)));
    $$('[data-close]', modal).forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    form.addEventListener('input', refresh);
    form.addEventListener('submit', async (e) => {
      e.preventDefault(); err.hidden = true;
      const btn = $('button[type=submit]', form); btn.disabled = true;
      const data = {}; Array.from(form.elements).forEach(el => { if (el.name) data[el.name] = el.value; });
      try {
        const r = await post('/order', data);
        if (!r.ok) throw new Error(r.error || 'Could not place order. Please try again.');
        form.hidden = true; done.hidden = false;
        $('.done-id', done).textContent = r.order_id; $('.done-email', done).textContent = r.email;
        $$('.wa-link', modal).forEach(a => a.href = waLink());
      } catch (ex) { err.textContent = ex.message; err.hidden = false; }
      btn.disabled = false;
    });
  }

  /* Track order */
  const track = $('#track-form');
  if (track) {
    const res = $('#track-result'), terr = $('#track-error');
    const STEPS = ['new', 'confirmed', 'processing', 'dispatched', 'delivered', 'completed'];
    track.addEventListener('submit', async (e) => {
      e.preventDefault(); res.hidden = true; terr.hidden = true;
      const btn = $('button', track); btn.disabled = true;
      try {
        const r = await post('/track-order', { order_id: track.elements.order_id.value });
        if (!r.found) throw new Error(r.error || 'No order found with that ID. Please check and try again.');
        const o = r.order; const idx = STEPS.indexOf(o.status); const neg = ['rejected', 'cancelled'].includes(o.status);
        $$('.step', res).forEach((s, i) => { s.className = 'step' + (neg ? (i === 0 ? ' neg' : '') : i < idx ? ' done' : i === idx ? ' current' : ''); });
        $('.t-id', res).textContent = o.order_id; $('.t-product', res).textContent = o.product_name; $('.t-qty', res).textContent = o.quantity + ' seat(s) · ' + o.billing_period;
        $('.t-status', res).textContent = o.status.charAt(0).toUpperCase() + o.status.slice(1); $('.t-status', res).className = 't-status status s-' + o.status;
        $('.t-date', res).textContent = new Date(o.created_at.replace(' ', 'T') + 'Z').toLocaleString();
        const c = $('.t-comment', res); c.hidden = !o.comment; if (o.comment) $('span', c).textContent = o.comment;
        res.hidden = false;
      } catch (ex) { terr.textContent = ex.message; terr.hidden = false; }
      btn.disabled = false;
    });
  }

  /* Live chat */
  const chat = $('#chat');
  if (chat) {
    const body = $('.chat-body', chat), cform = $('.chat-form', chat), cin = $('input', cform);
    $('.chat-fab').addEventListener('click', () => { chat.classList.toggle('open'); if (chat.classList.contains('open')) cin.focus(); });
    $('[data-chat-close]', chat).addEventListener('click', () => chat.classList.remove('open'));
    const add = (role, text) => { const d = document.createElement('div'); d.className = 'msg ' + role; d.textContent = text; body.appendChild(d); body.scrollTop = body.scrollHeight; return d; };
    const send = async (text) => {
      text = text.trim(); if (!text) return;
      add('user', text); cin.value = ''; $('.chat-sugg', chat).hidden = true;
      const wait = add('bot', '…');
      try { const r = await post('/api/chat', { message: text }); wait.textContent = r.ok ? r.reply : (r.error || 'Sorry, something went wrong.'); }
      catch (e) { wait.textContent = "Sorry, I couldn't reach the assistant right now. Please try again or contact us on WhatsApp."; }
      body.scrollTop = body.scrollHeight;
    };
    cform.addEventListener('submit', (e) => { e.preventDefault(); send(cin.value); });
    $$('.chat-sugg button', chat).forEach(b => b.addEventListener('click', () => send(b.textContent)));
  }

  /* Admin: image upload + delete confirm + status quick-change */
  $$('[data-upload]').forEach(box => {
    const input = $('input[type=file]', box), target = $('input[type=text]', box), prev = $('img', box), msg = $('.up-msg', box);
    input.addEventListener('change', async () => {
      if (!input.files[0]) return; msg.textContent = 'Uploading…';
      const fd = new FormData(); fd.append('file', input.files[0]); fd.append('_token', CSRF);
      try {
        const r = await fetch(url('/admin/upload'), { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } }).then(r => r.json());
        if (!r.ok) throw new Error(r.error); target.value = r.url; prev.src = url(r.url); prev.hidden = false; msg.textContent = 'Uploaded.';
      } catch (e) { msg.textContent = e.message || 'Upload failed'; }
    });
    target.addEventListener('input', () => { prev.src = target.value.startsWith('http') ? target.value : url(target.value); prev.hidden = !target.value; });
  });
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
  $$('select[data-status]').forEach(s => s.addEventListener('change', () => s.form.submit()));
})();
