(() => {
  const homeUrl = new URL('index.php', window.location.href);
  const storageKey = `migas:home-view:${homeUrl.pathname}`;
  const url = new URL(window.location.href);
  const navigation = performance.getEntriesByType('navigation')[0];
  const returning = url.searchParams.get('return') === 'home';
  const restoring = (returning && !url.hash) || ['back_forward', 'reload'].includes(navigation?.type);
  let saved = null;
  try {
    saved = JSON.parse(sessionStorage.getItem(storageKey));
  } catch (_) {
    // Navigation still works when browser storage is unavailable.
  }

  if (returning) {
    url.searchParams.delete('return');
    history.replaceState(history.state, '', url.pathname + url.search + url.hash);
  }

  // This script runs in <head>, before the home hero can be painted.
  const hasPosition = restoring && Number.isFinite(saved?.y) && saved.y >= 0;
  const reveal = () => document.documentElement.removeAttribute('data-home-restoring');
  let finishRestore = reveal;
  let revealTimeout;
  if (hasPosition) {
    document.documentElement.setAttribute('data-home-restoring', '');
    // Fail open if another script or a blocked resource interrupts setup.
    revealTimeout = window.setTimeout(() => finishRestore(), 8000);
  }

  document.addEventListener('DOMContentLoaded', () => {
  const home = document.querySelector('#home');
  if (!home) {
    clearTimeout(revealTimeout);
    reveal();
    return;
  }

  const carousel = document.querySelector('[data-program-carousel]');
  if (restoring && typeof saved?.program === 'string' && carousel) {
    carousel.dataset.restoreProgram = saved.program;
  }

  let clickedProgram = null;
  const savePosition = () => {
    const sections = [...document.querySelectorAll('main > section[id], footer[id]')];
    const section = sections.filter((item) => item.getBoundingClientRect().top <= 100).pop() || home;
    const activeProgram = carousel?.querySelector('.is-active .program-slide-link');
    try {
      sessionStorage.setItem(storageKey, JSON.stringify({
        y: window.scrollY,
        section: section.id,
        offset: -section.getBoundingClientRect().top,
        program: clickedProgram || activeProgram?.getAttribute('href') || null,
      }));
    } catch (_) {
      // Keep ordinary navigation available in private/restricted browsers.
    }
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented) return;
    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== location.origin) return;
    const goesHome = destination.pathname === homeUrl.pathname || destination.pathname === new URL('.', homeUrl).pathname;
    if (goesHome) return;
    clickedProgram = link.matches('.program-slide-link') ? link.getAttribute('href') : null;
    savePosition();
  });
  window.addEventListener('pagehide', savePosition);
  window.addEventListener('pageshow', () => { clickedProgram = null; });

  if (!hasPosition) return;

  let cancelled = false;
  const restorePosition = () => {
    if (cancelled) return;
    const section = typeof saved.section === 'string' ? document.getElementById(saved.section) : null;
    const y = section && Number.isFinite(saved.offset)
      ? window.scrollY + section.getBoundingClientRect().top + saved.offset
      : saved.y;
    window.scrollTo({ top: Math.max(0, y), behavior: 'instant' });
  };
  const cancelRestore = () => {
    restorePosition();
    cancelled = true;
    observer.disconnect();
    clearTimeout(revealTimeout);
    reveal();
    ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach((type) => {
      window.removeEventListener(type, cancelRestore);
    });
    window.removeEventListener('hashchange', cancelRestore);
  };
  // Correct layout shifts while fonts/images load, but never fight user input.
  const observer = new ResizeObserver(restorePosition);
  finishRestore = cancelRestore;
  observer.observe(document.querySelector('main'));
  ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach((type) => {
    window.addEventListener(type, cancelRestore, { passive: true });
  });
  window.addEventListener('hashchange', cancelRestore);
  requestAnimationFrame(() => {
    restorePosition();
    // Wait for text layout and carousel positioning, not large hero downloads.
    document.fonts.ready.then(() => {
      requestAnimationFrame(() => requestAnimationFrame(() => {
        restorePosition();
        cancelRestore();
      }));
    });
  });
  });
})();
