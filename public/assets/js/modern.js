document.addEventListener('DOMContentLoaded', () => {
  const headline = document.querySelector('.headline-sweep');
  if (!headline) return;

  const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      headline.classList.add('is-animated');
      observer.disconnect();
    }
  }, { threshold: 0.55 });

  observer.observe(headline);
});
