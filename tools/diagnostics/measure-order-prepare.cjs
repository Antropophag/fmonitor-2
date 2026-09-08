// ORDER-PREPARE-LATENCY-001: read-only browser probe, run explicitly on a local stand.
// Arguments: baseURL playwrightModule storageState objectId searchQuery.
// stdout contains timings/counts only. Keep the authenticated state outside the repo.
'use strict';
const [base, moduleRoot, storageState, objectId, searchQuery] = process.argv.slice(2);
const { chromium } = require(moduleRoot);
const budgetMs = Number(process.env.FMONITOR_PREPARE_BUDGET_MS || 1000);
const sampleCount = Number(process.env.FMONITOR_PREPARE_SAMPLES || 3);
const origin = new URL(base);
if (origin.protocol !== 'http:' || !['127.0.0.1', 'localhost'].includes(origin.hostname)
    || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash
    || !/^[1-9][0-9]*$/.test(objectId) || !searchQuery || searchQuery.trim().length < 2
    || !Number.isFinite(budgetMs) || budgetMs <= 0 || !Number.isInteger(sampleCount)
    || sampleCount < 3 || sampleCount > 20) throw new Error('Invalid local probe configuration');
const median = values => [...values].sort((a, b) => a - b)[Math.floor(values.length / 2)];

(async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    const context = await browser.newContext({ storageState, viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const failures = [];
    await context.route('**/*', route => {
      const request = route.request();
      if (request.method() !== 'GET' || new URL(request.url()).origin !== origin.origin) {
        failures.push('unexpected request');
        return route.abort();
      }
      return route.continue();
    });
    page.on('pageerror', () => failures.push('page error'));
    page.on('console', message => { if (message.type() === 'error') failures.push('console error'); });
    page.on('requestfailed', () => failures.push('request failed'));
    page.on('response', response => { if (response.status() >= 400) failures.push('HTTP error'); });
    const samples = [];
    for (let iteration = 0; iteration < sampleCount; iteration += 1) {
      await page.goto(`${origin.origin}/pilot/objects/${objectId}`);
      await page.locator('[data-fm2-preloader]').waitFor({ state: 'hidden' });
      const started = performance.now();
      await page.getByRole('link', { name: 'Загрузить распоряжение', exact: true }).click();
      const open = page.getByRole('button', { name: 'Выбрать монтажников', exact: true });
      await open.waitFor({ state: 'visible' });
      await page.locator('[data-fm2-preloader]').waitFor({ state: 'hidden' });
      const formMs = performance.now() - started;
      await open.click();
      const dialog = page.getByRole('dialog', { name: 'Выбор монтажников' });
      await dialog.waitFor({ state: 'visible' });
      const searchStarted = performance.now();
      const responsePromise = page.waitForResponse(response =>
        new URL(response.url()).pathname === `/pilot/objects/${objectId}/assignment-order/installers`);
      await dialog.getByRole('searchbox').fill(searchQuery);
      const response = await responsePromise;
      const payload = await response.json();
      if (response.status() !== 200 || !payload.items?.length || payload.items.length > 20)
        throw new Error('Search did not return a bounded selectable page');
      await dialog.locator('input[type="checkbox"]').first().waitFor({ state: 'visible' });
      const searchMs = performance.now() - searchStarted;
      const timing = response.request().timing();
      const sample = { iteration, formMs, searchMs, readyMs: performance.now() - started,
        searchHttpMs: timing.responseEnd - timing.requestStart, items: payload.items.length };
      await dialog.locator('input[type="checkbox"]').first().check();
      await dialog.getByRole('button', { name: 'Готово', exact: true }).click();
      if (await page.locator('[data-main-selection] input[name="installerTabIds[]"]').count() !== 1)
        throw new Error('Selected installer is not retained in the form');
      samples.push(sample);
      console.log(JSON.stringify(sample));
    }
    const result = { medianFormMs: median(samples.map(s => s.formMs)),
      medianSearchMs: median(samples.map(s => s.searchMs)), budgetMs, errors: failures.length };
    console.log(JSON.stringify(result));
    if (failures.length || result.medianFormMs > budgetMs || result.medianSearchMs > budgetMs)
      process.exitCode = 1;
  } finally {
    await browser.close();
  }
})().catch(() => { console.error('Order preparation probe failed; inspect the local stand privately.'); process.exitCode = 1; });
