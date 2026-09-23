const fs = require('node:fs');

const config = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const { chromium } = require(config.playwright);
const check = (value, message) => { if (!value) throw new Error(message); };

async function main() {
  const browser = await chromium.launch({ headless: true });
  try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('response', response => {
    if (new URL(response.url()).pathname.startsWith('/pilot/assets/') && response.status() >= 400) errors.push(response.url());
  });

  const response = await page.goto(config.origin + '/pilot/login');
  check(response.status() === 200, 'SETUP_FAILURE login route ' + response.status());

  const viewports = {};
  const screenshots = {};
  for (const [width, height] of [[320,568],[360,800],[390,844],[768,900],[800,1280],[1024,900],[1280,800],[1366,768],[1440,900],[1536,864],[1920,900]]) {
    await page.setViewportSize({ width, height });
    await page.goto(config.origin + '/pilot/login');
    viewports[width] = await page.evaluate(() => ({
      viewport: innerWidth,
      documentWidth: document.documentElement.scrollWidth,
      main: document.querySelectorAll('main').length,
      lastControlVisible: (() => {
        const controls = [...document.querySelectorAll('a[href],button,input,select,textarea')].filter(node => !node.disabled && !node.hidden);
        const box = controls.at(-1)?.getBoundingClientRect();
        return Boolean(box && box.bottom <= innerHeight + Math.max(document.documentElement.scrollHeight - scrollY - innerHeight, 0));
      })(),
    }));
    check(viewports[width].documentWidth <= viewports[width].viewport + 1, 'INTENDED_RED no page overflow ' + width);
    check(viewports[width].main === 1, 'one main ' + width);
    const path = `${config.artifacts}/shlz-before-${width}.png`;
    await page.screenshot({ path, fullPage: true });
    screenshots[width] = { path, sha256: require('node:crypto').createHash('sha256').update(fs.readFileSync(path)).digest('hex') };
  }

  check(errors.length === 0, 'browser errors ' + errors.join('; '));
  fs.writeFileSync(config.result, JSON.stringify({ viewports, screenshots }));
  check(await page.locator('main').count() === 1, 'INTENDED_RED exactly one main landmark');
  const skip = page.locator('a[href="#main-content"]');
  check(await skip.count() === 1, 'INTENDED_RED one shared skip link');
  await skip.focus();
  await page.keyboard.press('Enter');
  check(await page.evaluate(() => document.activeElement === document.querySelector('main')), 'INTENDED_RED skip link focuses main');
  } finally {
    await browser.close();
  }
}

main().catch(error => { console.error(error.stack || error.message); process.exitCode = 1; });
