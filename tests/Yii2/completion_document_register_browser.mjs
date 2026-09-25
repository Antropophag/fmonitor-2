import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);let raw='';for await(const part of process.stdin)raw+=part;
const input=JSON.parse(raw),{chromium}=require(input.playwright),browser=await chromium.launch({headless:true});
const check=(value,message)=>{if(!value)throw Error(message)};
async function context(width){const context=await browser.newContext({viewport:{width,height:900}});await context.addCookies(Object.entries(input.cookies).map(([name,value])=>({name,value,url:input.url+'/pilot'})));return{context,page:await context.newPage()};}
try{
 const desktop=await context(1440),page=desktop.page;
 await page.goto(input.url+'/pilot/completion-register?mode=pto_without_declaration&q=TEST&page=1');
 check(await page.getByRole('heading',{name:'ПТО и декларации'}).isVisible(),'register heading');
 check(await page.locator('.shlz-table-wrap').evaluate(e=>getComputedStyle(e).overflowX==='auto'||e.scrollWidth<=e.clientWidth),'desktop table contained');
 const target=page.locator('a[href="/pilot/objects/4512#completion"]');check(await target.isVisible(),'direct completion link');await target.click();await page.waitForURL('**/pilot/objects/4512#completion');
 check(await page.locator('#completion').isVisible(),'completion target visible');
 const form=page.locator('[data-completion-form="record_declaration"]');check(await form.isVisible(),'existing declaration form');
 await form.locator('[name="declarationDate"]').evaluate((element)=>{element.value='2026-09-06';element.dispatchEvent(new Event('change',{bubbles:true}));});await form.locator('[name="declarationDetails"]').fill('BROWSER-DECL-268');await form.getByRole('button',{name:'Завершить работы'}).click();
 await page.waitForURL('**/pilot/objects/4512#completion');await page.goBack();await page.waitForURL('**/pilot/completion-register?mode=pto_without_declaration&q=TEST&page=1');
 check(!(await page.content()).includes('/pilot/objects/4512#completion'),'object left queue after existing writer');await page.screenshot({path:input.artifacts+'/completion-register-desktop.png',fullPage:true});await desktop.context.close();
 const narrow=await context(390),mobile=narrow.page;await mobile.goto(input.url+'/pilot/completion-register?mode=complete&q=BROWSER-DECL-268&page=1');
 check(await mobile.locator('[data-object-id="4512"]').isVisible(),'saved declaration searchable on narrow');
 check(await mobile.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'narrow shell no viewport overflow');
 const wrap=mobile.locator('.shlz-table-wrap');check(await wrap.evaluate(e=>getComputedStyle(e).overflowX==='auto'&&e.scrollWidth>e.clientWidth),'narrow table has local scrolling');await wrap.evaluate(e=>{e.scrollLeft=e.scrollWidth});
 const action=mobile.locator('[data-object-id="4512"] td:last-child a');await action.scrollIntoViewIfNeeded();check(await action.isVisible(),'narrow action reachable after local scroll');
 const nav=mobile.locator('.fm2-sidebar'),actionBox=await action.boundingBox(),navBox=await nav.boundingBox();check(actionBox&&navBox&&actionBox.y+actionBox.height<=navBox.y,'fixed navigation does not obscure row action');
 const search=mobile.locator('input[name="q"]');await search.focus();check(await search.evaluate(e=>document.activeElement===e),'visible keyboard focus target');
 await mobile.screenshot({path:input.artifacts+'/completion-register-narrow.png',fullPage:false});await narrow.context.close();
 console.log('PASS completion register desktop/narrow journey');
}finally{await browser.close();}
