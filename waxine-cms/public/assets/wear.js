// Webartrose: signs of use (see lib/wear.php and "Webartrose" in screen.css).
//
// Everything you can see or touch wears with use, like things do in the
// real world. This script counts, per element, how often it is touched
// (pointer rests on it), pressed (clicked) and seen (in view long enough to
// look at or read), and per square of the grid how often the pointer passes.
// The counts of all visitors are added up on the server; nothing about the
// visitor is stored. The wear is then drawn on the element itself:
//
//   links       greasy and rubbed thin where fingers go
//   menu items  hang crooked and loose; a much-used one flickers like neon
//   cards       corners worn round, fingerprints, colours bleached by the sun
//   photos      fade and yellow; rounded corners where they are handled
//   text        ink fades, a thumb mark at the edge where it is held
//   buttons     pressed in, outline rubbed away
//   the floor   paths appear where the pointer walks, rubbing out the grid
//
// Something nobody touched for months flickers once when it comes back on.
// The switch turns the layer off; the time slider shows the site as new, as
// it is now, or as it may look up to 30 years from now if it goes on being
// used like this. Never so worn it stops working.
(function () {
  'use strict';

  var root = document.documentElement;
  var wrapper = document.getElementById('wrapper');
  if (!wrapper || !window.fetch) return;

  var page = location.pathname.replace(/\/+$/, '') || '/';
  var NOW = 25;                       // slider position of "now"
  var settings = { on: true, time: NOW };
  try {
    var saved = JSON.parse(localStorage.getItem('webartrose') || 'null');
    if (saved && typeof saved.on === 'boolean') settings.on = saved.on;
    if (saved && typeof saved.time === 'number') settings.time = Math.max(0, Math.min(100, saved.time));
  } catch (e) {}
  function save() { try { localStorage.setItem('webartrose', JSON.stringify(settings)); } catch (e) {} }

  var recordUse = !(navigator.globalPrivacyControl || navigator.doNotTrack === '1' || window.doNotTrack === '1');

  // ---------------------------------------------------------------- helpers

  function hash(s) {                  // short, stable fingerprint of a text
    var h = 2166136261;
    for (var i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
    return (h >>> 0).toString(36);
  }
  function seed(key) {                // -1..1, the same for an element on every visit
    var h = parseInt(hash(key + '~'), 36);
    return (h % 2001) / 1000 - 1;
  }
  function pathOf(url) {
    try {
      var u = new URL(url, location.href);
      if (u.origin === location.origin) return u.pathname.replace(/\/+$/, '') || '/';
      return '//' + u.host + u.pathname.replace(/\/+$/, '');
    } catch (e) { return null; }
  }
  function textOf(el) { return (el.textContent || '').replace(/\s+/g, ' ').trim(); }
  function square() {
    var probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;visibility:hidden;height:var(--u)';
    document.body.appendChild(probe);
    var u = probe.getBoundingClientRect().height || 18;
    probe.remove();
    return u;
  }

  // ------------------------------------------------- what can wear, and how

  // Each thing gets a key (shared by every element showing the same thing,
  // so the menu link to Talks and a link to Talks in a text wear together),
  // a host (the element that shows the wear) and a kind.
  var things = [];          // {key, kind, host, el, seed}
  var byKey = {};

  function add(el, kind, key, host) {
    if (!key || key.length > 180 || /\s/.test(key.slice(kind.length + 1))) return;
    var full = kind + ' ' + key;
    var t = { key: full, kind: kind, host: host || el, el: el, seed: seed(full) };
    things.push(t);
    (byKey[full] = byKey[full] || []).push(t);
  }

  document.querySelectorAll('a.link-figure').forEach(function (a) {
    add(a, 'card', pathOf(a.getAttribute('href')), a.querySelector('.article-figure') || a);
  });
  document.querySelectorAll('#menu a, .content a, .article-nav a, .up-one-level a').forEach(function (a) {
    if (a.matches('a.link-figure') || !a.getAttribute('href')) return;
    var host = a.closest('#menu .nav-item') || a.closest('ul.bullit > li') || a;
    add(a, 'a', pathOf(a.getAttribute('href')), host);
  });
  document.querySelectorAll('.content img, .header img').forEach(function (img) {
    add(img, 'img', pathOf(img.currentSrc || img.getAttribute('src')));
  });
  document.querySelectorAll('summary, .content button').forEach(function (b) {
    add(b, 'b', page + '#' + hash(textOf(b)));
  });
  document.querySelectorAll('.header h1, .content :is(p, h2, h3, li, blockquote)').forEach(function (el) {
    var txt = textOf(el);
    if (txt.length < 2 || el.closest('ul.bullit')) return;
    add(el, 't', page + '#' + hash(txt.slice(0, 120)));
  });
  if (things.length > 400) things = things.slice(0, 400);

  // Story age: an old story has been in the sun longer.
  var published = document.querySelector('[data-published]');
  var storyYears = 0;
  if (published) {
    var d = new Date(published.getAttribute('data-published'));
    if (!isNaN(d)) storyYears = Math.max(0, (Date.now() - d) / 31557600000);
  }

  // ------------------------------------------------------------ the counts

  var server = {};          // key -> [touch, press, seen, idleDays]
  var mine = {};            // key -> [touch, press, seen], this page view
  var trailServer = [];     // [cx, cy, n]
  var trailMine = {};       // "cx,cy" -> n, this page view
  var since = null;         // when the site started keeping count

  function bump(key, i) {
    var c = mine[key] = mine[key] || [0, 0, 0];
    if (c[i] >= 3) return;  // one page view adds at most 3 to a counter
    c[i]++;
    apply(byKey[key] || []);
  }

  // ------------------------------------------------------- from counts to wear

  // Time on the slider. Left of "now" the use so far is scaled down to
  // nothing (as new). Right of it runs up to 30 years ahead: the use goes on
  // at the rate the site has worn so far, so the wear is that much more.
  var YEARS_AHEAD = 30;
  function yearsAhead() {
    var s = settings.time;
    return s <= NOW ? 0 : YEARS_AHEAD * Math.pow((s - NOW) / (100 - NOW), 2);
  }
  function factor() {
    var s = settings.time;
    return s <= NOW ? s / NOW : 1 + yearsAhead() / siteYears();
  }
  // Wear grows fast at first and slowly after: 10 uses show, 10.000 is worn through.
  function level(n, m) { return Math.min(1, Math.log10(1 + n * m) / 4); }

  function apply(list) {
    var m = factor();
    var ageYears = storyYears * Math.min(1, m) + yearsAhead();
    list.forEach(function (t) {
      var s = server[t.key] || [0, 0, 0, 0], o = mine[t.key] || [0, 0, 0];
      var wt = level(s[0] + o[0], m), wp = level(s[1] + o[1], m), ws = level(s[2] + o[2], m);
      var wa = t.kind === 'img' || t.kind === 't' ? Math.min(1, ageYears / 30) : 0;
      var st = t.host.style;
      st.setProperty('--wt', wt.toFixed(3));
      st.setProperty('--wp', wp.toFixed(3));
      st.setProperty('--ws', ws.toFixed(3));
      st.setProperty('--wa', wa.toFixed(3));
      st.setProperty('--wr', t.seed.toFixed(3));
      // A much-pressed menu item or button starts to flicker, like old neon.
      t.host.classList.toggle('wear-flicker', wp > 0.45 && t.seed > 0.2 && (t.kind === 'a' || t.kind === 'b') && !!t.host.closest('#menu, ul.bullit'));
    });
  }
  function applyAll() { apply(things); drawTrail(); }

  function siteYears() {
    if (!since) return 1 / 12;
    var d = new Date(since.replace(' ', 'T'));
    return isNaN(d) ? 1 / 12 : Math.max(1 / 12, (Date.now() - d) / 31557600000);
  }

  // Something nobody touched for months warms up once when the page opens.
  function warmUp() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    things.forEach(function (t) {
      var s = server[t.key];
      if (s && s[3] >= 60 && t.kind !== 't') t.host.classList.add('wear-warmup');
    });
    setTimeout(function () {
      document.querySelectorAll('.wear-warmup').forEach(function (el) { el.classList.remove('wear-warmup'); });
    }, 2500);
  }

  // ------------------------------------------------- the floor: pointer paths

  // Paths are kept per square of the grid, counted from the page's centre
  // line and the top of the page, so they stay put across screen sizes.
  var canvas = document.createElement('canvas');
  canvas.className = 'wear-trail';
  canvas.setAttribute('aria-hidden', 'true');
  wrapper.insertBefore(canvas, wrapper.firstChild);
  var u = square(), half = 0, rows = 0, x0 = 0;

  function measure() {
    u = square();
    var r = wrapper.getBoundingClientRect();
    x0 = r.left + scrollX + r.width / 2 - u / 2;          // a grid line at the centre
    half = Math.ceil(r.width / 2 / u) + 1;
    rows = Math.ceil(wrapper.scrollHeight / u) + 1;
  }
  function drawTrail() {
    if (!settings.on) return;
    measure();
    var cols = half * 2;
    if (canvas.width !== cols) canvas.width = cols;
    if (canvas.height !== rows) canvas.height = rows;
    canvas.style.width = cols * u + 'px';
    canvas.style.height = rows * u + 'px';
    canvas.style.left = (x0 - wrapper.getBoundingClientRect().left - scrollX - half * u) + 'px';
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, cols, rows);
    var m = factor(), cells = {};
    trailServer.forEach(function (c) { cells[c[0] + ',' + c[1]] = (cells[c[0] + ',' + c[1]] || 0) + c[2]; });
    Object.keys(trailMine).forEach(function (k) { cells[k] = (cells[k] || 0) + trailMine[k]; });
    Object.keys(cells).forEach(function (k) {
      var p = k.split(','), a = Math.min(1, Math.log10(1 + cells[k] * m) / 3);
      if (a <= 0) return;
      ctx.fillStyle = 'rgba(232,226,218,' + (a * 0.9).toFixed(3) + ')';
      ctx.fillRect(+p[0] + half, +p[1], 1, 1);
    });
  }

  var lastMove = 0, drawQueued = false;
  function walk(x, y) {
    var now = Date.now();
    if (now - lastMove < 60) return;
    lastMove = now;
    var top = wrapper.getBoundingClientRect().top + scrollY;
    var cx = Math.floor((x - x0) / u), cy = Math.floor((y - top) / u);
    if (cy < 0 || Math.abs(cx) > 80) return;
    var k = cx + ',' + cy;
    if (!(k in trailMine) && Object.keys(trailMine).length >= 600) return;
    if ((trailMine[k] || 0) >= 5) return;
    trailMine[k] = (trailMine[k] || 0) + 1;
    if (!drawQueued) { drawQueued = true; setTimeout(function () { drawQueued = false; drawTrail(); }, 300); }
  }
  document.addEventListener('pointermove', function (e) { walk(e.pageX, e.pageY); }, { passive: true });
  document.addEventListener('touchmove', function (e) {
    var t = e.touches[0]; if (t) walk(t.pageX, t.pageY);
  }, { passive: true });

  // ---------------------------------------------- touched, pressed and seen

  things.forEach(function (t) {
    if (t.kind === 't') return;
    var timer = null, done = false;
    t.el.addEventListener('pointerenter', function () {
      if (done) return;
      timer = setTimeout(function () { done = true; bump(t.key, 0); }, 250);
    });
    t.el.addEventListener('pointerleave', function () { clearTimeout(timer); });
  });
  document.addEventListener('click', function (e) {
    var hit = {};
    things.forEach(function (t) {
      if (!hit[t.key] && t.kind !== 't' && t.el.contains(e.target)) { hit[t.key] = true; bump(t.key, 1); }
    });
  }, true);

  if ('IntersectionObserver' in window) {
    var timers = new Map(), seenOnce = new Set();
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        var t = en.target.__wear;
        if (!t || seenOnce.has(t.key)) return;
        if (en.isIntersecting) {
          // Long enough in view to look at a picture, or to read a text.
          timers.set(en.target, setTimeout(function () { seenOnce.add(t.key); bump(t.key, 2); },
            t.kind === 't' ? 2500 : 1000));
        } else {
          clearTimeout(timers.get(en.target));
        }
      });
    }, { threshold: 0.6 });
    things.forEach(function (t) {
      if (t.kind === 'a' && t.el.closest('#menu')) return;   // always in view: not "seen"
      t.el.__wear = t;
      io.observe(t.el);
    });
  }

  // ------------------------------------------------------ talk to the server

  function send() {
    if (!recordUse) return;
    var events = Object.keys(mine).map(function (k) { return [k, mine[k][0], mine[k][1], mine[k][2]]; });
    var trail = Object.keys(trailMine).map(function (k) { var p = k.split(','); return [+p[0], +p[1], trailMine[k]]; });
    if (!events.length && !trail.length) return;
    var body = JSON.stringify({ op: 'hit', page: page, events: events, trail: trail });
    var sent = navigator.sendBeacon && navigator.sendBeacon('/wear.php', new Blob([body], { type: 'application/json' }));
    if (!sent) fetch('/wear.php', { method: 'POST', body: body, keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(function () {});
    // What is sent now becomes part of the shared wear; start counting afresh.
    events.forEach(function (e) { var s = server[e[0]] = server[e[0]] || [0, 0, 0, 0]; s[0] += e[1]; s[1] += e[2]; s[2] += e[3]; s[3] = 0; });
    trail.forEach(function (c) { trailServer.push(c); });
    mine = {}; trailMine = {};
  }
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') send(); });
  window.addEventListener('pagehide', send);

  var keys = Object.keys(byKey);
  fetch('/wear.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ op: 'read', page: page, keys: keys })
  }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
    if (!data) return;
    server = data.elements || {};
    trailServer = data.trail || [];
    since = data.since || null;
    applyAll();
    if (settings.on) warmUp();
    updateLabel();
  }).catch(function () {});

  // ------------------------------------------------------------ the control

  var control = document.createElement('div');
  control.className = 'wear-control';
  control.innerHTML =
    '<button type="button" class="wear-button" aria-expanded="false" aria-controls="wear-panel" aria-label="Signs of use">' +
      '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">' +
        '<path d="M5 15c0-4 3-8 7-8s7 4 7 8"/><path d="M8 17c0-3 1.8-6 4-6s4 3 4 6"/><path d="M11.2 19c0-2 .3-4 .8-4s.8 2 .8 4" stroke-dasharray="1.5 1.8"/>' +
      '</svg>' +
    '</button>' +
    '<div class="wear-panel" id="wear-panel" role="group" aria-labelledby="wear-title" hidden>' +
      '<p class="wear-title" id="wear-title">Webartrose</p>' +
      '<p class="wear-intro">Everything here wears with use, like the things around you. What visitors touch, press and read leaves a trace on it. Only anonymous counts are kept: no cookies, nothing about you.</p>' +
      '<label class="wear-switch"><input type="checkbox" id="wear-on"> Show signs of use</label>' +
      '<label class="wear-label" for="wear-time">Time <output id="wear-when" for="wear-time"></output></label>' +
      '<input type="range" id="wear-time" min="0" max="100" step="1">' +
      '<div class="wear-scale" aria-hidden="true"><span>As new</span><span>Now</span><span>Years from now</span></div>' +
    '</div>';
  document.body.appendChild(control);

  var button = control.querySelector('.wear-button');
  var panel = control.querySelector('.wear-panel');
  var onBox = control.querySelector('#wear-on');
  var slider = control.querySelector('#wear-time');
  var when = control.querySelector('#wear-when');

  function whenText() {
    var s = settings.time;
    if (s === 0) return 'as new';
    if (s < NOW) return 'less used';
    if (s === NOW) return 'now';
    var years = yearsAhead();
    if (years < 1) return 'in ' + Math.max(1, Math.round(years * 12)) + ' months';
    if (years < 1.5) return 'in about a year';
    if (years >= YEARS_AHEAD - 0.01) return 'in ' + YEARS_AHEAD + ' years';
    return 'in about ' + (years < 10 ? Math.round(years) : Math.round(years / 5) * 5) + ' years';
  }
  function updateLabel() {
    when.textContent = whenText();
    slider.setAttribute('aria-valuetext', when.textContent);
  }
  function setOn(on) {
    settings.on = on;
    root.classList.toggle('wear-on', on);
    onBox.checked = on;
    slider.disabled = !on;
    if (on) applyAll();
  }

  slider.value = settings.time;
  setOn(settings.on);
  updateLabel();

  onBox.addEventListener('change', function () { setOn(onBox.checked); save(); });
  slider.addEventListener('input', function () {
    settings.time = +slider.value;
    // Snap to "now" when close, so it is easy to go back.
    if (Math.abs(settings.time - NOW) <= 1) { settings.time = NOW; slider.value = NOW; }
    updateLabel();
    applyAll();
    save();
  });
  function open(yes) {
    panel.hidden = !yes;
    button.setAttribute('aria-expanded', yes ? 'true' : 'false');
  }
  button.addEventListener('click', function () { open(panel.hidden); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { open(false); button.focus(); } });
  document.addEventListener('click', function (e) { if (!panel.hidden && !control.contains(e.target)) open(false); });

  var resizeTimer;
  window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(drawTrail, 150); });
  window.addEventListener('load', drawTrail);
})();
