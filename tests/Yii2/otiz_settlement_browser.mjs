import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';

const config = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const {chromium} = createRequire(import.meta.url)(config.playwright);
const result = {stage: 'startup', operations: [], pageErrors: [], badResponses: []};
let browser;
try {
  browser = await chromium.launch({headless: true});
  const context = await browser.newContext();
  const page = await context.newPage();
  page.setDefaultTimeout(7000);
  page.on('pageerror', error => result.pageErrors.push(error.message));
  page.on('response', response => {
    if (response.status() >= 400) result.badResponses.push([new URL(response.url()).pathname, response.status()]);
  });
  const snapshot = `${config.origin}/pilot/otiz/snapshots/301`;
  result.stage = 'protected snapshot login return';
  await page.goto(snapshot);
  assert.equal(new URL(page.url()).pathname, '/pilot/login', 'guest enters Yii login');
  await page.locator('input[name="email"]').fill(config.email);
  await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
  await page.locator('input[name="password"]').fill(config.password);
  await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
  assert.equal(new URL(page.url()).pathname, '/pilot/otiz/snapshots/301', 'login returns to the requested snapshot');
  assert.equal(await page.locator('h1').innerText(), 'Выплаты на 30.09.2026');
  assert.equal(await page.locator('.fm2-otiz-object-row').count(), 1);
  assert.match(await page.locator('.fm2-otiz-summary').innerText(), /1\s000,00/);

  async function formFor(action) {
    const form = page.locator(`form[action="${action}"]`);
    assert.equal(await form.count(), 1, `one form for ${action}`);
    assert.equal((await form.getAttribute('method')).toLowerCase(), 'post');
    // Open real ancestor disclosures with native clicks; never invoke submit() or fabricate payloads.
    const disclosures = form.locator('xpath=ancestor::details');
    for (let index = 0; index < await disclosures.count(); index++) {
      const disclosure = disclosures.nth(index);
      if (await disclosure.getAttribute('open') === null) await disclosure.locator(':scope > summary').click();
    }
    const operation = await form.locator('input[name="operationId"]').inputValue();
    assert.match(operation, /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
    assert.ok(await form.locator('input[name="_csrf"]').inputValue());
    assert.ok(!result.operations.includes(operation), 'each new command owns a distinct operation ID');
    result.operations.push(operation);
    return form;
  }
  async function clickSubmit(form, query, message) {
    await Promise.all([page.waitForNavigation(), form.locator('button[type="submit"]').click()]);
    assert.equal(new URL(page.url()).pathname, '/pilot/otiz/snapshots/301');
    assert.equal(new URL(page.url()).search, query);
    assert.ok((await page.locator('[role="status"]').allTextContents()).includes(message));
  }
  result.stage = 'retained details and invalid native form';
  const invalid = await formFor('/pilot/otiz/snapshots/301/closures');
  // Opening the discipline form also exposes the existing object details.
  await page.locator('.fm2-otiz-trace > summary').click();
  assert.match(await page.locator('.fm2-otiz-trace').innerText(), /Премиальный фонд/);
  assert.match(await page.locator('.fm2-otiz-trace').innerText(), /Начислено за прогресс/);
  assert.match(await page.locator('.fm2-otiz-trace').innerText(), /1\s000,00/);
  assert.match(await page.locator('.fm2-otiz-allocation').innerText(), /Browser Installer/);
  assert.match(await page.locator('.fm2-otiz-allocation').innerText(), /КТУ 1,00/);
  assert.match(await page.locator('.fm2-otiz-issues').innerText(), /Synthetic warning retained/);
  assert.match(await page.locator('.fm2-otiz-issues').innerText(), /OTIZ owner/);
  await invalid.locator('[name="discipline"]').fill('2000.00');
  await invalid.locator('[name="basis"]').fill('Over budget must reject');
  await Promise.all([page.waitForNavigation(), invalid.locator('button[type="submit"]').click()]);
  assert.equal(new URL(page.url()).search, '?error=closure');
  assert.match(await page.locator('[role="alert"]').innerText(), /Действие не выполнено/);
  assert.equal(await page.locator('.fm2-otiz-ledger tbody tr').count(), 0);
  fs.writeFileSync(path.join(config.artifacts, 'invalid-complete'), 'ready', {mode: 0o600});
  const checkpointDeadline = Date.now() + 3000;
  while (!fs.existsSync(path.join(config.artifacts, 'invalid-observed')) && Date.now() < checkpointDeadline) await new Promise(resolve => setTimeout(resolve, 10));
  assert.ok(fs.existsSync(path.join(config.artifacts, 'invalid-observed')), 'parent independently observed rejection counts');
  result.rejectedOperation = result.operations.pop();

  result.stage = 'discipline form';
  const discipline = await formFor('/pilot/otiz/snapshots/301/closures');
  await discipline.locator('[name="discipline"]').fill('100.00');
  await discipline.locator('[name="basis"]').fill('Browser discipline');
  await discipline.locator('[name="artifact"]').fill('Browser evidence');
  await clickSubmit(discipline, '?closed=1', 'Удержание добавлено к выплате по объекту.');
  assert.equal(await page.locator('.fm2-otiz-ledger tbody tr').count(), 1);
  assert.match(await page.locator('.fm2-otiz-ledger').innerText(), /Browser discipline/);
  assert.match(await page.locator('.fm2-otiz-summary').innerText(), /900,00/);
  await page.screenshot({path: path.join(config.artifacts, 'discipline.png'), fullPage: true});

  result.stage = 'complete form';
  await clickSubmit(await formFor('/pilot/otiz/snapshots/301/payments/complete'), '?paid=1', 'Выплаты отмечены выполненными и учтены по объектам.');
  assert.equal(await page.locator('form[action="/pilot/otiz/snapshots/301/payments/complete"]').count(), 0);
  assert.equal(await page.locator('.fm2-otiz-ledger tbody tr').count(), 2);
  assert.match(await page.locator('.fm2-otiz-ledger').innerText(), /900,00/);

  result.stage = 'reverse form';
  const original = page.locator('.fm2-otiz-ledger tbody tr').filter({hasText: 'Browser discipline'});
  assert.equal(await original.count(), 1);
  const action = await original.locator('form').getAttribute('action');
  const reverse = await formFor(action);
  await reverse.locator('[name="basis"]').fill('Browser reversal');
  await clickSubmit(reverse, '?reversed=1', 'Предыдущая отметка о выплате отменена.');
  assert.equal(await page.locator('.fm2-otiz-ledger tbody tr').count(), 3);
  assert.match(await page.locator('.fm2-otiz-ledger').innerText(), /Browser reversal/);
  assert.match(await page.locator('.fm2-otiz-ledger').innerText(), /Сторно записи №/);
  assert.match(await page.locator('.fm2-otiz-summary').innerText(), /100,00/);
  assert.equal(await page.locator('form[action="/pilot/otiz/snapshots/301/payments/complete"]').count(), 1);
  await page.screenshot({path: path.join(config.artifacts, 'reversed.png'), fullPage: true});
  assert.deepEqual(result.pageErrors, []);
  assert.deepEqual(result.badResponses.filter(([url]) => url !== '/favicon.ico'), []);
  result.stage = 'complete';
  console.log('OTIZ_YII_BROWSER_OK');
} catch (error) {
  result.failure = error.stack || String(error);
  throw error;
} finally {
  fs.writeFileSync(config.result, JSON.stringify(result, null, 2), {mode: 0o600});
  if (browser) await browser.close();
}
