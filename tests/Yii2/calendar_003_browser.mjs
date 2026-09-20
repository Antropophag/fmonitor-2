import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);let raw='';for await(const part of process.stdin)raw+=part;
const input=JSON.parse(raw),{chromium}=require(input.playwright),browser=await chromium.launch({headless:true});
const check=(v,m)=>{if(!v)throw Error(m)};
async function open(width){const context=await browser.newContext({viewport:{width,height:900}});await context.addCookies(Object.entries(input.cookies).map(([name,value])=>({name,value,url:input.url+'/pilot'})));const page=await context.newPage();return{context,page};}
try{
 for(const [label,width] of [['desktop',1440],['mobile',390]]){
  const {context,page}=await open(width);await page.goto(input.url+'/pilot/objects');
  const nav=page.getByRole('link',{name:'Календарь',exact:true});check(await nav.count()===1,'calendar link in Mount');await nav.click();await page.waitForURL('**/pilot/calendar');
  check(await page.locator('a[href="/pilot/calendar"][aria-current="page"]').count()===1,'calendar current');
  const grid=page.locator('[data-shlz-calendar-grid]');check(await grid.isVisible(),'shlz Calendar Grid visible');check(await grid.locator('table').count()===1,'semantic calendar table');
  const disclosure=grid.getByRole('button',{name:'Ещё 1',exact:true});check(await disclosure.count()===1,'third same-type event is disclosed');const overflowId=await disclosure.getAttribute('aria-controls');const overflow=grid.locator('#'+overflowId);check(await overflow.isHidden()&&await disclosure.getAttribute('aria-expanded')==='false','overflow starts hidden');await disclosure.click();check(await overflow.isVisible()&&await disclosure.getAttribute('aria-expanded')==='true'&&await overflow.locator('[data-object-id]').count()===1,'one click reveals all hidden events');await disclosure.click();check(await overflow.isHidden()&&await disclosure.getAttribute('aria-expanded')==='false','second click collapses all hidden events');
  check(await page.locator('[data-calendar-page]').count()===1,'calendar page owner');
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'no viewport overflow');
  check(await grid.evaluate(e=>getComputedStyle(e).overflowX==='auto'||e.scrollWidth<=e.clientWidth),'grid scroll containment');
  const day=page.locator('[data-calendar-date="2026-10-15"]');check(await day.count()===1,'selectable day');check((await day.locator('xpath=ancestor::form').getAttribute('action'))==='/pilot/calendar','day selection has no jump fragment');await day.click();
  check(new URL(page.url()).hash===''&&await page.evaluate(()=>scrollY<80),'day selection does not jump down the page');
  check((await page.locator('[data-calendar-agenda]').innerText()).includes('15.10.2026')||await page.getByText('15.10.2026',{exact:true}).count()===1,'selected agenda date');
  const objects=await page.locator('[data-calendar-agenda] [data-object-id]').evaluateAll(es=>es.map(e=>Number(e.dataset.objectId)));check(JSON.stringify(objects.slice(0,2))==='[7,19]','numeric object order');
  check(await page.locator('[data-shlz-calendar-grid-state="today"]').count()>0,'today state');check(await day.getAttribute('aria-pressed')==='true','selected state accessible');
  const firstObject=page.locator('[data-calendar-agenda] a[href^="/pilot/objects/"]').first();check(await firstObject.isVisible(),'object return link');
  await page.screenshot({path:input.artifacts+'/calendar-'+label+'.png',fullPage:true});
  if(label==='mobile')check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'mobile shell no overflow');
  await context.close();
 }
 console.log('PASS calendar shlz-ui grid desktop/mobile');
}finally{await browser.close();}
