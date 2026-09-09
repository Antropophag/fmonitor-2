import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const [port,moduleRoot,cookieValue,resultPath]=process.argv.slice(2);
const {chromium}=createRequire(import.meta.url)(moduleRoot);const base=`http://127.0.0.1:${port}`;let browser;const result={errors:[]};
try{
  browser=await chromium.launch({headless:true,channel:'chromium'});const context=await browser.newContext({acceptDownloads:true});
  await context.addCookies([{name:`fm2auth_${port}`,value:cookieValue,url:base,httpOnly:true,sameSite:'Strict'}]);const page=await context.newPage();
  page.on('console',m=>{if(m.type()==='error')result.errors.push(m.text());});page.on('pageerror',e=>result.errors.push(e.message));
  const response=await page.goto(`${base}/pilot/objects/4512`);if(response?.status()!==200||page.url().includes('/pilot/login'))throw new Error('persisted authenticated cookie rejected after restart');
  const progress=await page.getByRole('progressbar',{name:'Готовность работ'}).getAttribute('aria-valuenow');if(progress!=='100')throw new Error(`persisted completion projection ${progress}`);
  const href=await page.locator('a[href*="/originals/"][href$="/download"]').first().getAttribute('href');if(!href)throw new Error('authorized original link absent after restart');
  const original=await context.request.get(base+href);const bytes=await original.body();if(original.status()!==200||!bytes.subarray(0,5).equals(Buffer.from('%PDF-')))throw new Error('authorized private PDF unavailable after restart');
  await page.locator('[data-fm2-preloader]').waitFor({state:'detached',timeout:10000});
  await page.screenshot({path:path.join(path.dirname(resultPath),'completed-after-restart.png'),fullPage:true});
  result.progress=progress;result.originalBytes=bytes.length;result.success=true;fs.writeFileSync(resultPath,JSON.stringify(result),{mode:0o600});
}catch(error){result.failure=error.stack||String(error);fs.writeFileSync(resultPath,JSON.stringify(result),{mode:0o600});throw error;}finally{if(browser)await browser.close();}
