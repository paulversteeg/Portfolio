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
// And since November 2026, after a series of photos of things worn by time:
//
//   dead clicks     scuffs where people click on something that does nothing,
//                   or just next to a button (a fake door at IKEA)
//   how far         the edge of the page is worn as far as people get down it
//                   (monkey bars: not many kids make it past the 5th bar)
//   the slider      the time slider itself is worn where people set it
//                   (the paint gone from the weights used most)
//   tan line        the screen yellows in the sun, except behind the round
//                   button that always covers it (a can in a cup holder)
//   worn through    the menu item of where you are wears through to the grid
//                   paper; a much-touched card shows the grid through it
//                   (a globe where people point, a rug turned to cobbles)
//   staples         every visit leaves a staple at the foot of the page
//                   (a lamp post after 40 years of posters)
//   graphite        text people select gets a grey smudge (a clipboard)
//   routes          the ways between sections wear into the menu, like tyre
//                   marks on a parking roof
//   dog-ears        a page that arrives through shared links gets a folded
//                   corner, like a banknote that went round
//   the maker       the maker's own edits wear a spot beside the title (the
//                   floor where a barber stands)
//   a waist         a much-pressed button narrows in the middle (a post
//                   licked smooth by cows)
//
// A dead-click mark only shows once it was counted a few times, so it never
// gives away what one visitor did (a worn keypad gives away the code).
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
    if (el.closest('.wear-demo')) return;   // the examples on the Webartrose page keep their fixed wear
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
  var marksServer = [];     // [kind, cx, cy, n]: c dead click, d how far down
  var clicksMine = {};      // "cx,cy" -> n, this page view
  var depthMine = 0;        // the lowest row of the page seen in this page view
  var depthSent = false;

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
      if (t.kind === 't') st.setProperty('--wg', wp.toFixed(3));   // graphite: selected text
      if (t.kind === 'card') dogEar(t.host, count('e ' + t.key.slice(5), 0, m));
      // A much-pressed menu item or button starts to flicker, like old neon.
      t.host.classList.toggle('wear-flicker', wp > 0.45 && t.seed > 0.2 && (t.kind === 'a' || t.kind === 'b') && !!t.host.closest('#menu, ul.bullit'));
    });
  }
  function applyAll() { apply(things); drawTrail(); drawMore(); }

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
    rows = Math.ceil(wrapper.offsetHeight / u) + 1;
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


  // ---------------------------------------- more signs (Nov 2026, see the top)

  // Things that are counted but are not one element on the page.
  function count(key, i, m) {
    var s = server[key] || [0, 0, 0, 0], o = mine[key] || [0, 0, 0];
    return level(s[i] + o[i], m);
  }
  function raw(key, i) {
    var s = server[key] || [0, 0, 0, 0], o = mine[key] || [0, 0, 0];
    return s[i] + o[i];
  }
  function rng(key) {               // a seeded random row of numbers, the same on every visit
    var h = parseInt(hash(key), 36) || 1;
    return function () { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return ((h >>> 0) % 100000) / 100000; };
  }

  // Menu sections: a post counts for Thoughts, a case for Work (as in head.php).
  var navLinks = Array.prototype.slice.call(document.querySelectorAll('#menu .nav-item > a'));
  var sections = navLinks.map(function (a) { return pathOf(a.getAttribute('href')); });
  function sectionOf(p) {
    if (!p || p.indexOf('//') === 0) return null;
    var best = null;
    sections.forEach(function (h) {
      if (h !== '/' && (p === h || p.indexOf(h + '/') === 0) && (!best || h.length > best.length)) best = h;
    });
    return best || (sections.indexOf('/') >= 0 ? '/' : null);
  }
  var here = sectionOf(page);
  var extraKeys = ['h ' + page, 'e ' + page, 'm ' + page];
  if (here && here !== page) extraKeys.push('h ' + here);
  for (var b = 0; b <= 20; b++) extraKeys.push('s ' + b);
  sections.forEach(function (a) {
    sections.forEach(function (c) { if (a !== c) extraKeys.push('r ' + a + '>' + c); });
  });
  things.forEach(function (t) { if (t.kind === 'card' && t.key.length < 180) extraKeys.push('e ' + t.key.slice(5)); });

  // A page view: a staple at the foot of the page, wear on "you are here".
  // Arriving from outside the site (a shared link): the page goes round.
  function arrived() {
    bump('h ' + page, 2);
    if (here && here !== page) bump('h ' + here, 2);
    var ref = document.referrer, outside = !ref;
    try { if (ref) outside = new URL(ref).origin !== location.origin; } catch (e) {}
    if (outside && page !== '/') bump('e ' + page, 0);
  }

  function el(tag, cls, parent, first) {
    var e = parent.querySelector(':scope > .' + cls);
    if (!e) {
      e = document.createElement(tag);
      e.className = cls;
      e.setAttribute('aria-hidden', 'true');
      if (first) parent.insertBefore(e, parent.firstChild); else parent.appendChild(e);
    }
    return e;
  }

  // Dog-ears: a folded corner, bigger the more a page went round.
  function dogEar(host, we) {
    host.style.setProperty('--we', we.toFixed(3));
    if (we > 0.05) el('span', 'wear-dogear', host);
  }

  var SCUFF_DEFS = '<defs><filter id="wear-blur" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="2.5"/></filter></defs>';
  // Scuffs: a smudge with short scratches, more of them the more it was hit.
  function scuffs(svg, x, y, size, a, key) {
    if (a <= 0) return '';
    var r = rng(key), out = '<g filter="url(#wear-blur)">';
    for (var b = 0; b < 4; b++) {               // a grimy patch, not a disc
      out += '<circle cx="' + (x + (r() - .5) * size).toFixed(1) + '" cy="' + (y + (r() - .5) * size * .8).toFixed(1) +
        '" r="' + (size * (.2 + r() * .3) * (.6 + a * .6)).toFixed(1) + '" fill="rgba(110,92,70,' + (.03 + a * .06).toFixed(3) + ')"/>';
    }
    out += '</g>';
    var n = Math.round(3 + a * 22);
    for (var i = 0; i < n; i++) {
      var px = x + (r() - .5) * size * 1.6, py = y + (r() - .5) * size * 1.4;
      var ang = r() * Math.PI, len = 1.5 + r() * 4.5;
      out += '<line x1="' + px.toFixed(1) + '" y1="' + py.toFixed(1) + '" x2="' + (px + Math.cos(ang) * len).toFixed(1) +
        '" y2="' + (py + Math.sin(ang) * len).toFixed(1) + '" stroke="rgba(85,70,52,' + (.18 + a * .32 * r()).toFixed(3) + ')"/>';
    }
    return out;
  }
  var marksSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  marksSvg.setAttribute('class', 'wear-marks');
  marksSvg.setAttribute('aria-hidden', 'true');
  wrapper.appendChild(marksSvg);
  function drawMarks(m) {
    var r = wrapper.getBoundingClientRect(), left = x0 - (r.left + scrollX);
    marksSvg.setAttribute('width', r.width);
    marksSvg.setAttribute('height', wrapper.offsetHeight);
    var cells = {};
    marksServer.forEach(function (c) { if (c[0] === 'c') cells[c[1] + ',' + c[2]] = (cells[c[1] + ',' + c[2]] || 0) + c[3]; });
    Object.keys(clicksMine).forEach(function (k) { cells[k] = (cells[k] || 0) + clicksMine[k]; });
    var out = '';
    Object.keys(cells).forEach(function (k) {
      var p = k.split(',');
      out += scuffs(marksSvg, left + (+p[0] + .5) * u, (+p[1] + .5) * u, u, Math.min(1, Math.log10(1 + cells[k] * m) / 2.5), page + k);
    });
    marksSvg.innerHTML = out && SCUFF_DEFS + out;
  }

  // How far down: the page edge is worn as far as people get.
  var reach = el('div', 'wear-reach', wrapper);
  function drawReach(m) {
    var rows = {}, total = 0;
    marksServer.forEach(function (c) { if (c[0] === 'd') { rows[c[2]] = (rows[c[2]] || 0) + c[3]; total += c[3]; } });
    if (depthMine > 0) { rows[depthMine] = (rows[depthMine] || 0) + 1; total++; }
    var height = Math.ceil(wrapper.offsetHeight / u);
    if (!total || !height) { reach.style.backgroundImage = 'none'; return; }
    var strength = level(total, m), stops = [], left = total, steps = Math.min(48, height);
    var sorted = Object.keys(rows).map(Number).sort(function (a, b) { return a - b; }), i = 0;
    for (var s = 0; s <= steps; s++) {
      var row = Math.round(s * height / steps);
      while (i < sorted.length && sorted[i] < row) { left -= rows[sorted[i]]; i++; }
      stops.push('rgba(120,98,70,' + (left / total * strength * .4).toFixed(3) + ') ' + (s * 100 / steps).toFixed(1) + '%');
    }
    reach.style.backgroundImage = 'linear-gradient(' + stops.join(',') + ')';
  }
  function measureDepth() {
    var top = wrapper.getBoundingClientRect().top + scrollY;
    var row = Math.floor((scrollY + innerHeight - top) / u);
    if (row > depthMine) depthMine = Math.min(row, Math.ceil(wrapper.offsetHeight / u));
  }

  // The tan line: the screen yellows, but not behind the round button.
  var sun = el('div', 'wear-sun', document.body);
  var wearTrack = null;     // the slider's track, once the control is there

  // Worn through: where you are, the menu wears through to the grid paper.
  var active = document.querySelector('#menu .nav-item.is-active');
  function drawHere(m) {
    if (!active) return;
    var wr = wrapper.getBoundingClientRect(), r = active.getBoundingClientRect();
    var w = wr.width, lx = r.left - wr.left, ty = r.top - wr.top;
    var mod = function (v) { return ((v % u) + u) % u; };
    active.style.setProperty('--gx', mod((w - u) / 2 - lx).toFixed(2) + 'px');
    active.style.setProperty('--gy', mod(-ty).toFixed(2) + 'px');
    active.style.setProperty('--wh', count('h ' + (here || page), 2, m).toFixed(3));
  }

  // The maker: wear beside the title where the maker stands.
  var header = document.querySelector('main .header, .header');
  function drawHeader(m) {
    if (!header) return;
    var wm = count('m ' + page, 0, Math.max(m, 0.0001) * 40);   // few edits, each one shows
    header.style.setProperty('--wm', wm.toFixed(3));
    if (wm > 0.02) el('span', 'wear-maker', header);
    dogEar(header, count('e ' + page, 0, m));
  }

  // Staples: one for every visit, at the foot of the page.
  var foot = document.querySelector('.footer');
  function staples(canvas, n, key) {
    var dpr = Math.min(2, window.devicePixelRatio || 1), w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
    var ctx = canvas.getContext('2d'), r = rng(key);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    ctx.lineCap = 'round';
    n = Math.min(5000, Math.round(n));
    for (var i = 0; i < n; i++) {
      var x = r() * w, y = 2 + r() * (h - 4), a = (r() - .5) * 1.2 + (r() < .2 ? Math.PI / 2 : 0), len = 4 + r() * 3;
      var dx = Math.cos(a) * len / 2, dy = Math.sin(a) * len / 2, rust = r() < .25;
      ctx.strokeStyle = rust ? 'rgba(120,80,50,.55)' : 'rgba(70,72,76,.6)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(x - dx - dy * .25, y - dy + dx * .25); ctx.lineTo(x - dx, y - dy);
      ctx.lineTo(x + dx, y + dy); ctx.lineTo(x + dx - dy * .25, y + dy + dx * .25);
      ctx.stroke();
      if (!rust) { ctx.strokeStyle = 'rgba(255,255,255,.35)'; ctx.beginPath(); ctx.moveTo(x - dx * .6, y - dy * .6 - .5); ctx.lineTo(x + dx * .2, y + dy * .2 - .5); ctx.stroke(); }
    }
  }
  var post = foot && el('canvas', 'wear-staples', foot);
  function drawStaples(m) {
    if (!post) return;
    var n = raw('h ' + page, 2) * m / 10;   // a staple for every ten visits
    foot.classList.toggle('has-staples', n >= 1);
    if (n >= 1) staples(post, n, 'staples ' + page);
  }

  // Routes: the ways between sections wear into the menu like tyre marks.
  var nav = document.querySelector('nav.nav');
  var routes = nav && navLinks.length > 1 && el('canvas', 'wear-routes', nav, true);
  function drawRoutes(canvas, items, pairs, m) {   // items: [{x, el}], pairs: [[from, to, n]]
    var dpr = Math.min(2, window.devicePixelRatio || 1), w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
    var ctx = canvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    ctx.lineCap = 'round';
    pairs.forEach(function (p) {
      // Slower than other wear: a route needs many walks before it shows.
      var a = Math.min(1, Math.log10(1 + p[2] * m / 4) / 4.5), from = items[p[0]], to = items[p[1]];
      if (a <= 0 || !from || !to) return;
      // Only in the empty bands above and below the titles, never across them:
      // to the right along the bottom, back to the left along the top.
      var r = rng('route' + p[0] + '>' + p[1]), right = p[0] < p[1];
      var y = right ? h - u / 2 : u / 2, bulge = Math.min(u * .3, Math.abs(to.x - from.x) * .05) * (right ? -1 : 1);
      for (var k = 0; k < 2 + Math.round(a * 3); k++) {
        var j = (r() - .5) * u * .3;
        ctx.strokeStyle = 'rgba(95,80,60,' + (.015 + a * .05).toFixed(3) + ')';
        ctx.lineWidth = 2 + a * 3 * r();
        ctx.beginPath();
        ctx.moveTo(from.x, y + j);
        ctx.quadraticCurveTo((from.x + to.x) / 2, y + bulge + j, to.x, y + j);
        ctx.stroke();
      }
    });
  }
  function drawMenuRoutes(m) {
    if (!routes) return;
    var nr = routes.getBoundingClientRect(), items = navLinks.map(function (a) {
      var r = a.closest('.nav-item').getBoundingClientRect();
      return { x: r.left - nr.left + r.width / 2 };
    }), pairs = [];
    sections.forEach(function (a, i) {
      sections.forEach(function (c, j) { if (i !== j) pairs.push([i, j, raw('r ' + a + '>' + c, 0)]); });
    });
    drawRoutes(routes, items, pairs, m);
  }

  // The slider: worn where people set it.
  function drawSliderWear(track, m) {
    var g = [];
    for (var b = 0; b <= 20; b++) {
      var a = count('s ' + b, 1, m);
      if (a > 0.02) g.push('radial-gradient(ellipse ' + (6 + a * 10).toFixed(1) + 'px 5px at calc(8px + (100% - 16px) * ' + (b / 20) +
        ') 50%, rgba(244,238,228,' + (.5 + a * .45).toFixed(3) + ') 55%, rgba(150,130,100,' + (a * .35).toFixed(3) + ') 75%, transparent)');
    }
    track.style.backgroundImage = g.join(',') || 'none';
  }

  function drawMore() {
    if (!settings.on) return;
    var m = factor();
    measure();
    drawMarks(m);
    drawReach(m);
    drawHere(m);
    drawHeader(m);
    drawStaples(m);
    drawMenuRoutes(m);
    var age = Math.min(1, (siteYears() * Math.min(1, m) + yearsAhead()) / 30);
    root.style.setProperty('--wsun', Math.min(1, count('h ' + page, 2, m) * .6 + age * .4).toFixed(3));
    if (wearTrack) drawSliderWear(wearTrack, m);
  }

  // Examples on the Webartrose page: drawn with fixed counts, also when the layer is off.
  function drawExamples() {
    document.querySelectorAll('.wear-demo canvas.wear-staples[data-n]').forEach(function (c) {
      staples(c, +c.getAttribute('data-n'), 'demo staples');
    });
    document.querySelectorAll('.wear-demo[data-scuffs]').forEach(function (box) {
      var svg = box.querySelector('svg.wear-scuffs');
      if (!svg) {
        svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'wear-scuffs');
        svg.setAttribute('aria-hidden', 'true');
        box.appendChild(svg);
      }
      var w = box.clientWidth, h = box.clientHeight, out = '';
      svg.setAttribute('width', w); svg.setAttribute('height', h);
      try {
        JSON.parse(box.getAttribute('data-scuffs')).forEach(function (s, i) {   // [x%, y%, 0..1]
          out += scuffs(svg, s[0] / 100 * w, s[1] / 100 * h, u, s[2], 'demo' + i);
        });
      } catch (e) {}
      svg.innerHTML = SCUFF_DEFS + out;
    });
    document.querySelectorAll('.wear-demo canvas.wear-routes[data-routes]').forEach(function (c) {
      var box = c.parentNode, br = box.getBoundingClientRect();
      var items = Array.prototype.map.call(box.querySelectorAll('.nav-item'), function (li) {
        var r = li.getBoundingClientRect(); return { x: r.left - br.left + r.width / 2 };
      });
      drawRoutes(c, items, JSON.parse(c.getAttribute('data-routes')), 1);
    });
  }

  // Dead clicks, and routes between sections.
  var INTERACTIVE = 'a, button, summary, input, select, textarea, label, option, details, dialog, [role="button"], [onclick], [tabindex]:not([tabindex="-1"]), .talk img, .wear-control, .wear-demo';
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a[href]');
    if (link) {
      var to = sectionOf(pathOf(link.getAttribute('href')));
      if (here && to && to !== here) bump('r ' + here + '>' + to, 0);
      return;
    }
    if (!e.target.closest || e.target.closest(INTERACTIVE) || !wrapper.contains(e.target)) return;
    var sel = window.getSelection && window.getSelection();
    if (sel && !sel.isCollapsed) return;                 // selecting text, not trying a door
    measure();
    var top = wrapper.getBoundingClientRect().top + scrollY;
    var cx = Math.floor((e.pageX - x0) / u), cy = Math.floor((e.pageY - top) / u);
    if (cy < 0 || Math.abs(cx) > 80) return;
    var k = cx + ',' + cy;
    if (!(k in clicksMine) && Object.keys(clicksMine).length >= 60) return;
    if ((clicksMine[k] || 0) >= 3) return;
    clicksMine[k] = (clicksMine[k] || 0) + 1;
    if (settings.on) drawMarks(factor());
  });

  // Graphite: selected text.
  var selTimer;
  document.addEventListener('selectionchange', function () {
    clearTimeout(selTimer);
    selTimer = setTimeout(function () {
      var sel = window.getSelection();
      if (!sel || sel.isCollapsed || sel.toString().trim().length < 4) return;
      things.forEach(function (t) {
        if (t.kind === 't' && (t.el.contains(sel.anchorNode) || t.el.contains(sel.focusNode))) bump(t.key, 1);
      });
    }, 700);
  });

  // The keyboard wears things too: Tab to a link is touching it.
  document.addEventListener('focusin', function (e) {
    if (!e.target.matches || !e.target.matches(':focus-visible')) return;
    things.forEach(function (t) { if (t.kind !== 't' && t.el === e.target) bump(t.key, 0); });
  });

  var depthTimer;
  window.addEventListener('scroll', function () {
    clearTimeout(depthTimer);
    depthTimer = setTimeout(measureDepth, 200);
  }, { passive: true });
  setTimeout(measureDepth, 1500);
  arrived();

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
    var marks = Object.keys(clicksMine).map(function (k) { var p = k.split(','); return ['c', +p[0], +p[1], clicksMine[k]]; });
    measureDepth();
    if (!depthSent && depthMine > 0) { marks.push(['d', 0, depthMine, 1]); depthSent = true; }
    if (!events.length && !trail.length && !marks.length) return;
    var body = JSON.stringify({ op: 'hit', page: page, events: events, trail: trail, marks: marks });
    var sent = navigator.sendBeacon && navigator.sendBeacon('/wear.php', new Blob([body], { type: 'application/json' }));
    if (!sent) fetch('/wear.php', { method: 'POST', body: body, keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(function () {});
    // What is sent now becomes part of the shared wear; start counting afresh.
    events.forEach(function (e) { var s = server[e[0]] = server[e[0]] || [0, 0, 0, 0]; s[0] += e[1]; s[1] += e[2]; s[2] += e[3]; s[3] = 0; });
    trail.forEach(function (c) { trailServer.push(c); });
    marks.forEach(function (c) { marksServer.push(c); });
    mine = {}; trailMine = {}; clicksMine = {}; depthMine = 0;
  }
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') send(); });
  window.addEventListener('pagehide', send);

  var keys = Object.keys(byKey).concat(extraKeys);
  fetch('/wear.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ op: 'read', page: page, keys: keys })
  }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
    if (!data) return;
    server = data.elements || {};
    trailServer = data.trail || [];
    marksServer = data.marks || [];
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
      '<p class="wear-more"><a href="/webartrose">What wears, and why</a></p>' +
      '<label class="wear-switch"><input type="checkbox" id="wear-on"> Show signs of use</label>' +
      '<label class="wear-label" for="wear-time">Time <output id="wear-when" for="wear-time"></output></label>' +
      '<div class="wear-range"><input type="range" id="wear-time" min="0" max="100" step="1"><span class="wear-track" aria-hidden="true"></span></div>' +
      '<div class="wear-scale" aria-hidden="true"><span>As new</span><span>Now</span><span>Years from now</span></div>' +
    '</div>';
  document.body.appendChild(control);

  var button = control.querySelector('.wear-button');
  var panel = control.querySelector('.wear-panel');
  var onBox = control.querySelector('#wear-on');
  var slider = control.querySelector('#wear-time');
  var when = control.querySelector('#wear-when');
  wearTrack = control.querySelector('.wear-track');

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
  // Where people leave the slider wears it, like the weights used most.
  slider.addEventListener('change', function () { bump('s ' + Math.round(settings.time / 5), 1); drawMore(); });
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
  window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(function () { drawTrail(); drawMore(); drawExamples(); }, 150); });
  window.addEventListener('load', function () { drawTrail(); drawMore(); drawExamples(); });
  drawExamples();
})();
