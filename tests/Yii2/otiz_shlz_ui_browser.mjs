import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';

const config=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const {chromium}=createRequire(import.meta.url)(config.playwright);
const result={stage:'startup',viewports:{},pageErrors:[],badResponses:[]};
let browser;
try{
  browser=await chromium.launch({headless:true});
  const context=await browser.newContext();
  const page=await context.newPage();
  page.setDefaultTimeout(8000);
  page.on('pageerror',error=>result.pageErrors.push(error.message));
  page.on('response',response=>{if(response.status()>=400)result.badResponses.push([new URL(response.url()).pathname,response.status()]);});
  await page.goto(`${config.origin}/pilot/otiz/snapshots/501`);
  await page.locator('input[name="email"]').fill(config.email);
  await Promise.all([page.waitForNavigation(),page.locator('button[type="submit"]').click()]);
  await page.locator('input[name="password"]').fill(config.password);
  await Promise.all([page.waitForNavigation(),page.locator('button[type="submit"]').click()]);
  assert.equal(new URL(page.url()).pathname,'/pilot/otiz/snapshots/501');

  result.stage='semantic snapshot';
  assert.equal(await page.locator('body.shlz-scope .fm2-shell').count(),1,'shared authenticated shlz shell');
  assert.equal(await page.locator('main, [role="main"]').count(),1,'one main landmark');
  assert.equal(await page.locator('.fm2-otiz').count(),1,'OTIZ composition root');
  const workflow=page.locator('[data-otiz-workflow-header]');
  assert.equal(await workflow.count(),1,'one workflow header');
  assert.match(await workflow.innerText(),/30\.09\.2026/);
  assert.match(await workflow.innerText(),/Готовы к выплате/);
  assert.match(await workflow.innerText(),/900,00/);
  assert.equal(await workflow.locator('.shlz-button--primary').count(),1,'one primary next action');
  const object=page.locator('[data-otiz-object="7301"]');
  assert.equal(await object.count(),1,'one object-scoped region');
  assert.match(await object.innerText(),/BROWSER-LONG-OBJECT-IDENTITY/);
  assert.match(await object.innerText(),/Synthetic warning retained/);
  assert.match(await object.innerText(),/OTIZ owner with long responsibility label/);
  assert.match(await object.innerText(),/Browser Installer/);
  assert.equal(await object.locator('.fm2-otiz-issues [data-severity="warning"]').count(),1,'textual issue severity stays object-scoped');
  assert.equal(await page.locator('form[action$="/closures"] .shlz-button').count(),1,'discipline uses button composition');
  assert.equal(await page.locator('form[action$="/payments/complete"] .shlz-button--primary').count(),1,'complete payment is primary');
  assert.equal(await page.locator('form[action*="/closures/"][action$="/reverse"] .shlz-button--danger').count(),1,'reversal is danger');
  assert.equal(await page.locator('a.shlz-button--secondary[href$="/export.xlsx"]').count(),1,'export navigation remains a secondary link');
  const head=await context.request.head(`${config.origin}/pilot/otiz/snapshots/501`);assert.equal(head.status(),404,'existing HEAD outcome is preserved and read-only');
  await page.goto(`${config.origin}/pilot/otiz/snapshots/501`);fs.writeFileSync(path.join(config.artifacts,'read-check'),'ready',{mode:0o600});let checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'read-ready'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'read-ready')),'parent verified GET/HEAD fact stability');

  result.stage='current financial navigation';
  for(const [destination,current] of [['/pilot/otiz','objects'],['/pilot/otiz/objects','objects'],['/pilot/otiz/payments','payments'],['/pilot/otiz/history','history'],['/pilot/otiz/snapshots/501','snapshot']]){
    await page.goto(config.origin+destination);
    assert.equal(await page.locator(`[data-otiz-tab="${current}"][aria-current="page"]`).count(),1,`current ${current} tab`);
    assert.equal(await page.locator('[data-otiz-tabs] a').count()>=3,true,'financial navigation remains links');
    if(current==='payments'){const paymentWorkflow=page.locator('[data-otiz-workflow-header]');assert.match(await paymentWorkflow.innerText(),/Рассчитать|Подготовить/);assert.equal(await paymentWorkflow.locator('.shlz-button--primary').count(),1,'payments has one primary action');}
  }
  await page.goto(`${config.origin}/pilot/otiz/snapshots/501`);

  result.stage='responsive matrix';
  for(const width of [320,768,1024,1440]){
    await page.setViewportSize({width,height:900});
    await page.goto(`${config.origin}/pilot/otiz/snapshots/501`);
    const metrics=await page.evaluate(()=>{const visible=[...document.querySelectorAll('button,a,input,select')].filter(el=>{const r=el.getBoundingClientRect();return r.width>0&&r.height>0;});return{doc:document.documentElement.scrollWidth,viewport:document.documentElement.clientWidth,offscreen:visible.some(el=>{const r=el.getBoundingClientRect();return r.left<0||r.right>document.documentElement.clientWidth+1;}),collisions:visible.some((el,index)=>visible.slice(index+1).some(other=>{const a=el.getBoundingClientRect(),b=other.getBoundingClientRect();return a.left<b.right&&a.right>b.left&&a.top<b.bottom&&a.bottom>b.top&&!el.contains(other)&&!other.contains(el);} ))};});
    result.viewports[width]=metrics;
    assert.ok(metrics.doc<=metrics.viewport+1,`no page overflow at ${width}`);
    assert.equal(metrics.offscreen,false,`actions stay in viewport at ${width}`);
    assert.equal(metrics.collisions,false,`interactive targets do not overlap at ${width}`);
    await page.screenshot({path:path.join(config.artifacts,`otiz-${width}.png`),fullPage:true});
  }

  result.stage='200 percent layout zoom equivalent';
  const zoomContext=await browser.newContext({viewport:{width:384,height:450},screen:{width:768,height:900},deviceScaleFactor:2});await zoomContext.addCookies(await context.cookies());const zoomPage=await zoomContext.newPage();await zoomPage.goto(`${config.origin}/pilot/otiz/snapshots/501`);
  const zoom=await zoomPage.evaluate(()=>({ratio:screen.width/document.documentElement.clientWidth,dpr:devicePixelRatio,doc:document.documentElement.scrollWidth,viewport:document.documentElement.clientWidth,primary:document.querySelector('[data-otiz-workflow-header] .shlz-button--primary')?.getBoundingClientRect().toJSON()}));
  assert.equal(zoom.ratio,2,'768 physical pixels expose 384 CSS px at 200% layout equivalent');assert.equal(zoom.dpr,2);assert.ok(zoom.doc<=zoom.viewport+1,'no page overflow at 200% layout equivalent');assert.ok(zoom.primary&&zoom.primary.width>0&&zoom.primary.height>0,'primary action remains visible at zoom');await zoomContext.close();

  result.stage='register labelled rows';
  await page.setViewportSize({width:320,height:900});
  await page.goto(`${config.origin}/pilot/otiz/objects`);
  assert.equal(await page.locator('.fm2-otiz-register-table[data-mobile-strategy="labelled-rows"]').count(),1);
  assert.equal(await page.locator('.fm2-otiz-register-table td[data-label]').count()>=4,true);

  result.stage='ledger contained scroll and keyboard';
  await page.goto(`${config.origin}/pilot/otiz/snapshots/501`);
  const ledger=page.locator('[data-mobile-strategy="contained-scroll"]');
  assert.equal(await ledger.count(),1);
  assert.ok(await ledger.getAttribute('aria-label'));
  assert.equal(await ledger.getAttribute('tabindex'),'0');
  await page.locator('body').press('Tab');const first=await page.evaluate(()=>({tag:document.activeElement?.tagName,href:document.activeElement?.getAttribute('href'),text:document.activeElement?.textContent?.trim()}));
  assert.equal(first.href,'#main-content','skip link is first keyboard target');
  await page.keyboard.press('Enter');assert.equal(await page.evaluate(()=>document.activeElement?.id),'main-content','skip link activation moves focus to main');
  const disclosure=page.locator('.fm2-otiz-trace > summary').first();await disclosure.focus();assert.equal(await page.locator(':focus-visible').count(),1,'focus is visible');await page.keyboard.press('Space');assert.equal(await disclosure.locator('xpath=..').getAttribute('open')!==null,true,'native disclosure activates from keyboard');
  await page.goto(`${config.origin}/pilot/otiz/payments`);const period=page.locator('input[name="reportDate"]');await period.focus();await page.keyboard.press('Tab');const next=await page.evaluate(()=>({tag:document.activeElement?.tagName,type:document.activeElement?.getAttribute('type'),text:document.activeElement?.textContent?.trim(),primary:document.activeElement?.classList.contains('shlz-button--primary')}));assert.deepEqual(next,{tag:'BUTTON',type:'submit',text:'Подготовить расчёт',primary:true},'natural period-to-primary tab order');

  result.stage='coarse pointer and reduced motion';
  const touch=await browser.newContext({viewport:{width:320,height:900},hasTouch:true,isMobile:true});
  const touchPage=await touch.newPage();
  await touch.addCookies(await context.cookies());
  await touchPage.goto(`${config.origin}/pilot/otiz/snapshots/501`);
  const targets=await touchPage.locator('a,button,input,select,summary').evaluateAll(elements=>elements.filter(el=>{const r=el.getBoundingClientRect();return r.width>0&&r.height>0;}).map(el=>Math.min(el.getBoundingClientRect().width,el.getBoundingClientRect().height)));
  assert.equal(targets.every(size=>size>=44),true,'coarse targets are at least 44px');
  await touch.close();
  const semantics=async target=>({text:(await target.locator('[data-otiz-workflow-header]').innerText()).replace(/\s+/g,' '),actions:await target.locator('a,button,input,select,summary').evaluateAll(elements=>elements.filter(el=>{const r=el.getBoundingClientRect();return r.width>0&&r.height>0;}).map(el=>`${el.tagName}:${el.getAttribute('href')||el.getAttribute('action')||el.textContent?.trim()}`)),order:await target.locator('a,button,input,select,summary').evaluateAll(elements=>elements.map(el=>[el.tagName,el.getAttribute('name'),el.getAttribute('type'),el.getAttribute('href'),el.closest('form')?.getAttribute('action')||null,el.textContent?.trim()||'']))});
  const normalSemantics=await semantics(page);await page.emulateMedia({reducedMotion:'reduce'});await page.reload();const reducedSemantics=await semantics(page);
  assert.deepEqual(reducedSemantics,normalSemantics,'reduced motion preserves content actions and DOM order');
  const activeMotion=await page.locator('.fm2-otiz *').evaluateAll(elements=>elements.filter(el=>{const s=getComputedStyle(el);return s.display!=='none'&&(s.transitionDuration.split(',').some(v=>parseFloat(v)>0)||s.animationDuration.split(',').some(v=>parseFloat(v)>0));}).map(el=>el.className));
  assert.deepEqual(activeMotion,[],'no effective optional motion remains');

  result.stage='javascript off';
  const noJs=await browser.newContext({javaScriptEnabled:false,viewport:{width:320,height:900}});
  await noJs.addCookies(await context.cookies());
  const noJsPage=await noJs.newPage();
  for(const [destination,needle] of [['/pilot/otiz','Экономика объектов'],['/pilot/otiz/objects','Экономика объектов'],['/pilot/otiz/payments','Подготовка выплат'],['/pilot/otiz/history','Архив расчётов'],['/pilot/otiz/snapshots/501','BROWSER-LONG-OBJECT-IDENTITY']]){await noJsPage.goto(config.origin+destination);assert.equal(await noJsPage.locator('.fm2-otiz').count(),1,`SSR root ${destination}`);assert.match(await noJsPage.locator('body').innerText(),new RegExp(needle),`specific SSR content ${destination}`);assert.equal(await noJsPage.locator('[data-otiz-tabs] a').count()>=3,true,`native navigation ${destination}`);}
  assert.match(await noJsPage.locator('body').innerText(),/BROWSER-LONG-OBJECT-IDENTITY/);assert.equal(await noJsPage.locator('form[action$="/payments/complete"]').count(),1);assert.equal(await noJsPage.locator('a[href$="/export.xlsx"]').count(),1);
  await noJs.close();

  result.stage='permission denial and malformed precedence';fs.writeFileSync(path.join(config.artifacts,'permission-request'),'ready',{mode:0o600});checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'permission-denied'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'permission-denied')));
  const deniedMalformed=await context.request.post(`${config.origin}/pilot/otiz/calculate`,{form:{_csrf:'malformed',reportDate:'not-a-date',operationId:'not-a-uuid'},maxRedirects:0});assert.equal(deniedMalformed.status(),403,'authorization precedes malformed payload');const deniedGet=await context.request.get(`${config.origin}/pilot/otiz/payments`);assert.equal(deniedGet.status(),403);assert.doesNotMatch(await deniedGet.text(),/<form|shlz-button/,'permission-limited response exposes no actions');
  fs.writeFileSync(path.join(config.artifacts,'permission-action-done'),'ready',{mode:0o600});checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'permission-facts-ready'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'permission-facts-ready')));fs.writeFileSync(path.join(config.artifacts,'permission-restore-request'),'ready',{mode:0o600});checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'permission-restored'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'permission-restored')));

  result.stage='draft blocked and absent collections';
  await page.goto(`${config.origin}/pilot/otiz/snapshots/502`);const blocked=page.locator('[data-otiz-object="7302"]');assert.match(await blocked.innerText(),/BLOCKED-EMPTY-OBJECT/);assert.match(await blocked.innerText(),/Required evidence is absent/);assert.equal(await blocked.locator('.fm2-otiz-allocation [data-allocation]').count(),0);assert.equal(await page.locator('[data-empty="allocations"]').count(),1);assert.equal(await page.locator('[data-mobile-strategy="contained-scroll"]').count(),0);assert.equal(await page.locator('form[action$="/payments/complete"]').count(),0);assert.equal(await page.locator('form[action$="/closures"]').count(),0);
  result.stage='draft ready one primary and keyboard activation';await page.goto(`${config.origin}/pilot/otiz/snapshots/503`);const draftWorkflow=page.locator('[data-otiz-workflow-header]');assert.match(await draftWorkflow.innerText(),/На проверке/);assert.equal(await draftWorkflow.locator('.shlz-button--primary').count(),1);const acceptForm=draftWorkflow.locator('form[action$="/accept"]');assert.equal(await acceptForm.count(),1);assert.deepEqual((await acceptForm.locator('input').evaluateAll(inputs=>inputs.map(input=>input.name).sort())),['_csrf'],'accept form exact payload');const acceptCsrf=await acceptForm.locator('input[name="_csrf"]').inputValue();assert.ok(acceptCsrf.length>20,'accept form carries current CSRF');const accept=acceptForm.locator('button[type="submit"]');await accept.focus();await Promise.all([page.waitForNavigation(),page.keyboard.press('Enter')]);assert.equal(new URL(page.url()).search,'?error=incomplete','draft primary activates from keyboard and preserves incomplete outcome');assert.match(await page.locator('[role="alert"]').innerText(),/Действие не выполнено/);fs.writeFileSync(path.join(config.artifacts,'accept-first-done'),'ready',{mode:0o600});checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'accept-first-ready'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'accept-first-ready')));
  const repeat=await context.request.post(`${config.origin}/pilot/otiz/snapshots/503/accept`,{form:{_csrf:acceptCsrf},maxRedirects:0});assert.equal(repeat.status(),303);assert.match(repeat.headers()['location']||'',/error=incomplete/);fs.writeFileSync(path.join(config.artifacts,'accept-repeat-done'),'ready',{mode:0o600});checkpoint=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'accept-repeat-ready'))&&Date.now()<checkpoint)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'accept-repeat-ready')));

  result.stage='empty register and history';fs.writeFileSync(path.join(config.artifacts,'empty-request'),'ready',{mode:0o600});const emptyDeadline=Date.now()+3000;while(!fs.existsSync(path.join(config.artifacts,'empty-ready'))&&Date.now()<emptyDeadline)await new Promise(resolve=>setTimeout(resolve,10));assert.ok(fs.existsSync(path.join(config.artifacts,'empty-ready')),'parent prepared empty projection');
  await page.goto(`${config.origin}/pilot/otiz/objects`);assert.equal(await page.locator('[data-empty="objects"]').count()>=1,true);await page.goto(`${config.origin}/pilot/otiz/history`);assert.equal(await page.locator('[data-empty="history"]').count()>=1,true);
  assert.deepEqual(result.pageErrors,[]);
  assert.deepEqual(result.badResponses.filter(([url])=>url!=='/favicon.ico'),[]);
  result.stage='complete';
  console.log('OTIZ_SHLZ_UI_OK');
}catch(error){result.failure=error.stack||String(error);throw error;}
finally{fs.writeFileSync(config.result,JSON.stringify(result,null,2),{mode:0o600});if(browser)await browser.close();}
