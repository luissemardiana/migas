document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('site-header');
  if (!header) return;
  const sections = [...header.querySelectorAll('.nav-section')];
  const wide = matchMedia('(min-width: 1280px)');
  let closeTimer;
  const close = (restoreFocus = false) => {
    clearTimeout(closeTimer);
    sections.forEach(section => {
      const button = section.querySelector('.nav-trigger');
      if (restoreFocus && button.getAttribute('aria-expanded') === 'true') button.focus();
      button.setAttribute('aria-expanded', 'false');
      section.querySelector('.nav-panel').hidden = true;
    });
    header.classList.remove('nav-open');
  };
  const open = section => {
    close();
    section.querySelector('.nav-trigger').setAttribute('aria-expanded', 'true');
    section.querySelector('.nav-panel').hidden = false;
    header.classList.add('nav-open');
  };
  sections.forEach(section => {
    const trigger = section.querySelector('.nav-trigger');
    section.addEventListener('pointerenter', event => {
      clearTimeout(closeTimer);
      if (wide.matches && event.pointerType === 'mouse') open(section);
    });
    section.addEventListener('pointerleave', event => {
      if (event.pointerType === 'mouse' && !section.contains(document.activeElement)) closeTimer = setTimeout(() => close(), 160);
    });
    trigger.addEventListener('click', () => trigger.getAttribute('aria-expanded') === 'true' ? close() : open(section));
    trigger.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') { event.preventDefault(); open(section); section.querySelector('.nav-panel a')?.focus(); }
    });
    section.addEventListener('focusout', event => { if (!section.contains(event.relatedTarget)) closeTimer = setTimeout(() => close(), 120); });
    section.addEventListener('focusin', () => clearTimeout(closeTimer));
    section.querySelectorAll('.nav-panel a').forEach(link => link.addEventListener('click', () => close()));
  });
  document.addEventListener('pointerdown', event => { if (!header.contains(event.target)) close(); });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    close(true);
    const menu = header.querySelector('#mobile-menu');
    const toggle = header.querySelector('#menu-toggle');
    if (!menu?.classList.contains('hidden')) { toggle?.click(); toggle?.focus(); }
  });
  wide.addEventListener('change', () => {
    close();
    header.querySelector('#mobile-menu')?.classList.add('hidden');
    header.querySelector('#menu-toggle')?.setAttribute('aria-expanded', 'false');
  });
  const mobileGroups = [...header.querySelectorAll('.reliability-mobile-nav details')];
  mobileGroups.forEach(group => group.addEventListener('toggle', () => {
    if (group.open) mobileGroups.filter(other => other !== group).forEach(other => { other.open = false; });
  }));
});
