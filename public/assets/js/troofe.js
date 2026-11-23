/* Trôofê — interactions du site (sans dépendance hors Bootstrap) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name="csrf-token"]')?.content;
  const money = n => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';

  /* Barre du haut au défilement */
  const top = $('#topbar');
  addEventListener('scroll', () => top && top.classList.toggle('scrolled', scrollY > 8), { passive: true });

  /* Favoris et « Suivre » sans rechargement */
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-toggle-url]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    b.disabled = true;
    try {
      const r = await fetch(b.dataset.toggleUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
      if (r.status === 401 || r.status === 419) { location.href = '/connexion'; return; }
      const d = await r.json();
      b.classList.toggle('on', d.on); b.setAttribute('aria-pressed', d.on);
      const i = b.querySelector('i');
      if (b.dataset.kind === 'fav') { i.className = 'bi ' + (d.on ? 'bi-heart-fill' : 'bi-heart'); }
      else { i.className = 'bi ' + (d.on ? 'bi-bell-fill' : 'bi-bell'); b.classList.toggle('btn-soft', d.on); b.classList.toggle('btn-ghost', !d.on); const s = b.querySelector('span'); if (s) s.textContent = d.on ? b.dataset.on : b.dataset.off; }
    } catch { alert(TROOFE.i18n.error); }
    b.disabled = false;
  });

  /* Recherche instantanée (Ctrl+K) */
  const modalEl = $('#searchModal');
  if (modalEl) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl), q = $('#search-q'), out = $('#search-res');
    let timer, sel = -1;
    const open = () => { modal.show(); setTimeout(() => q.focus(), 200); };
    $$('[data-search]').forEach(b => b.addEventListener('click', open));
    addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open(); } });
    q.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const v = q.value.trim(); sel = -1;
        if (v.length < 2) { out.innerHTML = ''; return; }
        const r = await fetch(TROOFE.searchUrl + '?q=' + encodeURIComponent(v), { headers: { 'Accept': 'application/json' } });
        const groups = await r.json();
        out.innerHTML = groups.length ? groups.map(g => `<div class="pal-group">${g.group}</div>` + g.items.map(i => `<a href="${i.url}"><img src="${i.img}" alt=""><span class="min-w-0"><b class="d-block text-truncate">${esc(i.title)}</b><span class="small muted">${esc(i.sub)}</span></span></a>`).join('')).join('') : `<p class="muted">${TROOFE.i18n.noResult}</p>`;
      }, 180);
    });
    q.addEventListener('keydown', e => {
      const items = $$('a', out); if (!items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); sel = (sel + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length; items.forEach((a, k) => a.classList.toggle('on', k === sel)); items[sel].scrollIntoView({ block: 'nearest' }); }
      if (e.key === 'Enter' && sel >= 0) { e.preventDefault(); location.href = items[sel].href; }
    });
  }
  function esc(s) { return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

  /* Commande au restaurant : quantités et total */
  const order = $('[data-order-form]');
  if (order) {
    const fee = +order.dataset.fee || 0, bar = $('[data-order-total]'), btn = $('[data-order-submit]'), mode = () => $('[name="fulfilment"]:checked', order)?.value;
    const draw = () => {
      let sub = 0, n = 0;
      $$('[data-price]', order).forEach(i => { const q = Math.max(0, +i.value || 0); sub += q * +i.dataset.price; n += q; });
      const total = sub + (mode() === 'livraison' && n ? fee : 0);
      bar.textContent = money(total); btn.disabled = !n;
      $('[data-order-count]').textContent = n;
      const addr = $('[data-address]', order); if (addr) addr.hidden = mode() !== 'livraison';
    };
    order.addEventListener('click', e => { const s = e.target.closest('[data-step]'); if (!s) return; const i = s.parentElement.querySelector('input'); i.value = Math.max(0, Math.min(50, (+i.value || 0) + +s.dataset.step)); draw(); });
    order.addEventListener('input', draw); draw();
  }

  /* Billetterie : total */
  const tickets = $('[data-ticket-form]');
  if (tickets) {
    const draw = () => {
      let t = 0, n = 0;
      $$('[data-tprice]', tickets).forEach(i => { const q = +i.value || 0; t += q * +i.dataset.tprice; n += q; });
      $('[data-ticket-total]').textContent = t ? money(t) : (n ? '0 FCFA' : '—');
      $('[data-ticket-submit]').disabled = !n;
      const box = $('[data-holders]'); if (box) { const cur = $$('input', box); for (let k = cur.length; k < n; k++) { const inp = document.createElement('input'); inp.name = 'holders[]'; inp.className = 'form-control form-control-sm mb-2'; inp.placeholder = box.dataset.placeholder.replace(':n', k + 1); box.appendChild(inp); } cur.slice(n).forEach(x => x.remove()); }
    };
    tickets.addEventListener('input', draw); draw();
  }

  /* Calendrier de disponibilités → formulaire de réservation */
  $$('[data-day]').forEach(b => b.addEventListener('click', () => {
    const f = $('#booking-form'); if (!f) return;
    $('[name="event_date"]', f).value = b.dataset.day;
    f.scrollIntoView({ behavior: 'smooth', block: 'center' });
    $('[name="event_date"]', f).dispatchEvent(new Event('change'));
  }));
  $$('[data-offer]').forEach(b => b.addEventListener('click', () => {
    const f = $('#booking-form'); if (!f) return;
    const sel = $('[name="talent_offer_id"]', f); if (sel) sel.value = b.dataset.offer;
    f.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }));
  const bf = $('#booking-form');
  if (bf && bf.dataset.busy) {
    const busy = JSON.parse(bf.dataset.busy), d = $('[name="event_date"]', bf), msg = $('[data-date-msg]', bf);
    const check = () => { const bad = busy.includes(d.value); d.setCustomValidity(bad ? bf.dataset.busyMsg : ''); if (msg) msg.hidden = !bad; };
    d?.addEventListener('change', check);
  }

  /* « Composer ma soirée » : estimation */
  const pack = $('[data-pack]');
  if (pack) {
    const draw = () => {
      const n = Math.max(1, +$('[name="guests"]', pack).value || 1);
      const r = $('[name="restaurant"]', pack).selectedOptions[0], meal = r && r.value ? (+r.dataset.avg || 0) * n : 0;
      const offers = $$('select[name="offers[]"]', pack).map(s => s.selectedOptions[0]).filter(o => o && o.value);
      const lines = [...(meal ? [[r.textContent, meal]] : []), ...offers.map(o => [o.dataset.label, +o.dataset.price])];
      const total = lines.reduce((a, l) => a + l[1], 0);
      $('[data-pack-lines]').innerHTML = lines.map(([l, v]) => `<div><span class="min-w-0 flex-grow-1">${esc(l)}</span><span class="tnum fw-semibold">${money(v)}</span></div>`).join('');
      $('[data-pack-total]').textContent = money(total);
      $('[data-pack-per]').textContent = money(total / n);
      $('[data-pack-submit]').disabled = !offers.length;
      $$('select[name="offers[]"]', pack).forEach(s => s.disabled = !s.value && false);
    };
    pack.addEventListener('input', draw); draw();
    pack.addEventListener('submit', () => $$('select[name="offers[]"]', pack).forEach(s => { if (!s.value) s.disabled = true; }));
  }

  /* Galerie : agrandissement */
  $$('[data-lightbox]').forEach(a => a.addEventListener('click', e => {
    e.preventDefault();
    const m = $('#lightbox'); $('img', m).src = a.href; bootstrap.Modal.getOrCreateInstance(m).show();
  }));
})();
