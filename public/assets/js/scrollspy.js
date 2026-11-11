document.addEventListener('DOMContentLoaded', () => {
  const links = [...document.querySelectorAll('#site-header a[href^="#"]')];
  const sections = [...document.querySelectorAll('main section[id], footer[id]')];
  const activate = (id) => {
    links.forEach((link) => {
      const active = link.getAttribute('href') === `#${id}`;
      link.classList.toggle('is-active', active);
      if (active) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');
    });
  };

  const observer = new IntersectionObserver((entries) => {
    const visible = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
    if (visible) activate(visible.target.id);
  }, { rootMargin: '-25% 0px -60% 0px', threshold: [0.05, 0.2, 0.45] });

  sections.forEach((section) => observer.observe(section));
  activate('home');
});
