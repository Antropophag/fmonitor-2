import { createRequire } from 'node:module';
const require=createRequire(import.meta.url);
let raw='';for await(const part of process.stdin)raw+=part;
const input=JSON.parse(raw);const {chromium}=require(input.playwright);const browser=await chromium.launch({headless:true});
const assert=(value,message)=>{if(!value)throw Error(message);};
async function context(cookies,width){const c=await browser.newContext({viewport:{width,height:900}});await c.addCookies(Object.entries(cookies).map(([name,value])=>({name,value,url:input.url+'/pilot'})));return c;}
async function geometry(page,label){assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),label+' overflow');for(const element of await page.locator('form button[type=submit], textarea').all()){if(!await element.isVisible())continue;await element.scrollIntoViewIfNeeded();if(await element.evaluate(e=>e.tagName==='TEXTAREA'))assert(await element.evaluate(e=>{const box=e.getBoundingClientRect();let top=box.top,bottom=box.bottom;for(let p=e.parentElement;p;p=p.parentElement){if(/hidden|clip|auto|scroll/.test(getComputedStyle(p).overflowY)){const r=p.getBoundingClientRect();top=Math.max(top,r.top);bottom=Math.min(bottom,r.bottom);}}return bottom-top>=96;}),'INTENDED_RED FEEDBACK-001 multiline text must remain visible through field wrappers');assert(await element.evaluate(e=>{const r=e.getBoundingClientRect();const hit=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);return hit===e||e.contains(hit);}),label+' primary control obscured');}}
try {
 for(const [name,width]of[['desktop',1280],['mobile',360]]){
  const c=await context(input.cookies,width);const page=await c.newPage();
  await page.goto(input.url+'/pilot/objects/4512');
  const navToggle=page.locator('.fm2-nav-trigger');
  if(name==='desktop'){
   assert(await navToggle.count()===1,'INTENDED_RED one desktop collapse control');
   assert(await navToggle.getAttribute('aria-label')==='Свернуть меню','expanded control label');
   assert(await navToggle.getAttribute('data-shlz-icon')==='chevron-left-duo','expanded shlz chevron');
   assert(await navToggle.evaluate(e=>{const r=e.getBoundingClientRect();return r.width>=44&&r.height>=44;}),'collapse target 44px');
   await navToggle.focus();await page.keyboard.press('Enter');
   await page.waitForFunction(()=>document.querySelector('.fm2-nav-trigger')?.getAttribute('aria-label')==='Развернуть меню');
   assert(await navToggle.getAttribute('aria-label')==='Развернуть меню','collapsed control label');
   assert(await navToggle.getAttribute('data-shlz-icon')==='chevron-right-duo','collapsed shlz chevron');
   await page.reload();assert(await navToggle.getAttribute('aria-label')==='Развернуть меню','collapsed state persists reload');
  }else assert(await navToggle.isVisible()===false,'mobile has no visible collapse control');
  const link=page.locator('a.fm2-feedback-fab[href^="/pilot/feedback"]');assert(await link.count()===1,'INTENDED_RED one floating feedback action');
  assert(await link.evaluate(e=>{const r=e.getBoundingClientRect(),s=getComputedStyle(e);const nav=e.closest('nav[aria-label="Основная навигация"]');const hit=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);return !nav&&s.position==='fixed'&&r.width>=44&&r.height>=44&&(hit===e||e.contains(hit));}),'INTENDED_RED floating feedback is outside nav, fixed, visible and usable');
  assert(await link.getAttribute('data-shlz-icon')==='chat','floating feedback uses shlz chat icon');
  assert(await link.evaluate(e=>{const a=e.getBoundingClientRect(),s=getComputedStyle(e);const overlaps=b=>a.left<b.right&&a.right>b.left&&a.top<b.bottom&&a.bottom>b.top;const bottom=document.querySelector('.fm2-sidebar')?.getBoundingClientRect();const action=document.querySelector('main button, [role=main] button, main .shlz-button')?.getBoundingClientRect();return (!bottom||!overlaps(bottom))&&(!action||!overlaps(action))&&a.right<=innerWidth&&a.bottom<=innerHeight&&parseFloat(s.right)>=8&&parseFloat(s.bottom)>=8;}),'INTENDED_RED feedback avoids mobile nav, primary action and preserves safe edge clearance');
  await link.focus();await page.keyboard.press('Enter');await page.waitForURL('**/pilot/feedback**');
  assert(await page.locator('a[href="/pilot/admin/feedback"]').count()===0,'ordinary user has no review affordance');
  const textarea=page.locator('textarea[name="description"]');await textarea.waitFor();const id=await textarea.getAttribute('id');assert(id&&await page.locator(`label[for="${id}"]`).count()===1,'accessible label');
  assert(await page.locator('input[name="pagePath"]').inputValue()==='/pilot/objects/4512','current object context');
  assert(await textarea.evaluate(e=>e.getBoundingClientRect().height>=96),'INTENDED_RED FEEDBACK-001 usable multiline description');
  await textarea.fill('Браузер '+name);await geometry(page,name+' form');await page.screenshot({path:input.artifacts+'/feedback-'+name+'.png',fullPage:true});
  const submit=page.locator('form').filter({has:textarea}).locator('button[type=submit]');await submit.focus();await page.keyboard.press('Enter');await page.getByText('Обращение сохранено',{exact:false}).waitFor();
  const back=page.locator('a[href="/pilot/objects/4512"]');assert(await back.count()>0,'confirmation return');await back.first().click();await page.waitForURL('**/pilot/objects/4512');
  await c.close();
  const ac=await context(input.adminCookies,width);const admin=await ac.newPage();await admin.goto(input.url+'/pilot/feedback');
  await admin.locator('a[href="/pilot/admin/feedback"]').click();await admin.getByText('Браузер '+name,{exact:true}).waitFor();
  const form=admin.locator('form[action$="/result"]').first();assert(await form.count()===1,'review form');await form.locator('textarea[name="result"]').fill('Проверено '+name);await geometry(admin,name+' review');
  await admin.screenshot({path:input.artifacts+'/feedback-review-'+name+'.png',fullPage:true});await form.locator('button[type=submit]').click();
  await admin.goto(input.url+'/pilot/admin/feedback');await admin.getByText('Проверено '+name,{exact:false}).waitFor();await ac.close();
 }
 console.log('PASS feedback browser submit, return, authorized review desktop/mobile');
}finally{await browser.close();}
