(() => {
  const body = document.body;
  const menuButton = document.querySelector('[data-menu-toggle]');
  const navigation = document.querySelector('[data-main-nav]');
  const modal = document.getElementById('redirect-modal');
  const dialog = modal?.querySelector('[role="dialog"]');
  const continueButton = modal?.querySelector('[data-modal-continue]');
  const NEEVWORK_URL = body.dataset.neevworkUrl || 'https://neevwork.com/';
  let lastFocusedElement = null;

  function setMenuState(isOpen) {
    if (isOpen) window.NeevAssistant?.close?.();
    navigation?.classList.toggle('is-open', isOpen);
    menuButton?.setAttribute('aria-expanded', String(isOpen));

    const accessibleLabel = menuButton?.querySelector('.sr-only');
    if (accessibleLabel) accessibleLabel.textContent = isOpen ? 'Tutup menu' : 'Buka menu';
  }

  function closeMenu(returnFocus = false) {
    setMenuState(false);
    if (returnFocus) menuButton?.focus();
  }

  menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    setMenuState(!isOpen);
  });

  navigation?.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', () => closeMenu());
  });

  navigation?.addEventListener('focusout', event => {
    if (!navigation.contains(event.relatedTarget) && !menuButton?.contains(event.relatedTarget)) closeMenu();
  });

  document.addEventListener('neev:assistant-state', event => {
    if (event.detail?.open) closeMenu();
  });

  function openModal(trigger) {
    if (!modal || !dialog) return;

    lastFocusedElement = trigger;
    modal.hidden = false;
    body.classList.add('modal-open');
    window.requestAnimationFrame(() => dialog.focus());
  }

  function closeModal() {
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    body.classList.remove('modal-open');
    lastFocusedElement?.focus();
  }

  document.querySelectorAll('[data-neevwork-cta]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      event.preventDefault();
      openModal(trigger);
    });
  });

  modal?.querySelectorAll('[data-modal-close]').forEach(button => {
    button.addEventListener('click', closeModal);
  });

  modal?.querySelector('[data-modal-backdrop]')?.addEventListener('click', event => {
    if (event.target === event.currentTarget) closeModal();
  });

  continueButton?.addEventListener('click', () => {
    window.location.href = NEEVWORK_URL;
  });

  function openNeevAssistant(question) {
    if (typeof window.NeevAssistant?.open === 'function') {
      window.NeevAssistant.open(question);
      return;
    }

    document.dispatchEvent(new CustomEvent('neev:assistant-open', {
      detail: { question },
    }));
  }

  document.querySelectorAll('[data-neev-assistant-question]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      openNeevAssistant(trigger.dataset.neevAssistantQuestion || '');
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      if (modal && !modal.hidden) closeModal();
      else if (navigation?.classList.contains('is-open')) closeMenu(true);
      return;
    }

    if (event.key !== 'Tab' || !modal || modal.hidden || !dialog) return;

    const focusable = [...dialog.querySelectorAll(
      'button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )];
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  document.addEventListener('click', event => {
    if (!navigation?.classList.contains('is-open')) return;
    if (navigation.contains(event.target) || menuButton?.contains(event.target)) return;
    closeMenu();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 920) closeMenu();
  });

  body.classList.add('has-home-js');

  const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');

  function setupHeroSlideshow() {
    const hero = document.querySelector('[data-hero-slideshow]');
    if (!hero) return;

    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    const dots = [...hero.querySelectorAll('[data-hero-dot]')];
    const controls = hero.querySelector('[data-hero-controls]');
    const pauseButton = hero.querySelector('[data-hero-pause]');
    if (slides.length < 2 || slides.length !== dots.length || !controls || !pauseButton) return;

    let activeIndex = 0;
    let timer = null;
    let paused = false;
    let hovered = false;
    let focused = false;

    function showSlide(index) {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.classList.toggle('is-active', i === activeIndex));
      dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === activeIndex)));
    }

    function updateRotation() {
      window.clearInterval(timer);
      timer = null;
      pauseButton.hidden = motionPreference.matches;
      pauseButton.textContent = paused ? 'Putar' : 'Jeda';
      pauseButton.setAttribute('aria-pressed', String(paused));
      pauseButton.setAttribute('aria-label', paused ? 'Lanjutkan pergantian foto otomatis' : 'Jeda pergantian foto otomatis');
      if (motionPreference.matches || paused || hovered || focused || document.hidden) return;
      timer = window.setInterval(() => showSlide(activeIndex + 1), 5000);
    }

    function selectSlide(index) {
      showSlide(index);
      updateRotation();
    }

    hero.querySelector('[data-hero-previous]')?.addEventListener('click', () => selectSlide(activeIndex - 1));
    hero.querySelector('[data-hero-next]')?.addEventListener('click', () => selectSlide(activeIndex + 1));
    dots.forEach((dot, index) => dot.addEventListener('click', () => selectSlide(index)));
    pauseButton.addEventListener('click', () => {
      paused = !paused;
      updateRotation();
    });
    hero.addEventListener('mouseenter', () => { hovered = true; updateRotation(); });
    hero.addEventListener('mouseleave', () => { hovered = false; updateRotation(); });
    hero.addEventListener('focusin', () => { focused = true; updateRotation(); });
    hero.addEventListener('focusout', event => {
      focused = hero.contains(event.relatedTarget);
      updateRotation();
    });
    document.addEventListener('visibilitychange', updateRotation);
    motionPreference.addEventListener('change', updateRotation);

    showSlide(0);
    controls.hidden = false;
    updateRotation();
  }

  setupHeroSlideshow();

  // Content is always visible. Intersection only starts an entrance animation,
  // so unsupported APIs, disabled JS, or a failed observer cannot hide a section.
  if (motionPreference.matches || !('IntersectionObserver' in window)) return;

  const revealed = new WeakSet();
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting || revealed.has(entry.target)) return;

      revealed.add(entry.target);
      observer.unobserve(entry.target);
      if (!motionPreference.matches) entry.target.classList.add('is-revealed');
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });

  document.querySelectorAll('[data-reveal-group]').forEach(group => {
    group.querySelectorAll('[data-reveal]').forEach((item, index) => {
      item.style.setProperty('--reveal-delay', `${(index % 4) * 40}ms`);
    });
  });

  document.querySelectorAll('[data-reveal]').forEach(item => observer.observe(item));

  motionPreference.addEventListener('change', event => {
    if (event.matches) observer.disconnect();
  });
})();
