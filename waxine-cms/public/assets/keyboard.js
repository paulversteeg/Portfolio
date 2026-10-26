// Arrow-key navigation between articles.
//   Left arrow  -> previous (older) article
//   Right arrow -> next (newer) article
// Uses the rel="prev"/rel="next" links already rendered on post pages.
(function () {
  document.addEventListener('keydown', function (e) {
    // Don't hijack shortcuts or typing in fields.
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    var el = e.target || {};
    var tag = el.tagName || '';
    if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag) || el.isContentEditable) return;

    var go = function (sel) {
      var a = document.querySelector(sel);
      if (a && a.href) { window.location.href = a.href; }
    };
    if (e.key === 'ArrowLeft'  || e.keyCode === 37) go('a[rel="prev"]');
    if (e.key === 'ArrowRight' || e.keyCode === 39) go('a[rel="next"]');
  });
})();
