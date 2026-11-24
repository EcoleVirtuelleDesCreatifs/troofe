/* ============================================================
   Trôofê — Mouvement et navigation fluide
   - l'image d'une carte se transforme en image de la fiche ouverte
   - barre de progression à chaque changement de page
   - en-tête qui s'efface en descendant et revient en remontant
   - cartes et listes qui apparaissent en cascade (filtres compris)
   - parallaxe douce des grandes images
   - boutons principaux légèrement aimantés
   ============================================================ */
(function(){
'use strict';
const motionPreference = matchMedia('(prefers-reduced-motion: reduce)');
const reduced = () => motionPreference.matches;

/* 1. Transition partagée carte → fiche (View Transitions API) */
document.addEventListener('click', e => {
  const a = e.target.closest('a.card-x[href]');
  if (!a || !document.startViewTransition || reduced()) return;
  const img = a.querySelector('img'); if (!img) return;
  document.querySelectorAll('[style*="view-transition-name"]').forEach(x => x.style.viewTransitionName = '');
  document.querySelectorAll('.detail-hero img').forEach(x => x.style.viewTransitionName = 'none');
  img.style.viewTransitionName = 'hero-media';
}, true);

/* 2. Barre de progression de navigation */
const bar = document.createElement('div'); bar.className = 'nav-progress'; bar.innerHTML = '<i></i>'; document.body.appendChild(bar);
const progress = () => { bar.classList.remove('go'); void bar.offsetWidth; bar.classList.add('go'); setTimeout(() => bar.classList.remove('go'), 900); };
window.addEventListener('hashchange', progress);
window.addEventListener('DOMContentLoaded', progress);

/* 3. En-tête qui s'efface en descendant */
const topbar = document.getElementById('topbar');
const home = document.querySelector('[data-home-motion]');
const reading = home && home.querySelector('.home-scroll-progress i');
const hero = home && home.querySelector('.hs2');
const desktopMotion = matchMedia('(min-width:901px) and (pointer:fine)');
const backTop = home && home.querySelector('[data-home-top]');
let heroDrift = '';
const parallaxImages = [...document.querySelectorAll('.detail-hero img, .ent-hero > img')];
let lastY = scrollY, ticking = false, scrollRange = 1, heroHeight = innerHeight;
function measure(){
  scrollRange = Math.max(1, document.documentElement.scrollHeight - innerHeight);
  if (hero) heroHeight = hero.offsetHeight || innerHeight;
  scheduleScroll();
}
function scheduleScroll(){
  if (ticking) return;
  ticking = true;
  requestAnimationFrame(() => {
    const y = Math.max(0, scrollY);
    if (topbar){
      if (Math.abs(y - lastY) > 6 || y < 240) topbar.classList.toggle('tb-hide', y > lastY && y > 240 && !topbar.matches(':focus-within') && !topbar.querySelector('.dropdown-menu.show'));
      topbar.classList.toggle('scrolled', y > 8);
    }
    if (reading) reading.style.transform = `scaleX(${Math.min(1, y / scrollRange)})`;
    if (backTop) backTop.classList.toggle('is-visible', y > Math.max(480, heroHeight));
    if (hero){
      const drift = !reduced() && desktopMotion.matches ? (Math.min(y, heroHeight) * .045).toFixed(1) + 'px' : '0px';
      if (drift !== heroDrift){ hero.style.setProperty('--home-drift', drift); heroDrift = drift; }
    }
    lastY = y; ticking = false;
    /* 5. Parallaxe des grandes images */
    if (!reduced()) parallaxImages.forEach(im => { const r = im.parentElement.getBoundingClientRect(); if (r.bottom < 0 || r.top > innerHeight) return; im.style.setProperty('--par', (r.top * -0.12).toFixed(1) + 'px'); });
  });
}
window.addEventListener('scroll', scheduleScroll, { passive:true });
window.addEventListener('resize', measure, { passive:true });
window.addEventListener('load', measure, { once:true });
window.addEventListener('pageshow', measure);
if (home && 'ResizeObserver' in window) new ResizeObserver(measure).observe(document.documentElement);
if (backTop) backTop.addEventListener('click', () => {
  const heading = home.querySelector('h1');
  if (heading){ heading.setAttribute('tabindex', '-1'); heading.focus({ preventScroll:true }); }
  window.scrollTo({ top:0, behavior:reduced() ? 'auto' : 'smooth' });
});
measure();

/* 4. Apparitions en cascade : à l'insertion et au défilement */
const SEL = '.home-flow .rv, .card-x, .rail-item, .svc, .ency-list a, .agenda a, .news a, .figures > div, .menu-item, .timeline-item, .feed-item, .ev-row, .type-row, .sh, .section-head, .panel, .kpi, .mm-item, .g-item, .city, .slot-card, .owner-card, .pro-card';
const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); } }), { rootMargin:'0px 0px 64px 0px', threshold:0.06 }) : null;
function prime(root){
  if (reduced() || !io) return;
  const els = [...(root.matches && root.matches(SEL) ? [root] : []), ...root.querySelectorAll(SEL)];
  const groups = new Map();
  els.forEach(el => { if (el.dataset.mo || el.closest('.modal, .offcanvas, .dropdown-menu, .hs2, .table-wrap') || el.parentElement?.closest('.mo')) return;
    if (home?.contains(el) && el.matches('.services, .ency-list, .agenda, .news, .figures')) return;
    el.dataset.mo = 1;
    const r = el.getBoundingClientRect(); const p = el.parentElement; const k = groups.get(p) || 0; groups.set(p, k + 1);
    el.style.setProperty('--i', Math.min(k, 5));
    el.classList.add('mo');
    if (r.top < innerHeight && r.bottom > 0){ requestAnimationFrame(() => el.classList.add('in')); } else io.observe(el); });
}
const app = document.getElementById('main'), adm = document.getElementById('admin-content');
const mo = new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => { if (n.nodeType === 1) prime(n); })));
[app, adm].forEach(r => r && mo.observe(r, { childList:true, subtree:true }));
if (app) (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => prime(app)) : prime(app));
/* filet de sécurité : rien ne reste masqué si l'observateur ne se déclenche pas */
document.addEventListener('focusin', e => { e.target.closest('.mo')?.classList.add('in'); });
motionPreference.addEventListener('change', () => {
  if (reduced()) document.querySelectorAll('.mo, .rv-pre').forEach(el => el.classList.add('in', 'rv-in'));
  scheduleScroll();
});

/* 6. Boutons aimantés (pointeur fin uniquement) */
if (matchMedia('(pointer:fine)').matches){
  document.addEventListener('pointermove', e => { const b = e.target.closest && e.target.closest('.btn-ocre, .btn-brand, .hs2-arrow'); if (!b || reduced()) return; const r = b.getBoundingClientRect(); b.style.setProperty('--mx', ((e.clientX - r.left - r.width/2) * .18).toFixed(1) + 'px'); b.style.setProperty('--my', ((e.clientY - r.top - r.height/2) * .25).toFixed(1) + 'px'); });
  document.addEventListener('pointerout', e => { const b = e.target.closest && e.target.closest('.btn-ocre, .btn-brand, .hs2-arrow'); if (b && !b.contains(e.relatedTarget)){ b.style.setProperty('--mx','0px'); b.style.setProperty('--my','0px'); } });
}
})();
