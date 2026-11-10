// Larger view for the photos on the Talks page.
//   Click (or Enter / Space on) a talk photo -> it opens full size in a dialog.
//   Close with the close button, Escape, or a click next to the photo.
// Works on the photos as the CMS saves them; nothing extra is needed there.
(function () {
  var photos = document.querySelectorAll('.talk img');
  if (!photos.length || typeof HTMLDialogElement !== 'function') return;

  var dialog = document.createElement('dialog');
  dialog.className = 'lightbox';
  dialog.setAttribute('aria-label', 'Photo');
  dialog.innerHTML =
    '<figure class="lightbox-figure">' +
      '<img class="lightbox-img" alt="">' +
      '<figcaption class="lightbox-caption"></figcaption>' +
    '</figure>' +
    '<button type="button" class="lightbox-close" aria-label="Close photo">&times;</button>';
  document.body.appendChild(dialog);

  var big = dialog.querySelector('.lightbox-img');
  var caption = dialog.querySelector('.lightbox-caption');

  function titleOf(img) {
    var talk = img.closest('.talk');
    var h2 = talk && talk.querySelector('h2');
    return h2 ? h2.textContent.trim() : '';
  }

  function open(img) {
    big.src = img.currentSrc || img.src;
    big.alt = img.alt || '';
    var title = titleOf(img);
    var subs = img.closest('.talk') ? img.closest('.talk').querySelectorAll('.subtitle') : [];
    var where = Array.prototype.map.call(subs, function (s) { return s.textContent.trim(); })
                     .filter(Boolean).join(' · ');
    caption.textContent = [title, where].filter(Boolean).join(' — ');
    dialog.showModal();
  }

  Array.prototype.forEach.call(photos, function (img) {
    var title = titleOf(img);
    img.classList.add('zoomable');
    img.setAttribute('tabindex', '0');
    img.setAttribute('role', 'button');
    img.setAttribute('aria-label', 'Show larger photo' + (title ? ': ' + title : ''));
    img.addEventListener('click', function () { open(img); });
    img.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(img); }
    });
  });

  dialog.querySelector('.lightbox-close').addEventListener('click', function () { dialog.close(); });
  // A click on the dark area around the photo closes it; a click on the photo does not.
  dialog.addEventListener('click', function (e) {
    if (e.target === dialog || e.target.classList.contains('lightbox-figure')) dialog.close();
  });
  dialog.addEventListener('close', function () { big.removeAttribute('src'); });
})();
