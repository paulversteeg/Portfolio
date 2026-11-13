// Notebook grid (see "Notebook grid" in screen.css).
//
// Text and spacing sit on the grid through CSS alone. Photos, videos and
// embeds in the page text have a height nobody knows in advance, so here
// the block that holds one gets just enough room below it to end on a
// line of the grid again; whatever follows then starts on a line too.
//
// Add ?grid=check to any URL to show the grid lines in red.
(function () {
  var root = document.documentElement;
  if (/[?&]grid=check\b/.test(location.search)) root.classList.add('grid-check');

  // One square of the grid, in px (0.9rem, so it follows the text size).
  function square() {
    var probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;visibility:hidden;height:var(--u)';
    document.body.appendChild(probe);
    var u = probe.getBoundingClientRect().height;
    probe.remove();
    return u;
  }

  // The block directly inside the page text (or inside a talk or a
  // publication) that holds the media: that is the one to round up.
  function blockFor(media) {
    var el = media;
    while (el.parentElement && !el.parentElement.matches('.content, .talk, .publication')) {
      el = el.parentElement;
      if (el === document.body) return null;
    }
    return el.parentElement ? el : null;
  }

  function snap() {
    var u = square();
    if (!u) return;
    var seen = [];
    document.querySelectorAll('.content :is(img, video, iframe, object, embed)').forEach(function (media) {
      if (getComputedStyle(media).float !== 'none') return;   // floats do not move the text below them
      var block = blockFor(media);
      if (!block || seen.indexOf(block) !== -1) return;
      if (getComputedStyle(block).float !== 'none') return;
      seen.push(block);
      // In a figure, round the photo itself, so its caption starts on a line.
      if (block.tagName === 'FIGURE' && media.tagName === 'IMG') {
        media.style.marginBottom = '';
        var ih = media.getBoundingClientRect().height;
        var gap = Math.ceil(ih / u - 0.01) * u - ih;
        if (gap > 0.5) media.style.marginBottom = gap + 'px';
      }
      block.style.minHeight = '';
      var h = block.getBoundingClientRect().height;
      var rounded = Math.ceil(h / u - 0.01) * u;
      if (rounded - h > 0.5) block.style.minHeight = rounded + 'px';
    });
  }

  var timer;
  function later() { clearTimeout(timer); timer = setTimeout(snap, 100); }

  snap();
  document.querySelectorAll('.content img').forEach(function (img) {
    if (!img.complete) img.addEventListener('load', later);
  });
  window.addEventListener('load', snap);
  window.addEventListener('resize', later);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(snap);
})();
