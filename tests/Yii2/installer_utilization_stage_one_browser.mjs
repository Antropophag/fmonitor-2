import fs from 'node:fs';
import {createRequire} from 'node:module';

const config = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const require = createRequire(import.meta.url);
const {chromium} = require(config.playwright);
const browser = await chromium.launch({headless: true});
const check = (value, message) => { if (!value) throw new Error(message); };

try {
  const page = await browser.newPage({viewport: {width: 1440, height: 900}});
  await page.goto(config.origin + '/pilot/login');
  await page.locator('[name=email]').fill(config.email);
  await page.locator('[name=email]').locator('xpath=ancestor::form').locator('button').click();
  await page.locator('[name=password]').fill(config.password);
  await Promise.all([
    page.waitForNavigation(),
    page.locator('[name=password]').locator('xpath=ancestor::form').locator('button').click(),
  ]);

  for (const [width, height] of [[1440, 900], [390, 844]]) {
    await page.setViewportSize({width, height});
    let response = await page.goto(config.origin + '/pilot/installers?q=Монтажник&current=present&upcoming=absent&page=1');
    check(response.status() === 200, 'INTENDED_RED directory utilization filter');
    check(await page.getByRole('link', {name: /Монтажник 001/}).count() === 1, 'installer card link');
    await page.getByRole('link', {name: /Монтажник 001/}).click();
    check(new URL(page.url()).pathname === '/pilot/installers/1', 'installer card route');
    check((await page.textContent('body')).includes('История участия'), 'installer history');
    check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'card overflow ' + width);
    await page.screenshot({path: config.artifacts + `/installer-card-${width}.png`, fullPage: true});
    await page.getByRole('link', {name: /К списку монтажников/}).click();
    const returned = new URL(page.url());
    check(returned.pathname === '/pilot/installers', 'directory return');
    check(returned.searchParams.get('q') === 'Монтажник' && returned.searchParams.get('current') === 'present' && returned.searchParams.get('upcoming') === 'absent' && returned.searchParams.get('page') === '1', 'exact allowlisted return state');
  }

  const picker = await page.goto(config.origin + '/pilot/objects/5001/assignment-order/selection');
  check(picker.status() === 200, 'picker page');
  await page.locator('[data-dialog-open]').click();
  await page.locator('[data-installer-search]').fill('Монтажник 001');
  await page.waitForResponse(response => response.url().includes('/assignment-order/installers?'));
  check((await page.locator('[data-installer-results]').textContent()).includes('текущ'), 'picker utilization context');

  fs.writeFileSync(config.result, JSON.stringify({passed: true}));
} finally {
  await browser.close();
}
