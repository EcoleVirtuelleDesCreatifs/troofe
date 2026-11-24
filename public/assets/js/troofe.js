/* Trôofê — interactions du site (sans dépendance hors Bootstrap) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name="csrf-token"]')?.content;
  const money = n => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';

  /* Barre du haut au défilement */
  const top = $('#topbar');
  if (!document.querySelector('[data-home-motion]')) addEventListener('scroll', () => top && top.classList.toggle('scrolled', scrollY > 8), { passive: true });

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

  /* Onglets de la carte (filtrage par catégorie) */
  $$('#menu-tabs button').forEach(b => b.addEventListener('click', () => {
    $$('#menu-tabs button').forEach(x => x.classList.toggle('on', x === b));
    $$('#menu-list .menu-item').forEach(m => m.hidden = b.dataset.cat && m.dataset.cat !== b.dataset.cat);
  }));

  /* Partager : copie le lien */
  $$('[data-share]').forEach(b => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(location.href); b.classList.add('text-success'); setTimeout(() => b.classList.remove('text-success'), 1500); } catch {}
  }));

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

  /* ---------- Thème clair/sombre : synchronise Bootstrap ---------- */
  const syncTheme = () => {
    const t = document.documentElement.getAttribute('data-theme');
    const dark = t ? t === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
  };
  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncTheme);
  new MutationObserver(syncTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  syncTheme();

  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Apparition au défilement (.rv) */
  if (!reduced() && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('rv-in'); io.unobserve(en.target); } }), { rootMargin: '0px 0px -6% 0px' });
    $$('.rv').filter(el => !el.closest('[data-home-motion]')).forEach((el, k) => { const r = el.getBoundingClientRect(); if (r.top > innerHeight) { el.classList.add('rv-pre'); el.style.transitionDelay = (k % 4) * 60 + 'ms'; io.observe(el); } });
  }

  /* Compteurs animés */
  const animateCount = el => {
    const to = +el.dataset.count, pre = el.dataset.prefix || '', fmt = v => pre + new Intl.NumberFormat('fr-FR').format(v).replace(/ | /g, ' ');
    if (reduced()) { el.textContent = fmt(to); return; }
    const t0 = performance.now(), d = 1100;
    const tick = t => { const p = reduced() ? 1 : Math.min(1, (t - t0) / d), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(Math.round(to * e)); if (p < 1) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  };
  const counts = 'IntersectionObserver' in window && !reduced() ? new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) { counts.unobserve(entry.target); animateCount(entry.target); }
  }), { threshold:.4 }) : null;
  $$('[data-count]').forEach(el => counts ? counts.observe(el) : animateCount(el));

  /* Rails horizontaux : flèches, glisser, progression, auto */
  $$('.rail-wrap').forEach(root => {
    const sc = $('.rail', root), prev = $('[data-rail="-1"]', root), next = $('[data-rail="1"]', root), bar = $('.rail-progress i', root);
    const upd = () => { const max = sc.scrollWidth - sc.clientWidth; if (prev) prev.disabled = sc.scrollLeft <= 4; if (next) next.disabled = sc.scrollLeft >= max - 4;
      if (bar) { const vis = sc.clientWidth / sc.scrollWidth; bar.style.width = Math.max(12, vis * 100) + '%'; bar.style.transform = `translateX(${max > 0 ? (sc.scrollLeft / max) * (100 / Math.max(vis, .12) - 100) : 0}%)`; } };
    const step = d => { const card = sc.firstElementChild; const cw = card ? card.getBoundingClientRect().width + 20 : sc.clientWidth * .8; const per = Math.max(1, Math.floor(sc.clientWidth / cw)); sc.scrollBy({ left: d * cw * per, behavior: reduced() ? 'auto' : 'smooth' }); };
    if (prev) prev.onclick = () => step(-1); if (next) next.onclick = () => step(1);
    let railPending = false;
    sc.addEventListener('scroll', () => { if (railPending) return; railPending = true; requestAnimationFrame(() => { upd(); railPending = false; }); }, { passive: true }); window.addEventListener('resize', upd);
    let down = false, sx = 0, sl = 0, moved = false;
    sc.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse' || e.button) return; down = true; moved = false; sx = e.clientX; sl = sc.scrollLeft; });
    window.addEventListener('pointermove', e => { if (!down) return; const dx = e.clientX - sx; if (Math.abs(dx) > 5 && !moved) { moved = true; sc.classList.add('dragging'); } if (moved) sc.scrollLeft = sl - dx; });
    window.addEventListener('pointerup', () => { if (!down) return; down = false; if (moved) { sc.classList.remove('dragging'); const card = sc.firstElementChild; if (card) { const cw = card.getBoundingClientRect().width + 20; sc.scrollTo({ left: Math.round(sc.scrollLeft / cw) * cw, behavior: 'smooth' }); } } });
    sc.addEventListener('click', e => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
    sc.addEventListener('dragstart', e => e.preventDefault());
    if (root.dataset.auto && !reduced()) { let hover = false, visible = false; root.addEventListener('mouseenter', () => hover = true); root.addEventListener('mouseleave', () => hover = false);
      if ('IntersectionObserver' in window) new IntersectionObserver(entries => { visible = entries[0].isIntersecting; }, { threshold:.15 }).observe(root);
      setInterval(() => { if (!visible || hover || reduced() || document.hidden || down || root.contains(document.activeElement)) return; const max = sc.scrollWidth - sc.clientWidth; if (sc.scrollLeft >= max - 4) sc.scrollTo({ left: 0, behavior: 'smooth' }); else step(1); }, +root.dataset.auto || 4500); }
    upd(); setTimeout(upd, 300);
  });

  /* Carrousel d'en-tête (hs2) : textes en fondu, parallaxe curseur, glisser */
  $$('.hs2').forEach(root => {
    const slides = $$('.hs2-slide', root), dots = $$('.hs2-dot', root), n = slides.length; let i = 0, sx = null, sy = null;
    if (reduced()) root.classList.add('no-auto');
    const go = k => { const prev = i; i = (k + n) % n; if (prev === i) return;
      slides[prev].classList.remove('on'); slides[prev].classList.add('leaving'); setTimeout(() => slides[prev].classList.remove('leaving'), 900);
      slides.forEach((s, j) => { s.setAttribute('aria-hidden', j !== i); s.inert = j !== i; });
      slides[i].classList.add('on');
      dots.forEach((d, j) => { d.classList.remove('on'); d.classList.toggle('done', j < i); if (j === i) { void d.offsetWidth; d.classList.add('on'); } }); };
    slides.forEach((s, j) => s.inert = j !== 0);
    dots.forEach((d, k) => { d.onclick = () => go(k); const b = d.querySelector('i'); if (b) b.addEventListener('animationend', () => { if (k === i) go(i + 1); }); });
    $$('[data-step]', root).forEach(b => b.onclick = () => go(i + +b.dataset.step));
    let offscreen = false;
    const pause = v => root.classList.toggle('paused', v || offscreen || document.hidden);
    const syncPause = () => pause(root.matches(':hover') || root.contains(document.activeElement));
    if ('IntersectionObserver' in window) new IntersectionObserver(entries => { offscreen = !entries[0].isIntersecting; syncPause(); }).observe(root);
    document.addEventListener('visibilitychange', syncPause);
    matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', e => root.classList.toggle('no-auto', e.matches));
    root.addEventListener('mouseenter', () => pause(true)); root.addEventListener('mouseleave', () => { syncPause(); root.style.setProperty('--px', 0); root.style.setProperty('--py', 0); });
    root.addEventListener('focusin', () => pause(true)); root.addEventListener('focusout', () => requestAnimationFrame(syncPause));
    root.addEventListener('keydown', e => { if (e.key === 'ArrowRight') go(i + 1); if (e.key === 'ArrowLeft') go(i - 1); });
    root.addEventListener('mousemove', e => { if (reduced()) return; const b = root.getBoundingClientRect(); root.style.setProperty('--px', ((e.clientX - b.left) / b.width - .5).toFixed(3)); root.style.setProperty('--py', ((e.clientY - b.top) / b.height - .5).toFixed(3)); });
    root.addEventListener('pointerdown', e => { sx = e.clientX; sy = e.clientY; });
    root.addEventListener('pointerup', e => { if (sx === null) return; const dx = e.clientX - sx, dy = e.clientY - sy; if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) go(i + (dx < 0 ? 1 : -1)); sx = null; });
  });
})();
