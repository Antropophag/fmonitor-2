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
  await page.locator('input[name="email"]').locator('xpath=ancestor::form').locator('button[type="submit"]').click();
  await page.locator('input[name="password"]').fill(config.password);
  await Promise.all([page.waitForURL(/\/pilot\/objects/),page.locator('button[type="submit"]').click()]);
  await page.goto(`${config.origin}/pilot/objects/4512#completion`);
  const form=page.locator('[data-completion-form="correct_declaration"]');
  assert.equal(await form.count(),1,'INTENDED_RED completion recovery form hook');
  await form.locator('textarea[name="reason"]').fill('Черновик после неизвестного результата');
  await form.locator('input[name="declarationDetails"]').fill('Д-BROWSER-RECOVERY');

  let posts=0;
  await page.route('**/pilot/objects/4512/completion',async route=>{
    if(route.request().method()!=='POST')return route.continue();
    posts+=1;
    if(posts===1){await new Promise(resolve=>setTimeout(resolve,200));return route.abort('failed');}
    if(posts===2)return route.fulfill({status:503,contentType:'text/plain; charset=UTF-8',body:'Service unavailable.\n'});
    return route.continue();
  });
  await form.evaluate(node=>{node.requestSubmit();node.requestSubmit();});
  await page.getByText('Результат сохранения не подтверждён. Проверьте актуальные документы перед повторной отправкой.').waitFor();
  assert.equal(posts,1,'double submit produces one in-flight POST');
  assert.equal(await form.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY');
  assert.equal(await form.locator('textarea[name="reason"]').inputValue(),'Черновик после неизвестного результата');
  assert.equal(await form.locator('button[type="submit"]').isEnabled(),true,'unknown result unlocks form');
  await page.waitForTimeout(500);assert.equal(posts,1,'unknown result has no delayed automatic retry');

  await form.evaluate(node=>node.requestSubmit());
  await page.getByText('Результат сохранения не подтверждён. Проверьте актуальные документы перед повторной отправкой.').waitFor();
  assert.equal(posts,2,'non-HTML failure is one unconfirmed request');assert.equal(await form.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY');

  await form.locator('textarea[name="reason"]').fill('   ');
  await form.evaluate(node=>node.requestSubmit());
  const corrected=page.locator('[data-completion-form="correct_declaration"]');
  await corrected.locator('[name="reason"][aria-invalid="true"]').waitFor();
  assert.equal(await corrected.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY');
  assert.equal(await corrected.locator('textarea[name="reason"]').inputValue(),'   ');
  assert.equal(await corrected.locator('textarea[name="reason"]').evaluate(node=>document.activeElement===node),true,'invalid field focused');
  assert.equal(await corrected.locator('xpath=ancestor::details').getAttribute('open'),'','correction remains open');
  assert.equal(await page.locator('#object-tab-readiness').getAttribute('aria-selected'),'true','containing tab active');
  const box=await corrected.locator('textarea[name="reason"]').boundingBox();assert.ok(box&&box.y>=0&&box.y<900,'focused error is scrolled into viewport');

  await corrected.locator('input[name="factId"]').evaluate(node=>node.value='999999');
  await corrected.locator('textarea[name="reason"]').fill('Конфликт актуальности');
  await corrected.evaluate(node=>node.requestSubmit());
  const conflicted=page.locator('[data-completion-form="correct_declaration"]');await conflicted.getByText('Исправляемая запись не найдена.').waitFor();
  assert.equal(await conflicted.locator('[data-completion-focus="true"]').evaluate(node=>document.activeElement===node),true,'409 general error focused');
  assert.equal(await conflicted.locator('input[name="declarationDetails"]').inputValue(),'Д-BROWSER-RECOVERY','409 retains submitted details');
  assert.equal(await conflicted.locator('textarea[name="reason"]').inputValue(),'Конфликт актуальности','409 retains submitted reason');

  await conflicted.locator('textarea[name="reason"]').fill('Подтверждено в браузере');
  await Promise.all([page.waitForURL(/\/pilot\/objects\/4512#completion$/),conflicted.locator('button[type="submit"]').click()]);
  await page.getByText('Подтверждено в браузере').waitFor();
  fs.writeFileSync(config.result,JSON.stringify({passed:true,posts}),{mode:0o600});
} finally {
  await browser.close();
}
