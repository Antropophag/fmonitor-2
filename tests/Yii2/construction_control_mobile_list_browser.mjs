import fs from 'node:fs';
import {createRequire} from 'node:module';
import assert from 'node:assert/strict';

const config=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const{chromium}=createRequire(import.meta.url)(config.playwright);
const browser=await chromium.launch({headless:true});
const login=async(page)=>{
  await page.goto(config.origin+'/pilot/login');
  await page.locator('input[name=email]').fill(config.email);
  await page.locator('input[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click();
  await page.locator('input[name=password]').fill(config.password);
  await Promise.all([page.waitForURL(/\/pilot\/objects/),page.locator('button[type=submit]').click()]);
};
try{
  for(const viewport of[{width:320,height:844},{width:390,height:844},{width:680,height:844},{width:681,height:844},{width:1032,height:1376},{width:1280,height:900}]){
    const page=await browser.newPage({viewport});await login(page);
    await page.goto(config.origin+'/pilot/construction-control?ownership=all&completed=1');
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true,'no document overflow '+viewport.width);
    const mobile=viewport.width<=680,tablet=viewport.width>680&&viewport.width<=1180,toolbar=page.locator('.fm2-control-toolbar');
    assert.equal(await toolbar.count(),1,'white toolbar exists');
    assert.equal(await toolbar.evaluate(e=>getComputedStyle(e).backgroundColor),'rgb(255, 255, 255)','toolbar surface');
    for(const selector of['.fm2-control-header h1','.fm2-control-description','[data-result-count]'])assert.equal(await page.locator(selector).isVisible(),!mobile,'responsive visibility '+selector+' '+viewport.width);
    assert.equal(await page.locator('.fm2-control-table thead').isVisible(),!mobile,'responsive table heading '+viewport.width);
    for(const selector of['[data-control-search]','input[name=ownership][value=mine]','input[name=ownership][value=all]','[data-show-completed]'])assert.equal(await toolbar.locator(selector).count(),1,'toolbar control '+selector);
    if(mobile)assert.equal((await toolbar.boundingBox())?.y<32,true,'toolbar is first mobile content surface');
    const row=page.locator('[data-object-id="4512"]');await row.waitFor();
    if(viewport.width===1032){const sidebar=page.locator('.fm2-sidebar'),trigger=page.locator('.fm2-nav-trigger'),main=page.locator('.fm2-main'),railBefore=await row.locator('.fm2-control-checklist-rail').boundingBox(),mainBefore=await main.boundingBox();assert.ok((await sidebar.boundingBox()).width<100,'tablet defaults to compact rail');await trigger.click();await page.waitForFunction(()=>document.querySelector('.fm2-sidebar').getBoundingClientRect().width>200);const mainOpen=await main.boundingBox(),railOpen=await row.locator('.fm2-control-checklist-rail').boundingBox();assert.equal(mainOpen.width,mainBefore.width,'tablet drawer does not resize workspace');assert.equal(railOpen.x,railBefore.x,'tablet drawer does not reflow checklist rail');await main.click({position:{x:mainOpen.width-10,y:mainOpen.height-10}});await page.waitForFunction(()=>document.querySelector('.fm2-sidebar').getBoundingClientRect().width<100);assert.equal(await trigger.evaluate(e=>e===document.activeElement),true,'outside close returns focus to tablet trigger');await trigger.click();await page.keyboard.press('Escape');await page.waitForFunction(()=>document.querySelector('.fm2-sidebar').getBoundingClientRect().width<100);assert.equal(await trigger.evaluate(e=>e===document.activeElement),true,'escape closes tablet drawer and returns focus');await trigger.click();const navLink=page.locator('.fm2-primary-nav a').first();await navLink.evaluate(element=>element.addEventListener('click',event=>event.preventDefault()));await navLink.click();await page.waitForFunction(()=>document.querySelector('.fm2-sidebar').getBoundingClientRect().width<100);assert.equal(await trigger.evaluate(e=>e===document.activeElement),false,'navigation selection closes tablet drawer without stealing destination focus');}
    assert.match(await row.innerText(),/Бескудниковский бульвар, дом 32, корпус 5, строение 2/,'long address remains readable');
    assert.match(await row.innerText(),/Инспекция сегодня/,'today label');
    assert.equal((await row.innerText()).includes(config.todayLabel),false,'Moscow today date is not repeated');
    for(const selector of['.fm2-local-sync','.fm2-shipment-status','.fm2-control-document-action','.fm2-control-inspection-action','.fm2-control-checklist-rail'])assert.equal(await row.locator(selector).count(),1,'row control '+selector);
    assert.equal(await row.locator('.fm2-local-sync').getAttribute('aria-label'),'Синхронизировано','sync accessible label');
    assert.match(await row.locator('.fm2-shipment-status').getAttribute('aria-label'),/Полностью отгружен/,'full shipment state');
    assert.match(await page.locator('[data-object-id="4513"] .fm2-shipment-status').getAttribute('aria-label'),/Частично отгружен/,'partial shipment state');
    assert.equal(await page.locator('[data-object-id="4514"] .fm2-shipment-status img').count(),0,'unknown shipment has no confirmed icon');
    assert.match(await page.locator('.fm2-control-table').innerText(),/Инспекций ещё не было|Последняя активность/,'activity states retained');
    const rail=row.locator('.fm2-control-checklist-rail'),rowBox=await row.boundingBox(),railBox=await rail.boundingBox();
    assert.ok(rowBox&&railBox&&Math.abs(rowBox.height-railBox.height)<2&&railBox.width>=40,'checklist rail owns full row height and target');
    assert.equal(await rail.getAttribute('href'),'/pilot/construction-control/objects/4512/checklist','checklist rail route');
    const documentAction=row.locator('.fm2-control-document-action'),calendar=row.locator('.fm2-control-inspection-action');
    for(const action of[documentAction,calendar]){const box=await action.boundingBox();assert.ok(box&&Math.abs(box.width-box.height)<1,'icon action stays circular '+viewport.width);assert.equal(await action.evaluate(e=>getComputedStyle(e).borderRadius),'50%','icon action circular radius');}
    const urlBefore=page.url(),rowColor=await row.evaluate(e=>getComputedStyle(e).backgroundColor);await row.locator('.fm2-object-cell').hover();assert.equal(await row.evaluate(e=>getComputedStyle(e).backgroundColor),rowColor,'record body has no hover action');await row.locator('.fm2-object-cell').click();assert.equal(page.url(),urlBefore,'record body is not clickable');
    const actionGroup=row.locator('.fm2-control-status-actions'),actionBox=await actionGroup.boundingBox();if(mobile){assert.equal(await actionGroup.evaluate(e=>getComputedStyle(e).justifyContent),'flex-end','mobile actions align right');assert.ok(actionBox&&railBox&&actionBox.x+actionBox.width<=railBox.x+1,'mobile actions stay against checklist rail');}
    if(!mobile){const rows=page.locator('[data-control-row]'),rails=page.locator('.fm2-control-checklist-rail');for(let i=0;i<Math.min(3,await rows.count());i++){const rb=await rows.nth(i).boundingBox(),cb=await rails.nth(i).boundingBox();assert.ok(rb&&cb&&Math.abs(rb.y-cb.y)<2&&Math.abs(rb.height-cb.height)<2&&cb.width<=56,'desktop rail belongs to narrow row area '+i);assert.ok(cb.x>=0&&cb.x+cb.width<=viewport.width,'checklist rail remains inside viewport with expanded sidebar '+viewport.width+' row '+i);}}else assert.ok(rowBox&&rowBox.height<=165,'mobile record remains compact '+viewport.width);
    for(const[action,name]of[[documentAction,'Открыть техническую документацию'],[calendar,'Изменить план инспекции'],[rail,'Открыть чек-лист']]){assert.match(await action.getAttribute('aria-label'),new RegExp(name),'accessible action '+name);const box=await action.boundingBox();assert.ok(box&&box.width>=40&&box.height>=40,'touch target '+name);await action.focus();assert.equal(await action.evaluate(e=>e===document.activeElement),true,'focus '+name);assert.notEqual(await action.evaluate(e=>getComputedStyle(e).outlineStyle),'none','visible focus '+name);}
    assert.equal(await documentAction.locator('svg').evaluate(e=>getComputedStyle(e).color),await documentAction.evaluate(e=>getComputedStyle(e).color),'document icon inherits button color');assert.equal(await calendar.locator('svg').evaluate(e=>getComputedStyle(e).color),await calendar.evaluate(e=>getComputedStyle(e).color),'calendar icon inherits button color');
    assert.equal(await documentAction.getAttribute('href'),'https://bitrix24public.com/control-4512','document exact target');
    assert.equal(await calendar.evaluate(e=>e.classList.contains('fm2-control-inspection-action--planned')),true,'planned calendar outlined state');
    assert.equal(await page.locator('[data-object-id="4514"] .fm2-control-inspection-action').evaluate(e=>e.classList.contains('shlz-button--primary')),true,'unplanned calendar primary state');
    assert.equal(await page.locator('[data-object-id="4513"] .fm2-control-document-action').isDisabled(),true,'unavailable document disabled');
    const railColor=await rail.evaluate(e=>getComputedStyle(e).backgroundColor);await rail.hover();assert.notEqual(await rail.evaluate(e=>getComputedStyle(e).backgroundColor),railColor,'checklist rail has hover feedback');
    await calendar.click();const dialog=page.locator('[data-inspection-dialog]');await dialog.waitFor();assert.equal(await dialog.evaluate(e=>e.open),true,'planned calendar opens dialog');assert.equal(await dialog.locator('[data-inspection-cancel]').isVisible(),true,'planned dialog exposes cancel');assert.equal(await dialog.locator('.shlz-modal__close').evaluate(e=>getComputedStyle(e).borderRadius),'50%','modal close is circular');const picker=dialog.locator('.shlz-date-picker'),visibleDate=picker.locator('.shlz-date-field__input');assert.equal(await picker.count(),1,'inspection uses public shlz date picker');assert.equal(await picker.locator('input[type="hidden"][name="inspectionDate"]').count(),1,'picker owns ISO submission');assert.equal(await dialog.locator('input[type="date"]:not([disabled])').count(),0,'native date is fallback only');assert.equal(await visibleDate.evaluate(e=>getComputedStyle(e).outlineStyle),'none','date input has no nested browser focus frame');assert.equal(await visibleDate.evaluate(e=>getComputedStyle(e).boxShadow),'none','date input has no nested browser focus shadow');await picker.locator('.shlz-date-field__trigger').click();assert.equal(await picker.locator('.shlz-date-picker__popover').isVisible(),true,'library calendar opens');const modalBox=await dialog.boundingBox();assert.ok(modalBox&&modalBox.y>=0&&modalBox.y+modalBox.height<=viewport.height,'inspection modal fits viewport '+viewport.width);assert.equal(await dialog.locator('[data-inspection-submit]').isVisible(),true,'submit remains visible with calendar open');await page.keyboard.press('Escape');await page.keyboard.press('Escape');
    await page.emulateMedia({reducedMotion:'reduce'});assert.equal(await calendar.evaluate(e=>getComputedStyle(e).transitionDuration),'0s','reduced motion disables action transition');
    await page.close();
  }
  const page=await browser.newPage({viewport:{width:390,height:844}});await login(page);await page.goto(config.origin+'/pilot/construction-control?ownership=all&completed=1&page=1');
  await page.locator('[data-control-search]').fill('CONTROL-4512');await page.waitForURL(url=>url.searchParams.get('query')==='CONTROL-4512');assert.equal(await page.locator('[data-control-row]').count(),1,'live factory search');assert.equal(new URL(page.url()).searchParams.has('page'),false,'search resets page');
  await page.locator('input[name=ownership][value=mine]').check();await page.waitForURL(url=>url.searchParams.get('ownership')==='mine');
  await page.locator('[data-show-completed]').uncheck();await page.waitForURL(url=>!url.searchParams.has('completed'));
  await page.locator('[data-clear-filters]').click();await page.waitForURL(url=>url.searchParams.get('ownership')==='mine'&&!url.searchParams.has('query')&&!url.searchParams.has('completed'));
  await page.close();
  fs.writeFileSync(config.result,JSON.stringify({passed:true}),{mode:0o600});
}finally{await browser.close();}
