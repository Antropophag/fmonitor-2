import fs from 'node:fs';
import {createRequire} from 'node:module';
const c=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const require=createRequire(import.meta.url),{chromium}=require(c.playwright);
const check=(v,m)=>{if(!v)throw new Error(m);};
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
const login=async(page,email)=>{
 await page.goto(c.origin+'/pilot/login');await page.locator('[name=email]').fill(email);
 await Promise.all([page.waitForNavigation(),page.locator('[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);
 await page.locator('[name=password]').fill(c.password);
 await Promise.all([page.waitForNavigation(),page.locator('[name=password]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);
};
const fillDate=async(form,name,iso)=>{
 const field=form.locator(`[name="${name}"]`);check(await field.count()===1,`INTENDED_RED: ${name} is a separate required field`);
 if(await field.getAttribute('type')==='hidden'){
  const visible=field.locator('xpath=ancestor::*[contains(concat(" ",normalize-space(@class)," ")," shlz-date-picker ")]').locator('.shlz-date-field__input');
  const [y,m,d]=iso.split('-');await visible.fill(`${d}.${m}.${y}`);await visible.press('Tab');
 }else await field.fill(iso);
 check(await field.inputValue()===iso,`date ${name} preserves ISO value`);
};
try{
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 await login(page,c.email);await page.goto(c.origin+'/pilot/objects/4512/assignment-orders/81/originals/submit');
 let form=page.locator('[data-original-upload-form]');await form.waitFor();
 check(await form.locator('[name=appendixStartDate]').count()===1,'INTENDED_RED: upload offers signed appendix start date');
 check(await form.locator('[name=documentDate]').count()===1,'document date remains distinct');
 check(await form.locator('[name=appendixStartDate]').inputValue()==='2026-10-01','initial suggested start comes from plan for explicit confirmation');
 for(const width of [1440,390]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'upload has no horizontal overflow');await page.screenshot({path:c.artifacts+`/original-start-upload-${width}.png`,fullPage:true});}
 await form.locator('[name=original]').setInputFiles({name:'signed.pdf',mimeType:'application/pdf',buffer:Buffer.from(c.pdf,'base64')});
 await fillDate(form,'documentDate','2026-09-01');await fillDate(form,'appendixStartDate','2030-10-09');await form.locator('[name=compositionConfirmed]').check();
 const requestId=await form.locator('[name=requestId]').inputValue();let first=true;let resolveLost;const lost=new Promise(r=>resolveLost=r);
 await page.route('**/originals',async route=>{
  if(first&&route.request().method()==='POST'){first=false;const response=await route.fetch();check(response.status()===201,'server accepts and opens before response loss');await route.abort('failed');resolveLost();}
  else await route.continue();
 });
 await form.locator('[data-original-submit]').click();await lost;
 await page.waitForFunction(()=>{const b=document.querySelector('[data-original-submit]');return b&&!b.disabled;});
 check(await form.locator('[name=requestId]').inputValue()===requestId,'response loss retains request id');
 check(await form.locator('[name=original]').evaluate(e=>e.files.length)===1,'response loss retains file');
 await Promise.all([page.waitForResponse(r=>r.url().endsWith('/originals')&&r.request().method()==='POST'&&r.status()===200),form.locator('[data-original-submit]').click()]);
 await page.waitForURL(c.origin+'/pilot/objects/4512');
 check(await page.locator('[name=actualStartDate]').count()===0,'card has no editable actual start');
 check(await page.locator('time[datetime="2030-10-09"]').count()>0,'card shows signed future start');
 for(const width of [1440,390]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'opened card no overflow');await page.screenshot({path:c.artifacts+`/original-start-card-${width}.png`,fullPage:true});}
 await page.goto(c.origin+'/pilot/objects/4512/assignment-orders/81/originals/history');check((await page.locator('body').innerText()).includes('2030-10-09')||(await page.locator('body').innerText()).includes('09.10.2030'),'history displays signed appendix date');
 await page.goto(c.origin+'/pilot/objects/4512/assignment-orders/81/originals/submit');form=page.locator('[data-original-upload-form]');check(await form.locator('[name=appendixStartDate]').inputValue()==='2030-10-09','correction loads preserved signed date, not object plan');
 const engineerContext=await browser.newContext(),engineer=await engineerContext.newPage();await login(engineer,c.engineerEmail);
 const checklist=await engineer.goto(c.origin+'/pilot/objects/4512/checklist');check(checklist.status()===200,'checklist accessible immediately despite future date');
 check(await engineer.locator('[name=actualStartDate]').count()===0,'engineer has no date-opening control');
 check(!(await engineer.locator('body').innerText()).includes('После открытия пункты чек-листа станут доступны'),'checklist is not behind manual opening gate');
 check(errors.length===0,'no uncaught browser errors: '+errors.join(','));
}finally{await browser.close();}
