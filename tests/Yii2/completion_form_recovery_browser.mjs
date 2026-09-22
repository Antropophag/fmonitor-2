import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
import assert from 'node:assert/strict';

const config=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const {chromium}=createRequire(import.meta.url)(config.playwright);
const browser=await chromium.launch({headless:true});
try {
  const page=await browser.newPage({viewport:{width:1280,height:900}});
  await page.goto(`${config.origin}/pilot/login`);
  await page.locator('input[name="email"]').fill(config.email);
  await page.locator('input[name="password"]').fill(config.password);
  await Promise.all([page.waitForURL(/\/pilot\/objects/),page.locator('button[type="submit"]').click()]);
  await page.goto(`${config.origin}/pilot/objects/4512#completion`);
  const form=page.locator('[data-completion-form="correct_declaration"]');
  await form.locator('textarea[name="reason"]').fill('Черновик после неизвестного результата');
  await form.locator('input[name="declarationDetails"]').fill('Д-BROWSER-RECOVERY');

  let posts=0;
  await page.route('**/pilot/objects/4512/completion',async route=>{
    if(route.request().method()!=='POST')return route.continue();
    posts+=1;await new Promise(resolve=>setTimeout(resolve,200));await route.abort('failed');
  },{times:1});
  await form.evaluate(node=>{node.requestSubmit();node.requestSubmit();});
  await page.getByText('Результат сохранения не подтверждён. Проверьте актуальные документы перед повторной отправкой.').waitFor();
  assert.equal(posts,1,'double submit produces one in-flight POST');
  assert.equal(await form.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY');
  assert.equal(await form.locator('textarea[name="reason"]').inputValue(),'Черновик после неизвестного результата');
  assert.equal(await form.locator('button[type="submit"]').isEnabled(),true,'unknown result unlocks form');

  await form.locator('textarea[name="reason"]').fill('   ');
  await form.evaluate(node=>node.requestSubmit());
  const corrected=page.locator('[data-completion-form="correct_declaration"]');
  await corrected.locator('[name="reason"][aria-invalid="true"]').waitFor();
  assert.equal(await corrected.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY');
  assert.equal(await corrected.locator('textarea[name="reason"]').inputValue(),'   ');
  assert.equal(await corrected.locator('textarea[name="reason"]').evaluate(node=>document.activeElement===node),true,'invalid field focused');
  assert.equal(await corrected.locator('xpath=ancestor::details').getAttribute('open'),'','correction remains open');

  await corrected.locator('textarea[name="reason"]').fill('Подтверждено в браузере');
  await Promise.all([page.waitForURL(/\/pilot\/objects\/4512#completion$/),corrected.locator('button[type="submit"]').click()]);
  await page.getByText('Подтверждено в браузере').waitFor();
  fs.writeFileSync(config.result,JSON.stringify({passed:true,posts}),{mode:0o600});
} finally {
  await browser.close();
}
