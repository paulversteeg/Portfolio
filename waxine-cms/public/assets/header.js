// Menu that gets out of the way.
//   Scrolling down: the menu scrolls away with the page.
//   Scrolling up, even a little: the menu slides back in and stays at the top.
// The menu is always shown at the top of the page and while it has keyboard focus.
(function () {
  var nav = document.querySelector('.nav');
  if (!nav) return;

  // On staging the warning bar is also stuck to the top: keep the menu below it.
  var banner = document.querySelector('body > div[role="note"]');
  if (banner) nav.style.top = banner.offsetHeight + 'px';

  var lastY = window.scrollY;
  var THRESHOLD = 6; // px of scrolling before the menu reacts, to ignore jitter

  function update() {
    var y = window.scrollY;
    var delta = y - lastY;
    if (y <= nav.offsetHeight) {
      nav.classList.remove('nav--hidden');      // near the top: always visible
    } else if (nav.contains(document.activeElement)) {
      nav.classList.remove('nav--hidden');      // keyboard users keep the menu
    } else if (delta > THRESHOLD) {
      nav.classList.add('nav--hidden');         // scrolling down
    } else if (delta < -THRESHOLD) {
      nav.classList.remove('nav--hidden');      // scrolling up
    }
    if (Math.abs(delta) > THRESHOLD || y <= nav.offsetHeight) lastY = y;
  }

  // Only a class toggle per event, so no need to throttle.
  window.addEventListener('scroll', update, { passive: true });

  nav.addEventListener('focusin', function () { nav.classList.remove('nav--hidden'); });
})();
