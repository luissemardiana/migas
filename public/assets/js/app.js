document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide?.createIcons) window.lucide.createIcons();

  const header = document.querySelector('#site-header');
  const menuButton = document.querySelector('#menu-toggle');
  const mobileMenu = document.querySelector('#mobile-menu');

  const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 40);
  updateHeader();
  addEventListener('scroll', updateHeader, { passive: true });

  menuButton.addEventListener('click', () => {
    const isOpen = mobileMenu.classList.toggle('hidden') === false;
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.innerHTML = isOpen
      ? '<i data-lucide="x" class="h-6 w-6"></i>'
      : '<i data-lucide="menu" class="h-6 w-6"></i>';
    if (window.lucide?.createIcons) window.lucide.createIcons();
  });

  mobileMenu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    mobileMenu.classList.add('hidden');
    menuButton.setAttribute('aria-expanded', 'false');
  }));

  const heroAnimation = document.querySelector('#home .hero-animation');
  if (heroAnimation) {
    const animationImage = heroAnimation.querySelector('img');
    const showPoster = () => {
      heroAnimation.classList.add('is-unavailable');
    };
    animationImage.addEventListener('error', showPoster);
    if (animationImage.complete && !animationImage.naturalWidth) showPoster();
  }

  document.querySelectorAll('[data-program-carousel]').forEach((carousel) => {
    const stage = carousel.querySelector('[data-program-stage]');
    const slides = Array.from(carousel.querySelectorAll('[data-program-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-program-dot]'));
    const status = carousel.querySelector('[data-program-status]');
    const count = slides.length;
    if (!stage || !count) return;
    const restoredIndex = slides.findIndex((slide) =>
      slide.querySelector('.program-slide-link')?.getAttribute('href') === carousel.dataset.restoreProgram
    );
    const initialIndex = restoredIndex >= 0 ? restoredIndex : Math.min(2, count - 1);
    let activeIndex = -1;
    let scrollFrame = 0;
    let edgeAdvanceLocked = false;

    const updateActive = (index) => {
      if (index < 0 || index >= count || index === activeIndex) return;
      activeIndex = index;
      slides.forEach((slide, slideIndex) => {
        const distance = Math.abs(slideIndex - activeIndex);
        slide.classList.toggle('is-active', distance === 0);
        slide.classList.toggle('is-near', distance === 1);
        slide.classList.toggle('is-far', distance > 1);
      });
      dots.forEach((dot, dotIndex) => {
        const isActive = dotIndex === activeIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-pressed', String(isActive));
      });
      if (status) status.textContent = `Program ${activeIndex + 1} dari ${count}: ${slides[activeIndex].dataset.title}`;
    };

    const findCenteredSlide = () => {
      const center = stage.getBoundingClientRect().left + stage.clientWidth / 2;
      let closestIndex = 0;
      let closestDistance = Infinity;
      slides.forEach((slide, index) => {
        const rect = slide.getBoundingClientRect();
        const distance = Math.abs(rect.left + rect.width / 2 - center);
        if (distance < closestDistance) {
          closestDistance = distance;
          closestIndex = index;
        }
      });
      updateActive(closestIndex);
    };

    slides.forEach((slide, index) => {
      slide.addEventListener('pointerenter', (event) => {
        if (event.pointerType !== 'mouse') return;
        updateActive(index);
        if (edgeAdvanceLocked) return;

        const stageRect = stage.getBoundingClientRect();
        const rightmostVisibleIndex = slides.reduce((rightmost, candidate, candidateIndex) => {
          const rect = candidate.getBoundingClientRect();
          return rect.right > stageRect.left + 1 && rect.left < stageRect.right - 1
            ? candidateIndex
            : rightmost;
        }, -1);

        if (index !== rightmostVisibleIndex || index >= count - 1) return;
        edgeAdvanceLocked = true;
        const nextSlide = slides[index + 1];
        const nextRect = nextSlide.getBoundingClientRect();
        const offset = nextRect.left + nextRect.width / 2 - (stageRect.left + stage.clientWidth / 2);
        stage.scrollTo({ left: stage.scrollLeft + offset, behavior: 'smooth' });
        updateActive(index + 1);
      });
    });

    carousel.addEventListener('pointerleave', (event) => {
      if (event.pointerType === 'mouse') edgeAdvanceLocked = false;
    });

    stage.addEventListener('scroll', () => {
      if (scrollFrame) return;
      scrollFrame = window.requestAnimationFrame(() => {
        scrollFrame = 0;
        findCenteredSlide();
      });
    }, { passive: true });

    dots.forEach((dot) => dot.addEventListener('click', () => {
      const index = Number(dot.dataset.programDot);
      const slide = slides[index];
      if (!slide) return;
      const stageRect = stage.getBoundingClientRect();
      const slideRect = slide.getBoundingClientRect();
      const offset = slideRect.left + slideRect.width / 2 - (stageRect.left + stage.clientWidth / 2);
      stage.scrollTo({ left: stage.scrollLeft + offset, behavior: 'smooth' });
    }));

    window.requestAnimationFrame(() => {
      const stageRect = stage.getBoundingClientRect();
      const initialRect = slides[initialIndex].getBoundingClientRect();
      const offset = initialRect.left + initialRect.width / 2 - (stageRect.left + stage.clientWidth / 2);
      stage.style.scrollBehavior = 'auto';
      stage.scrollLeft += offset;
      stage.style.scrollBehavior = '';
      updateActive(initialIndex);
    });
  });

  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  }), { threshold: 0.12 });

  document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));
});
