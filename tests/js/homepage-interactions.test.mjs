import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const homeSource = readFileSync(new URL('../../public/assets/landing/js/home.js', import.meta.url), 'utf8');
const shellSource = readFileSync(new URL('../../public/assets/landing/js/chatbot-widget-shell.js', import.meta.url), 'utf8');

// These fixtures exercise event/state contracts without a browser or API calls.
function createPage({ reduced = false, mobile = false, observerSupported = true, slideCount = 2, width = mobile ? 390 : 1440, coarse = mobile, visualViewport = true } = {}) {
  let document;
  function element(initialClasses = []) {
    const classes = new Set(initialClasses);
    const attributes = new Map();
    const listeners = new Map();
    const selectors = new Map();
    const properties = new Map();
    return {
      dataset: {}, selectors, listeners, attributes, value: '', inert: false,
      style: {
        setProperty: (name, value) => properties.set(name, value),
        getPropertyValue: name => properties.get(name),
        removeProperty(name) { properties.delete(name); delete this[name]; },
      },
      classList: {
        add: name => classes.add(name),
        remove: name => classes.delete(name),
        contains: name => classes.has(name),
        toggle(name, force = !classes.has(name)) {
          if (force) classes.add(name); else classes.delete(name);
          return force;
        },
      },
      setAttribute: (name, value) => attributes.set(name, value),
      getAttribute: name => attributes.get(name),
      querySelector: selector => selectors.get(selector) ?? null,
      querySelectorAll: selector => selectors.get(selector) ?? [],
      addEventListener(type, listener) {
        listeners.set(type, [...(listeners.get(type) ?? []), listener]);
      },
      dispatchEvent(event) {
        for (const listener of listeners.get(event.type) ?? []) listener(event);
      },
      emit(type, detail = {}) {
        this.dispatchEvent({ type, target: this, currentTarget: this, preventDefault() {}, ...detail });
      },
      focus() { document.activeElement = this; },
      contains(target) { return target === this; },
      closest() { return null; },
      getBoundingClientRect: () => ({ left: 20, top: 20, width: 390, height: 540 }),
    };
  }

  document = element();
  document.body = element();
  document.body.dataset.neevworkUrl = 'https://neevwork.com/';
  document.activeElement = null;
  document.hidden = false;

  const menu = element();
  menu.setAttribute('aria-expanded', 'false');
  const navigation = element();
  const navLink = element();
  navigation.selectors.set('a[href^="#"]', [navLink]);
  navigation.contains = target => target === navigation || target === navLink;
  const modal = element();
  modal.hidden = true;
  const dialog = element();
  const continueButton = element();
  const cancelButton = element();
  const redirectTrigger = element();
  const serviceTrigger = element();
  serviceTrigger.dataset.neevAssistantQuestion = 'Saya ingin konsultasi pemasangan CCTV.';
  const revealTarget = element();
  const revealGroup = element();
  revealGroup.selectors.set('[data-reveal]', [revealTarget]);

  modal.selectors.set('[role="dialog"]', dialog);
  modal.selectors.set('[data-modal-continue]', continueButton);
  modal.selectors.set('[data-modal-close]', [cancelButton]);
  const selectors = document.selectors;
  selectors.set('[data-menu-toggle]', menu);
  selectors.set('[data-main-nav]', navigation);
  selectors.set('[data-neevwork-cta]', [redirectTrigger]);
  selectors.set('[data-neev-assistant-question]', [serviceTrigger]);
  selectors.set('[data-reveal-group]', [revealGroup]);
  selectors.set('[data-reveal]', [revealTarget]);

  const hero = element();
  const slides = Array.from({ length: slideCount }, () => element());
  const dots = Array.from({ length: slideCount }, () => element());
  const controls = element();
  controls.hidden = true;
  const previous = element();
  const next = element();
  const pause = element();
  hero.selectors.set('[data-hero-slide]', slides);
  hero.selectors.set('[data-hero-dot]', dots);
  hero.selectors.set('[data-hero-controls]', controls);
  hero.selectors.set('[data-hero-previous]', previous);
  hero.selectors.set('[data-hero-next]', next);
  hero.selectors.set('[data-hero-pause]', pause);
  hero.contains = target => [hero, controls, previous, next, pause, ...dots].includes(target);
  selectors.set('[data-hero-slideshow]', hero);

  const shell = element(['cbot-shell--hidden']);
  const launcher = element();
  const minimize = element();
  const resize = element();
  const header = element();
  const input = element();
  shell.selectors.set('#cbot .cbot__header', header);
  shell.selectors.set('#cbot-input', input);
  const ids = { 'redirect-modal': modal, 'cbot-shell': shell, 'cbot-launcher': launcher, 'cbot-minimize': minimize, 'cbot-resize': resize };
  document.getElementById = id => ids[id] ?? null;

  const motionPreference = element();
  motionPreference.matches = reduced;
  const mobileQuery = element();
  mobileQuery.matches = mobile || width <= 767;
  const touchQuery = element();
  touchQuery.matches = coarse;
  const window = element();
  window.matchMedia = query => query.includes('reduced-motion') ? motionPreference : query.includes('pointer: coarse') ? touchQuery : mobileQuery;
  window.requestAnimationFrame = callback => { callback(); return 1; };
  window.cancelAnimationFrame = () => {};
  window.location = { href: '' };
  window.innerWidth = width;
  window.innerHeight = 900;
  if (visualViewport) {
    window.visualViewport = element();
    window.visualViewport.height = 900;
    window.visualViewport.offsetTop = 0;
  }
  const timers = new Map();
  let nextTimer = 1;
  window.setInterval = (callback, delay) => {
    const id = nextTimer++;
    timers.set(id, { callback, delay });
    return id;
  };
  window.clearInterval = id => timers.delete(id);

  const observers = [];
  class IntersectionObserver {
    constructor(callback) {
      this.callback = callback;
      this.observed = new Set();
      this.unobserved = [];
      this.disconnected = false;
      observers.push(this);
    }
    observe(target) { this.observed.add(target); }
    unobserve(target) { this.observed.delete(target); this.unobserved.push(target); }
    disconnect() { this.disconnected = true; }
  }
  if (observerSupported) window.IntersectionObserver = IntersectionObserver;
  class Event { constructor(type, options = {}) { this.type = type; Object.assign(this, options); } }
  class CustomEvent extends Event {}
  const context = { window, document, IntersectionObserver, Event, CustomEvent, WeakSet };

  runInNewContext(homeSource, context);
  runInNewContext(shellSource, context);
  return { document, window, motionPreference, mobileQuery, touchQuery, observers, revealTarget, menu, navigation, navLink, modal, redirectTrigger, continueButton, cancelButton, serviceTrigger, shell, launcher, minimize, resize, header, input, hero, slides, dots, controls, previous, next, pause, timers };
}

test('navigation links close the mobile menu and Escape restores toggle focus', () => {
  const page = createPage({ mobile: true });
  page.menu.emit('click');
  page.navLink.emit('click');
  assert.equal(page.navigation.classList.contains('is-open'), false);
  page.menu.emit('click');
  page.document.emit('keydown', { key: 'Escape' });
  assert.equal(page.menu.getAttribute('aria-expanded'), 'false');
  assert.equal(page.document.activeElement, page.menu);
});

test('menu and chatbot do not overlap; toggling preserves the chat draft', () => {
  const page = createPage({ mobile: true });
  page.serviceTrigger.emit('click');
  const draft = page.input.value;
  page.menu.emit('click');
  assert.equal(page.shell.inert, true);
  assert.equal(page.navigation.classList.contains('is-open'), true);
  page.launcher.emit('click');
  assert.equal(page.navigation.classList.contains('is-open'), false);
  assert.equal(page.shell.inert, false);
  assert.equal(page.input.value, draft);
});

test('mobile shell follows keyboard resize and visual viewport scrolling', () => {
  const page = createPage({ mobile: true });
  page.launcher.emit('click');
  page.window.visualViewport.height = 360;
  page.window.visualViewport.offsetTop = 42;
  page.window.visualViewport.emit('resize');
  assert.equal(page.shell.style.getPropertyValue('--cbot-viewport-height'), '360px');
  assert.equal(page.shell.style.getPropertyValue('--cbot-viewport-top'), '42px');
  assert.equal(page.launcher.style.getPropertyValue('--cbot-keyboard-offset'), '498px');
  page.window.visualViewport.offsetTop = 60;
  page.window.visualViewport.emit('scroll');
  assert.equal(page.shell.style.getPropertyValue('--cbot-viewport-top'), '60px');
  page.window.visualViewport.height = 900;
  page.window.visualViewport.offsetTop = 0;
  page.window.visualViewport.emit('resize');
  assert.equal(page.launcher.style.getPropertyValue('--cbot-keyboard-offset'), '0px');
});

test('shell remains usable when VisualViewport is unavailable', () => {
  const page = createPage({ mobile: true, visualViewport: false });
  page.launcher.emit('click');
  assert.equal(page.shell.inert, false);
  assert.equal(page.shell.style.getPropertyValue('--cbot-viewport-height'), '900px');
  page.window.innerHeight = 420;
  page.window.emit('resize');
  assert.equal(page.shell.style.getPropertyValue('--cbot-viewport-height'), '420px');
});

test('phone and coarse-pointer tablet disable drag, resize, and forced keyboard focus', () => {
  for (const options of [{ mobile: true }, { width: 768, coarse: true }]) {
    const page = createPage(options);
    page.launcher.emit('click');
    assert.equal(page.resize.hidden, true);
    assert.equal(page.resize.disabled, true);
    assert.notEqual(page.document.activeElement, page.input);
    page.resize.emit('click');
    assert.equal(page.shell.classList.contains('cbot-shell--wide'), false);
    page.header.emit('pointerdown', { pointerType: 'touch', button: 0, pointerId: 1, clientX: 40, clientY: 40 });
    page.header.emit('pointermove', { pointerId: 1, clientX: 200, clientY: 200 });
    assert.equal(page.shell.style.left, undefined);
  }
});

test('desktop mouse dragging and resizing still work; switching to mobile clears position', () => {
  const page = createPage();
  page.launcher.emit('click');
  assert.equal(page.resize.hidden, false);
  page.resize.emit('click');
  assert.equal(page.shell.classList.contains('cbot-shell--wide'), true);
  page.header.emit('pointerdown', { pointerType: 'mouse', button: 0, pointerId: 1, clientX: 40, clientY: 40 });
  page.header.emit('pointermove', { pointerId: 1, clientX: 200, clientY: 200 });
  assert.equal(page.shell.style.left, '180px');
  page.mobileQuery.matches = true;
  page.mobileQuery.emit('change');
  assert.equal(page.shell.style.left, undefined);
  assert.equal(page.document.body.classList.contains('cbot-dragging'), false);
  assert.equal(page.resize.hidden, true);
});

test('switching to a coarse pointer resets a previous desktop drag position', () => {
  const page = createPage();
  page.launcher.emit('click');
  page.shell.style.left = '800px';
  page.shell.style.top = '500px';
  page.touchQuery.matches = true;
  page.touchQuery.emit('change');
  assert.equal(page.shell.style.left, undefined);
  assert.equal(page.shell.style.top, undefined);
  assert.equal(page.resize.disabled, true);
});

test('hero starts on the first image and wraps every five seconds', () => {
  const page = createPage();
  assert.equal(page.controls.hidden, false);
  assert.equal(page.slides[0].classList.contains('is-active'), true);
  assert.equal(page.slides[1].classList.contains('is-active'), false);
  assert.equal(page.dots[0].getAttribute('aria-current'), 'true');
  assert.equal(page.timers.size, 1);
  const timer = [...page.timers.values()][0];
  assert.equal(timer.delay, 5000);
  timer.callback();
  assert.equal(page.slides[1].classList.contains('is-active'), true);
  assert.equal(page.dots[0].getAttribute('aria-current'), 'false');
  timer.callback();
  assert.equal(page.slides[0].classList.contains('is-active'), true);
});

test('hero arrows and dots select images without accumulating timers', () => {
  const page = createPage();
  page.previous.emit('click');
  assert.equal(page.slides[1].classList.contains('is-active'), true);
  page.next.emit('click');
  assert.equal(page.slides[0].classList.contains('is-active'), true);
  page.dots[1].emit('click');
  assert.equal(page.slides[1].classList.contains('is-active'), true);
  assert.equal(page.dots[1].getAttribute('aria-current'), 'true');
  assert.equal(page.timers.size, 1);
});

test('hero rotation pauses on hover, keyboard focus, and hidden tabs', () => {
  const page = createPage();
  page.hero.emit('mouseenter');
  assert.equal(page.timers.size, 0);
  page.hero.emit('mouseleave');
  assert.equal(page.timers.size, 1);
  page.hero.emit('focusin');
  assert.equal(page.timers.size, 0);
  page.hero.emit('focusout', { relatedTarget: page.next });
  assert.equal(page.timers.size, 0);
  page.hero.emit('focusout', { relatedTarget: null });
  assert.equal(page.timers.size, 1);
  page.document.hidden = true;
  page.document.emit('visibilitychange');
  assert.equal(page.timers.size, 0);
  page.document.hidden = false;
  page.document.emit('visibilitychange');
  assert.equal(page.timers.size, 1);
});

test('hero provides an explicit persistent pause and resume control', () => {
  const page = createPage();
  page.pause.emit('click');
  assert.equal(page.pause.getAttribute('aria-pressed'), 'true');
  assert.equal(page.pause.textContent, 'Putar');
  assert.equal(page.timers.size, 0);
  page.hero.emit('mouseleave');
  page.next.emit('click');
  assert.equal(page.timers.size, 0);
  page.pause.emit('click');
  assert.equal(page.pause.getAttribute('aria-pressed'), 'false');
  assert.equal(page.timers.size, 1);
});

test('reduced motion disables autoplay but preserves manual slide controls', () => {
  const page = createPage({ reduced: true });
  assert.equal(page.timers.size, 0);
  assert.equal(page.pause.hidden, true);
  page.next.emit('click');
  assert.equal(page.slides[1].classList.contains('is-active'), true);
  assert.equal(page.timers.size, 0);
  page.motionPreference.matches = false;
  page.motionPreference.emit('change', { matches: false });
  assert.equal(page.pause.hidden, false);
  assert.equal(page.timers.size, 1);
  page.motionPreference.matches = true;
  page.motionPreference.emit('change', { matches: true });
  assert.equal(page.timers.size, 0);
});

test('single-image fallback leaves controls hidden and core actions functional', () => {
  const page = createPage({ slideCount: 1 });
  assert.equal(page.controls.hidden, true);
  assert.equal(page.timers.size, 0);
  page.menu.emit('click');
  assert.equal(page.navigation.classList.contains('is-open'), true);
  page.launcher.emit('click');
  assert.equal(page.shell.inert, false);
});

test('hero rotation remains available without IntersectionObserver', () => {
  const page = createPage({ observerSupported: false });
  assert.equal(page.timers.size, 1);
  page.next.emit('click');
  assert.equal(page.slides[1].classList.contains('is-active'), true);
});

test('scroll reveal observes once and never hides pending content', () => {
  const page = createPage();
  const observer = page.observers[0];
  assert.equal(observer.observed.has(page.revealTarget), true);
  assert.equal(page.revealTarget.attributes.has('hidden'), false);
  assert.equal(page.revealTarget.classList.contains('is-revealed'), false);
  observer.callback([{ target: page.revealTarget, isIntersecting: false }]);
  assert.equal(page.revealTarget.classList.contains('is-revealed'), false);
  observer.callback([{ target: page.revealTarget, isIntersecting: true }]);
  observer.callback([{ target: page.revealTarget, isIntersecting: true }]);
  assert.equal(page.revealTarget.classList.contains('is-revealed'), true);
  assert.equal(observer.unobserved.length, 1);
});

test('reduced motion skips reveal setup while navigation and prefill work', () => {
  const page = createPage({ reduced: true });
  assert.equal(page.observers.length, 0);
  page.menu.emit('click');
  assert.equal(page.navigation.classList.contains('is-open'), true);
  page.serviceTrigger.emit('click');
  assert.equal(page.input.value, 'Saya ingin konsultasi pemasangan CCTV.');
  assert.equal(page.shell.inert, false);
});

test('without IntersectionObserver, content and core actions remain available', () => {
  const page = createPage({ observerSupported: false });
  assert.equal(page.observers.length, 0);
  assert.equal(page.revealTarget.classList.contains('is-revealed'), false);
  page.serviceTrigger.emit('click');
  assert.equal(page.launcher.getAttribute('aria-expanded'), 'true');
});

test('changing to reduced motion disconnects pending reveals', () => {
  const page = createPage();
  page.motionPreference.matches = true;
  page.motionPreference.emit('change', { matches: true });
  assert.equal(page.observers[0].disconnected, true);
  page.observers[0].callback([{ target: page.revealTarget, isIntersecting: true }]);
  assert.equal(page.revealTarget.classList.contains('is-revealed'), false);
});

test('closed widget is inert; rapid open/close retains draft and returns focus', () => {
  const page = createPage();
  assert.equal(page.shell.inert, true);
  let inputEvents = 0;
  page.input.addEventListener('input', () => inputEvents++);
  page.serviceTrigger.emit('click');
  assert.equal(inputEvents, 1);
  assert.equal(page.shell.inert, false);
  assert.equal(page.shell.getAttribute('aria-hidden'), 'false');
  assert.equal(page.document.activeElement, page.input);
  page.minimize.emit('click');
  assert.equal(page.shell.inert, true);
  assert.equal(page.document.activeElement, page.launcher);
  page.launcher.emit('click');
  assert.equal(page.input.value, 'Saya ingin konsultasi pemasangan CCTV.');
  page.document.emit('keydown', { key: 'Escape' });
  assert.equal(page.shell.getAttribute('aria-hidden'), 'true');
  assert.equal(page.shell.inert, true);
});

test('mobile prefill opens the widget without forcing the software keyboard', () => {
  const page = createPage({ mobile: true });
  page.serviceTrigger.emit('click');
  assert.equal(page.shell.inert, false);
  assert.equal(page.input.value, 'Saya ingin konsultasi pemasangan CCTV.');
  assert.notEqual(page.document.activeElement, page.input);
});

test('official redirect still requires the existing continue action', () => {
  const page = createPage();
  page.redirectTrigger.emit('click');
  assert.equal(page.modal.hidden, false);
  assert.equal(page.window.location.href, '');
  page.cancelButton.emit('click');
  assert.equal(page.modal.hidden, true);
  assert.equal(page.document.activeElement, page.redirectTrigger);
  page.redirectTrigger.emit('click');
  page.continueButton.emit('click');
  assert.equal(page.window.location.href, 'https://neevwork.com/');
});
