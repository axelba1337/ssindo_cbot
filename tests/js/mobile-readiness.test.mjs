import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import postcss from 'postcss';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const home = postcss.parse(read('public/assets/landing/css/home.css'));
const widget = postcss.parse(read('public/assets/landing/css/chatbot-widget.css'));
const admin = postcss.parse(read('public/assets/landing/css/admin.css'));

// Source-level cascade checks, not a substitute for a rendering engine/device.
function styles(root, selector, width, coarse = false) {
  const declarations = {};
  root.walkRules(rule => {
    if (!rule.selectors.includes(selector)) return;
    for (let parent = rule.parent; parent; parent = parent.parent) {
      if (parent.type !== 'atrule' || parent.name !== 'media') continue;
      const max = parent.params.match(/max-width:\s*(\d+)px/);
      const min = parent.params.match(/min-width:\s*(\d+)px/);
      if ((max && width > Number(max[1])) || (min && width < Number(min[1]))) return;
      if (parent.params.includes('pointer: coarse') && !coarse) return;
      if (parent.params.includes('prefers-reduced-motion: reduce')) return;
    }
    rule.walkDecls(declaration => { declarations[declaration.prop] = declaration.value; });
  });
  return declarations;
}

for (const width of [320, 360, 390, 430, 768, 1366]) {
  test(`${width}px: homepage grids, widget bounds and input sizing`, () => {
    const grid = styles(home, '.product-grid', width);
    assert.equal(grid['grid-template-columns'], width <= 600 ? 'minmax(0, 1fr)' : width <= 920 ? 'repeat(2, minmax(0, 1fr))' : 'repeat(4, minmax(0, 1fr))');
    const panel = styles(widget, '.cbot-shell', width);
    assert.equal(panel['min-width'], '0');
    assert.equal(panel['min-height'], '0');
    assert.match(panel.height, /--cbot-viewport-height/);
    const availableWidth = width <= 767 ? width - 16 : Math.min(390, width - 40);
    assert(availableWidth <= width);
    if (width <= 767) {
      assert.match(panel.top, /--cbot-viewport-top/);
      assert.match(panel.left, /safe-area-inset-left/);
      assert.match(panel.height, /safe-area-inset-top/);
      assert.equal(styles(widget, '.cbot__input input', width)['font-size'], '16px');
      assert.equal(styles(widget, '.cbot-shell-toggle', width).width, '44px');
      assert.equal(styles(widget, '.quick__btn', width)['min-height'], '44px');
      assert.equal(styles(home, '.footer-main', width)['grid-template-columns'], 'minmax(0, 1fr)');
      assert.match(styles(home, '.main-nav', width)['max-height'], /100dvh - 160px/);
    } else {
      assert.equal(panel.width, 'min(390px, calc(100vw - 40px))');
      assert.equal(styles(home, '.footer-main', width)['grid-template-columns'], 'minmax(0, 1.4fr) minmax(0, .7fr) minmax(0, 1fr)');
    }
  });
}

test('mobile hero preserves whole photos, touch controls and reduced-motion fallback', () => {
  assert.equal(styles(home, '.hero__slide', 390)['object-fit'], 'contain');
  assert.equal(styles(home, '.hero__slide', 1366)['object-fit'], 'cover');
  assert.equal(styles(home, '.hero__arrow', 320)['min-width'], '44px');
  assert.equal(styles(home, '.hero__dot', 320).width, '44px');
  assert.equal(styles(home, '.hero__controls[hidden]', 320).display, 'none');
  assert.equal(styles(widget, '.quick[hidden]', 320).display, 'none');
  let reducedTransitions = false;
  home.walkAtRules('media', media => {
    if (media.params === '(prefers-reduced-motion: reduce)') {
      media.walkDecls('transition', declaration => { reducedTransitions ||= declaration.value === 'none' && declaration.important; });
    }
  });
  assert(reducedTransitions);
});

test('admin tables scroll locally while mobile forms and navigation wrap', () => {
  assert.equal(styles(admin, '.table-scroll', 320)['overflow-x'], 'auto');
  assert.equal(styles(admin, '.table-scroll .table', 320)['min-width'], '640px');
  assert.equal(styles(admin, '.admin__nav', 320)['flex-wrap'], 'wrap');
  assert.equal(styles(admin, '.form-grid', 320)['grid-template-columns'], 'minmax(0,1fr)');
  assert.equal(styles(admin, '.form-grid', 1366)['grid-template-columns'], '1fr 1fr');
  for (const path of ['resources/views/admin/faq.blade.php', 'resources/views/admin/unanswered.blade.php']) {
    assert.match(read(path), /class="table-scroll" role="region"[^>]+tabindex="0"/);
  }
});

test('no page scaling, fixed development endpoints or replacement chat API', () => {
  for (const root of [home, widget, admin]) {
    root.walkDecls('zoom', () => assert.fail('Do not scale the page with zoom'));
    root.walkRules(rule => {
      if (rule.selectors.some(selector => ['html', 'body', 'body.home-page', 'body.admin', '.home-page'].includes(selector))) {
        rule.walkDecls('transform', declaration => assert.doesNotMatch(declaration.value, /scale/));
      }
    });
  }
  const chat = read('public/assets/landing/js/chatbot-widget.js');
  assert.match(chat, /fetch\('\/api\/chatbot\/query'/);
  assert.doesNotMatch(chat, /https?:\/\/(?:localhost|127\.0\.0\.1)/);
});

test('Railway container builds assets and excludes local development markers and secrets', () => {
  const docker = read('DockerFile');
  const railway = JSON.parse(read('railway.json'));
  assert.equal(railway.build.builder, 'DOCKERFILE');
  assert.equal(railway.build.dockerfilePath, 'DockerFile');
  assert.equal(railway.deploy.healthcheckPath, '/up');
  assert.match(docker, /FROM node:22[^\n]+ AS assets/);
  assert.match(docker, /RUN npm ci/);
  assert.match(docker, /RUN npm run build/);
  assert.match(docker, /COPY --from=assets \/build\/public\/build .\/public\/build/);
  assert.match(docker, /APACHE_DOCUMENT_ROOT=\/var\/www\/html\/public/);
  const ignored = read('.dockerignore');
  for (const path of ['.env*', '/public/hot', '/public/build', '/bootstrap/cache/*.php']) assert(ignored.split(/\r?\n/).includes(path));
  const startup = read('docker/start-app.sh');
  assert.match(startup, /\$\{PORT:-8080\}/);
  assert.match(startup, /exec apache2-foreground/);
  assert.doesNotMatch(startup, /php artisan (?:migrate|db:seed|key:generate|chatbot:build-knowledge)/);
  assert.match(read('docker/apache-app.conf'), /AllowOverride All/);
});
