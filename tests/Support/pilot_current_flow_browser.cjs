const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const [port, moduleRoot, artifactRoot, resultPath] = process.argv.slice(2);
const { chromium } = require(moduleRoot);
const base = `http://127.0.0.1:${port}`;
const output = { errors: [], downloads: 0 };

function monitor(page) {
  page.on('console', message => { if (message.type() === 'error') output.errors.push(message.text()); });
  page.on('pageerror', error => output.errors.push(error.message));
  page.on('requestfailed', request => output.errors.push(request.failure()?.errorText));
  page.on('response', async response => {
    if (response.status() >= 400) {
      let body = '';
      try { body = (await response.text()).replace(/\s+/g, ' ').slice(0, 400); } catch {}
      output.errors.push(`${new URL(response.url()).pathname}:${response.status()}:${body}`);
    }
  });
  page.on('download', () => { output.downloads += 1; });
}

async function waitObserved(predicate, label, timeout = 30000) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    if (predicate()) return;
    await new Promise(resolve => setTimeout(resolve, 25));
  }
  throw new Error(`${label} was not observed`);
}

async function login(page, email) {
  await page.getByLabel('Корпоративный email', { exact: true }).fill(email);
  await page.getByRole('button', { name: 'Продолжить', exact: true }).click();
  await page.getByLabel('Пароль', { exact: true }).fill('Synthetic protected E2E 2026');
  await page.getByRole('button', { name: 'Войти', exact: true }).click();
}

async function selectComposition(page) {
  await page.goto(`${base}/pilot/objects/4512/assignment-order/selection`);
  const opener = page.getByRole('button', { name: 'Выбрать монтажников', exact: true });
  await opener.click();
  const dialog = page.getByRole('dialog', { name: 'Выбор монтажников' });
  const search = dialog.locator('[data-installer-search]');
  await search.fill('Монтажник');
  await dialog.locator('.fm2-picker-result').first().waitFor();
  await dialog.locator('.fm2-picker-result').first().click();
  await dialog.getByRole('button', { name: 'Готово', exact: true }).click();
  await page.locator('input[name="controlEngineerUserId"]').first().check();
  await page.locator('input[name="controlEngineerConfirmed"]').check();
  await page.getByRole('button', { name: 'Сохранить состав', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
}

async function verifyInlineTemplate(page, context) {
  let captured = null;
  const templatePattern = /\/assignment-orders\/\d+\/template$/;
  await context.route('**/assignment-orders/*/template', async route => {
    if (route.request().method() !== 'POST' || !templatePattern.test(new URL(route.request().url()).pathname)) return route.continue();
    const response = await route.fetch();
    const bytes = await response.body();
    captured = { status: response.status(), headers: response.headers(), bytes };
    await route.fulfill({ response, body: bytes });
  });
  const popupPromise = page.waitForEvent('popup');
  await page.getByRole('button', { name: 'Сформировать шаблон', exact: true }).click();
  const popup = await popupPromise;
  monitor(popup);
  await popup.waitForURL(/\/assignment-orders\/\d+\/template$/);
  await page.waitForTimeout(300);
  await context.unroute('**/assignment-orders/*/template');
  if (!captured) throw new Error('Template PDF response was not observed');
  const { status, headers, bytes } = captured;
  if (status !== 200 || headers['content-type'] !== 'application/pdf') throw new Error('Template response is not PDF 200');
  if (!headers['content-disposition']?.startsWith('inline;')) throw new Error('Template response is not inline');
  if (bytes.length === 0 || bytes.subarray(0, 5).toString() !== '%PDF-') throw new Error(`Template PDF bytes are invalid length=${bytes.length} first8=${bytes.subarray(0, 8).toString('hex')}`);
  if (headers['content-length'] !== String(bytes.length)) throw new Error('Template PDF length header mismatch');
  if (output.downloads !== 0) throw new Error('Template triggered a download');
  output.templateBytes = bytes.length;
  output.templateSha256 = crypto.createHash('sha256').update(bytes).digest('hex');
  fs.writeFileSync(path.join(artifactRoot, 'template.pdf'), bytes, { mode: 0o600 });
  await popup.close();
}

async function uploadAndCorrect(page) {
  await page.getByRole('link', { name: 'Загрузить оригинал', exact: true }).click();
  await page.locator('input[name="original"]').setInputFiles(path.join(artifactRoot, 'original.pdf'));
  await page.locator('input[name="documentDate"]').fill('2026-09-07');
  await page.locator('input[name="compositionConfirmed"]').check();
  await page.getByRole('button', { name: 'Загрузить оригинал', exact: true }).click();
  await page.waitForURL('**/pilot/objects/4512');
  if (await page.getByText('Загрузите распоряжение', { exact: false }).count()) throw new Error('Accepted original hidden after return');

  await page.goto(`${base}/pilot/objects/4512/assignment-orders/81/originals/submit`);
  await page.locator('input[name="original"]').setInputFiles(path.join(artifactRoot, 'original.pdf'));
  await page.locator('input[name="documentDate"]').fill('2026-09-06');
  await page.locator('input[name="correctionReason"]').fill('Синтетическое исправление даты');
  await page.locator('input[name="compositionConfirmed"]').check();
  await page.getByRole('button', { name: 'Исправить оригинал', exact: true }).click();
  await page.waitForURL('**/pilot/objects/4512');
  if (await page.getByText('Применить состав', { exact: false }).count()) throw new Error('Separate apply UI returned');
}

async function verifyOriginalDownload(page, context) {
  const href = await page.locator('a[href*="/originals/"][href$="/download"]').first().getAttribute('href');
  if (!href) throw new Error('Current original download link missing');
  const expected = fs.readFileSync(path.join(artifactRoot, 'original.pdf'));
  const get = await context.request.get(`${base}${href}`);
  const bytes = await get.body();
  const head = await context.request.head(`${base}${href}`);
  const getHeaders = get.headers();
  const headHeaders = head.headers();
  if (get.status() !== 200 || head.status() !== 200 || getHeaders['content-type'] !== 'application/pdf') throw new Error('Current original GET/HEAD rejected');
  for (const name of ['content-type', 'content-length', 'content-disposition', 'x-content-type-options', 'cache-control']) if (getHeaders[name] !== headHeaders[name]) throw new Error(`Current original GET/HEAD ${name} mismatch`);
  if (!bytes.equals(expected) || crypto.createHash('sha256').update(bytes).digest('hex') !== crypto.createHash('sha256').update(expected).digest('hex')) throw new Error('Current original exact bytes/hash mismatch');
  output.originalSha256 = crypto.createHash('sha256').update(bytes).digest('hex');
}

async function openAsDistinctActor(browser) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  monitor(page);
  const actions = [];
  page.on('request', request => {
    if (request.method() === 'POST' && new URL(request.url()).pathname.endsWith('/execution')) actions.push(new URLSearchParams(request.postData() || '').get('action'));
  });
  await page.goto(`${base}/pilot/objects/4512`);
  await login(page, 'protected19@shlz.ru');
  await page.waitForURL('**/pilot/objects/4512');
  await page.locator('input[name="actualStartDate"]').fill('2026-09-07');
  const responsePromise = page.waitForResponse(response => response.request().method() === 'POST' && new URL(response.url()).pathname.endsWith('/execution'));
  await page.getByRole('button', { name: 'Открыть работы', exact: true }).click();
  const openingResponse = await responsePromise;
  if (openingResponse.status() !== 303) throw new Error(`Direct opening rejected status=${openingResponse.status()} body=${(await openingResponse.text()).replace(/\s+/g, ' ').slice(0, 400)}`);
  await page.waitForURL('**/pilot/objects/4512');
  if (JSON.stringify(actions) !== '["open_confirmed"]') throw new Error('Opening used an extra application request');
  output.separateOpener = true;
  await context.close();
}

async function completeChecklist(browser) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  monitor(page);
  let acceptedItems = 0;
  let acceptedPhotos = 0;
  let acceptedSections = 0;
  const acceptedTrace = [];
  page.on('response', async response => {
    if (!new URL(response.url()).pathname.endsWith('/checklist/operations') || response.status() >= 400) return;
    try {
      const body = await response.json();
      acceptedItems = Object.keys(body.projection?.items || {}).length;
      acceptedPhotos = (body.projection?.photos || []).length;
      acceptedSections = Object.keys(body.projection?.completedSections || {}).length;
      const request = JSON.parse(response.request().postData() || '{}');
      acceptedTrace.push({ type: request.type, baseRevision: request.baseRevision, revision: body.projection?.revision });
    } catch {}
  });
  await page.goto(`${base}/pilot/objects/4512/checklist`);
  await login(page, 'protected73@shlz.ru');
  await page.waitForURL('**/checklist');
  let expectedItems = 0;
  for (let section = 1; section <= 7; section += 1) {
    const part = page.locator(`[data-check-section="${section}"]`);
    const checkAll = part.locator('[data-check-all]');
    if (section !== 1 && !(await checkAll.isVisible())) await part.locator('.fm2-section-toggle').click();
    try { await checkAll.waitFor({ state: 'visible', timeout: 5000 }); }
    catch { const state = await part.evaluate(node => ({ aria: node.querySelector('.fm2-section-toggle')?.getAttribute('aria-expanded'), display: getComputedStyle(node.querySelector('[data-check-all]')).display, html: node.outerHTML.replace(/\s+/g, ' ').slice(0, 500) })); throw new Error(`Checklist section ${section} did not expand ${JSON.stringify(state)}`); }
    await checkAll.click();
    expectedItems += await part.locator('.fm2-check-toggle').count();
    await page.locator('[data-bulk-confirm]').click();
    await page.waitForFunction(id => [...document.querySelectorAll(`[data-check-section="${id}"] .fm2-check-toggle`)].every(item => item.getAttribute('aria-checked') === 'true'), section);
    await page.waitForFunction(() => document.querySelector('[data-sync-banner]')?.dataset.state === 'ok');
    await waitObserved(() => acceptedItems === expectedItems, `accepted checklist item count ${expectedItems}`);
    await part.locator('[data-photo-input]').last().setInputFiles(path.join(artifactRoot, 'photo.png'));
    await part.locator('[data-section-state]').filter({ hasText: 'Завершён' }).waitFor();
    await page.waitForFunction(() => document.querySelector('[data-sync-banner]')?.dataset.state === 'ok');
    await waitObserved(() => acceptedPhotos === section, `accepted checklist photo count ${section}`);
    await waitObserved(() => acceptedSections === section, `accepted checklist section count ${section}`);
  }
  await page.reload();
  await page.waitForFunction(() => document.querySelector('[data-total-progress]')?.textContent === '85');
  const projection = JSON.parse(Buffer.from(await page.locator('[data-checklist]').getAttribute('data-projection'), 'base64').toString('utf8'));
  output.workProgress = 85;
  output.workItems = Object.keys(projection.items).length;
  output.photos = projection.photos.length;
  output.checklistAcceptedTrace = acceptedTrace;
  await page.screenshot({ path: path.join(artifactRoot, 'checklist-85.png'), fullPage: true });
  await context.close();
}

async function completeDocuments(ownerPage) {
  await ownerPage.goto(`${base}/pilot/objects/4512`);
  await ownerPage.getByRole('button', { name: 'Зафиксировать акт ПТО', exact: true }).click();
  await ownerPage.locator('input[name="declarationDetails"]').fill('SYNTHETIC-DECLARATION');
  await ownerPage.getByRole('button', { name: 'Завершить работы', exact: true }).click();
  await ownerPage.getByRole('progressbar', { name: 'Готовность работ' }).waitFor();
  output.finalProgress = await ownerPage.getByRole('progressbar', { name: 'Готовность работ' }).getAttribute('aria-valuenow');
  await ownerPage.reload();
  if (await ownerPage.getByRole('progressbar', { name: 'Готовность работ' }).getAttribute('aria-valuenow') !== '100') throw new Error('Completion lost');
  await ownerPage.screenshot({ path: path.join(artifactRoot, 'card-100.png'), fullPage: true });
}

(async () => {
  const browser = await chromium.launch({ headless: true, channel: 'chrome' });
  const ownerContext = await browser.newContext({ viewport: { width: 1280, height: 900 }, acceptDownloads: true });
  const ownerPage = await ownerContext.newPage();
  monitor(ownerPage);
  try {
    await ownerPage.goto(`${base}/`);
    await login(ownerPage, 'test18@shlz.ru');
    await ownerPage.waitForURL('**/pilot/objects');
    await ownerPage.goto(`${base}/pilot/objects?q=4512`);
    await ownerPage.locator('a[href="/pilot/objects/4512"]').click();
    await selectComposition(ownerPage);
    await verifyInlineTemplate(ownerPage, ownerContext);
    await uploadAndCorrect(ownerPage);
    await verifyOriginalDownload(ownerPage, ownerContext);
    await openAsDistinctActor(browser);
    await completeChecklist(browser);
    await completeDocuments(ownerPage);
    if (output.errors.length) throw new Error(`Browser errors: ${JSON.stringify(output.errors)}`);
    output.result = 'PASS';
  } catch (error) {
    output.result = 'FAIL';
    output.failure = error.message;
    try { await ownerPage.screenshot({ path: path.join(artifactRoot, 'failure-page.png'), fullPage: true }); } catch (screenshotError) { output.failureScreenshotError = screenshotError.message; }
    process.exitCode = 1;
  } finally {
    output.errorDetails = output.errors;
    output.errors = output.errors.length;
    fs.writeFileSync(resultPath, JSON.stringify(output, null, 2), { mode: 0o600 });
    await ownerContext.close();
    await browser.close();
  }
})();
