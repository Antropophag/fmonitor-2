const fs = require('node:fs');
const path = require('node:path');

const { chromium } = require(process.env.FMONITOR_TEST_PLAYWRIGHT_MODULE);
const origin = process.env.FMONITOR_AUDIT_ORIGIN;
const email = process.env.FMONITOR_INITIAL_OWNER_EMAIL;
const password = process.env.FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD;
const artifacts = process.argv[2];
if (!origin || !email || !password || !artifacts) throw new Error('missing audit configuration');

const allProfiles = [
  ['laptop-1366', 1366, 768, false],
  ['laptop-1536', 1536, 864, false],
  ['tablet-landscape', 1280, 800, true],
  ['tablet-portrait', 800, 1280, true],
  ['mobile-360', 360, 800, true],
  ['mobile-390', 390, 844, true],
];
const allRoutes = [
  ['objects', '/pilot/objects'],
  ['construction', '/pilot/construction-control'],
  ['calendar', '/pilot/calendar'],
  ['dashboard', '/pilot/dashboard'],
  ['installers', '/pilot/installers'],
  ['users', '/pilot/admin/users'],
  ['roles', '/pilot/admin/roles'],
  ['feedback', '/pilot/feedback'],
  ['object-card', '/pilot/objects/39058'],
  ['checklist', '/pilot/objects/2232/checklist'],
  ['selection', '/pilot/objects/39058/assignment-order/selection'],
  ['deadline-certificates', '/pilot/objects/2448/deadline-certificates'],
  ['otiz-objects', '/pilot/otiz/objects'],
  ['otiz-payments', '/pilot/otiz/payments'],
  ['otiz-history', '/pilot/otiz/history'],
];
const selectedProfiles = new Set((process.env.FMONITOR_AUDIT_PROFILES || '').split(',').filter(Boolean));
const selectedRoutes = new Set((process.env.FMONITOR_AUDIT_ROUTES || '').split(',').filter(Boolean));
const profiles = selectedProfiles.size ? allProfiles.filter(([name]) => selectedProfiles.has(name)) : allProfiles;
const routes = selectedRoutes.size ? allRoutes.filter(([name]) => selectedRoutes.has(name)) : allRoutes;

async function login(page) {
  await page.goto(origin + '/pilot/login');
  await page.locator('input[name="email"]').fill(email);
  await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
  await page.locator('input[name="password"]').fill(password);
  await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
}

async function geometry(page) {
  return page.evaluate(() => {
    const visible = element => {
      const box = element.getBoundingClientRect();
      const style = getComputedStyle(element);
      return box.width > 0 && box.height > 0 && style.visibility !== 'hidden' && style.display !== 'none';
    };
    const interactive = [...document.querySelectorAll('a[href],button,input,select,textarea,summary')].filter(visible);
    const offscreen = interactive.filter(element => {
      const box = element.getBoundingClientRect();
      return box.left < -1 || box.right > document.documentElement.clientWidth + 1;
    }).map(element => ({ tag: element.tagName, text: (element.innerText || element.value || element.getAttribute('aria-label') || '').trim().slice(0, 120) }));
    const headerCollisions = [];
    for (const row of document.querySelectorAll('thead tr')) {
      const headers = [...row.querySelectorAll(':scope > th')].filter(visible).map(header => {
        const range = document.createRange();
        range.selectNodeContents(header);
        return { label: (header.textContent || '').trim(), text: range.getBoundingClientRect() };
      });
      for (let index = 0; index < headers.length - 1; index++) {
        if (headers[index].text.right + 4 > headers[index + 1].text.left) headerCollisions.push(`${headers[index].label} -> ${headers[index + 1].label}`);
      }
    }
    const localScroll = [...document.querySelectorAll('.shlz-table-wrap,[data-mobile-strategy="contained-scroll"]')].filter(visible).map(element => ({
      label: element.getAttribute('aria-label') || element.className,
      clientWidth: element.clientWidth,
      scrollWidth: element.scrollWidth,
    }));
    const unownedWideTables = [...document.querySelectorAll('table')].filter(visible).flatMap(table => {
      const owner = table.closest('.shlz-table-wrap,[data-mobile-strategy="contained-scroll"]');
      if (table.scrollWidth <= (owner?.clientWidth || table.clientWidth) + 1) return [];
      const overflow = owner ? getComputedStyle(owner).overflowX : 'missing';
      return /auto|scroll/.test(overflow) ? [] : [{ label: table.getAttribute('aria-label') || table.querySelector('caption')?.textContent?.trim() || table.className, overflow }];
    });
    return {
      title: document.title,
      pathname: location.pathname,
      documentWidth: document.documentElement.scrollWidth,
      viewportWidth: document.documentElement.clientWidth,
      mainCount: document.querySelectorAll('main,[role="main"]').length,
      offscreen,
      headerCollisions,
      localScroll,
      unownedWideTables,
    };
  });
}

(async () => {
  fs.mkdirSync(artifacts, { recursive: true, mode: 0o700 });
  const browser = await chromium.launch({ headless: true });
  const manifest = { profiles: {}, errors: [] };
  try {
    for (const [profile, width, height, touch] of profiles) {
      const context = await browser.newContext({ viewport: { width, height }, hasTouch: touch, isMobile: false });
      const page = await context.newPage();
      page.on('pageerror', error => manifest.errors.push({ profile, type: 'pageerror', message: error.message }));
      await login(page);
      manifest.profiles[profile] = {};
      for (const [name, route] of routes) {
        const response = await page.goto(origin + route, { waitUntil: 'networkidle' });
        const metrics = await geometry(page);
        const screenshot = path.join(artifacts, `${profile}--${name}.png`);
        await page.screenshot({ path: screenshot, fullPage: true });
        manifest.profiles[profile][name] = { route, status: response?.status() || null, screenshot, ...metrics };
      }
      await context.close();
    }
  } finally {
    await browser.close();
  }
  fs.writeFileSync(path.join(artifacts, 'manifest.json'), JSON.stringify(manifest, null, 2));
  const failures = Object.entries(manifest.profiles).flatMap(([profile, screens]) => Object.entries(screens).flatMap(([screen, result]) => {
    if (result.status !== 200) return [];
    const reasons = [];
    if (result.documentWidth > result.viewportWidth + 1) reasons.push(`document ${result.documentWidth}>${result.viewportWidth}`);
    if (result.unownedWideTables.length) reasons.push(`unowned tables ${JSON.stringify(result.unownedWideTables)}`);
    return reasons.length ? [{ profile, screen, reasons }] : [];
  }));
  if (failures.length) throw new Error(`DEVICE_MATRIX_RED ${JSON.stringify(failures)}`);
})().catch(error => { console.error(error.stack || error); process.exit(1); });
