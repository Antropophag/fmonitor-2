import fs from 'node:fs';
import {createRequire} from 'node:module';
const c=JSON.parse(fs.readFileSync(process.argv[2],'utf8')),require=createRequire(import.meta.url);
const {chromium}=require(c.playwright),browser=await chromium.launch({headless:true});
const check=(v,m)=>{if(!v)throw new Error(m);};
try {
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage(),errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 page.on('response',r=>{if(new URL(r.url()).pathname.startsWith('/pilot/assets/')&&r.status()>=400)errors.push(r.url());});
 await page.goto(c.origin+'/pilot/login');await page.locator('[name=email]').fill(c.email);await page.locator('[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click();await page.locator('[name=password]').fill(c.password);await Promise.all([page.waitForResponse(r=>r.url().endsWith('/pilot/login')&&r.status()===303),page.locator('[name=password]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);
 const response=await page.goto(c.origin+'/pilot/objects/4512#completion');check(response.status()===200,'card ready');
 check(await page.locator('form [name=action][value=record_pto]').count()===1,'INTENDED_RED documentary PTO form');
 const form=action=>page.locator(`form:has(input[name=action][value="${action}"])`);
 async function submit(action){const f=form(action);await Promise.all([page.waitForResponse(r=>r.url().endsWith('/completion')&&r.request().method()==='POST'&&r.status()===303),f.locator('button').click()]);await page.waitForURL('**/pilot/objects/4512#completion');}
 async function inspectForm(action,fields){
  const f=form(action),d=f.locator('xpath=ancestor::details[1]');if(await d.count()&&!(await d.evaluate(e=>e.open)))await d.locator('summary').click();
  for(const width of [1440,390]){await page.setViewportSize({width,height:1000});
   for(const [name,constraints] of Object.entries(fields)){const input=f.locator(`[name="${name}"]`);for(const [key,value] of Object.entries(constraints))check(await input.getAttribute(key)===value,`${action} ${name} ${key}`);check(await input.evaluate(e=>e.labels.length>0),'input label');}
   const button=f.locator('button');await button.scrollIntoViewIfNeeded();check(await button.isVisible()&&await button.isEnabled(),'usable submit '+width);check(await button.evaluate(e=>{const r=e.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.top>=0&&r.bottom<=innerHeight&&r.width>0&&r.height>0;}),'submit within viewport');
  }
 }
 await inspectForm('record_pto',{ptoActDate:{type:'date',required:'',max:c.today}});
 await form('record_pto').locator('[name=ptoActDate]').fill('2026-09-05');await submit('record_pto');
 await inspectForm('record_declaration',{declarationDate:{type:'date',required:'',max:c.today},declarationDetails:{required:'',maxlength:'500'}});
 await form('record_declaration').locator('[name=declarationDate]').fill('2026-09-06');await form('record_declaration').locator('[name=declarationDetails]').fill('Д-UI-001');await submit('record_declaration');
 check((await page.textContent('body')).includes('100%'),'completed100');
 await inspectForm('correct_pto',{ptoActDate:{type:'date',required:'',max:c.today},reason:{required:'',maxlength:'1000'}});
 const correction=form('correct_declaration');const details=correction.locator('xpath=ancestor::details[1]');if(await details.count()&&!(await details.evaluate(e=>e.open)))await details.locator('summary').click();
 await inspectForm('correct_declaration',{declarationDate:{type:'date',required:'',max:c.today},declarationDetails:{maxlength:'500'},reason:{required:'',maxlength:'1000'}});
 await correction.locator('[name=declarationDate]').fill('2026-09-04');await correction.locator('[name=declarationDetails]').fill('Д-UI-002');await correction.locator('[name=reason]').fill('Сверено с оригиналом');await submit('correct_declaration');await page.reload();
 for(const text of ['Д-UI-001','Д-UI-002','Сверено с оригиналом'])check((await page.textContent('body')).includes(text),'history '+text);
 // Inspect the actual form/label relationships and both target viewports together.
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:1000});for(const d of await page.locator('#completion details').all())if(!(await d.evaluate(e=>e.open)))await d.locator('summary').first().click();
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'no overflow '+width);
  check(await page.locator('#completion input:not([type=hidden]), #completion textarea').evaluateAll(inputs=>inputs.every(e=>e.labels?.length>0)),'associated labels');
  await page.evaluate(()=>window.scrollTo(0,0));
  await page.screenshot({path:c.artifacts+`/documentary-${width===390?'mobile':'desktop'}.png`,fullPage:true});
 }
 await page.locator('a[href="/pilot/objects"]:visible').first().click();check((await page.textContent('body')).includes('Работы завершены'),'queue completion');
 const checklistResponse=await page.goto(c.origin+'/pilot/objects/4512/checklist');check(checklistResponse.status()===200,'FKR checklist return page '+checklistResponse.status());await page.locator('a[href="/pilot/objects/4512#completion"]').click();check(new URL(page.url()).hash==='#completion','checklist return');
 check(errors.length===0,'browser errors '+errors.join(';'));fs.writeFileSync(c.result,JSON.stringify({passed:true}));
}finally{await browser.close();}
