document.addEventListener('DOMContentLoaded', function () {
  // Like button toggles
  document.querySelectorAll('.btn.like').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const pressed = btn.getAttribute('aria-pressed') === 'true';
      const likesEl = btn.closest('.post').querySelector('.post-likes');
      // parse number from likesEl text like "1,234 likes"
      const currentStr = likesEl ? likesEl.textContent.split(' ')[0].replace(/,/g, '') : '0';
      let n = parseInt(currentStr, 10) || 0;
      if (pressed) {
        n = Math.max(0, n - 1);
        btn.textContent = '♡';
        btn.setAttribute('aria-pressed', 'false');
      } else {
        n = n + 1;
        btn.textContent = '♥';
        btn.setAttribute('aria-pressed', 'true');
      }
      if (likesEl) likesEl.textContent = n.toLocaleString() + ' likes';
    });
  });

  // Simple story click: toggle a ring highlight
  document.querySelectorAll('.story').forEach(function (s) {
    s.addEventListener('click', function () {
      s.classList.toggle('seen');
    });
  });

  // Left nav: toggle active item
  const leftNav = document.querySelector('.left-nav');
  if (leftNav) {
    leftNav.querySelectorAll('.nav-item').forEach(function (li) {
      const btn = li.querySelector('button');
      btn.addEventListener('click', function () {
        // remove active from others
        leftNav.querySelectorAll('.nav-item').forEach(function (other) { other.classList.remove('active'); });
        li.classList.add('active');
        // small visual feedback: focus
        btn.focus();
      });
    });
  }
});
