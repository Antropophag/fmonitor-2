import fs from 'node:fs';import http from 'node:http';import{createRequire}from'node:module';
const c=JSON.parse(fs.readFileSync(process.argv[2],'utf8')),require=createRequire(import.meta.url),{chromium}=require(c.playwright);
const check=(v,m)=>{if(!v)throw new Error(m);};
const start=()=>chromium.launchPersistentContext(c.profile,{headless:true,args:['--no-sandbox']});
let context=await start(),external=null,attackBrowser=null;
try{
 let page=context.pages()[0]||await context.newPage();await page.goto(c.origin+'/pilot/login');await page.locator('[name=email]').fill(c.email);
 await Promise.all([page.waitForNavigation(),page.locator('[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);await page.locator('[name=password]').fill(c.password);
 await Promise.all([page.waitForNavigation(),page.locator('[name=password]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);check(new URL(page.url()).pathname==='/pilot/objects','actual user signed in');
 const cookie=(await context.cookies()).find(x=>x.name==='fm2yii_preopening');check(Boolean(cookie),'native session cookie exists');
 check(cookie.httpOnly&&cookie.path==='/pilot'&&cookie.expires!==-1,'persistent HttpOnly scoped cookie');
 check(Math.abs(cookie.expires-Date.now()/1000-2592000)<15,'INTENDED_RED: session cookie must last 30 days');
 check(cookie.sameSite==='Lax','INTENDED_RED: external links require SameSite=Lax');
 await context.close();context=await start();page=context.pages()[0]||await context.newPage();await page.goto(c.origin+'/pilot/objects');
 check(new URL(page.url()).pathname==='/pilot/objects','full browser restart retains authenticated session');check((await context.cookies()).find(x=>x.name===cookie.name)?.value===cookie.value,'restart retains same session identity');
 external=http.createServer((q,s)=>{s.setHeader('Content-Type','text/html');s.end('<a id="open" href="'+c.origin+'/pilot/objects">FMonitor</a><form method="post" action="'+c.origin+'/pilot/logout"><button id="attack">Submit</button></form>');});await new Promise(r=>external.listen(0,'127.0.0.1',r));const externalOrigin='http://localhost:'+external.address().port;
 await page.goto(externalOrigin);await Promise.all([page.waitForURL(c.origin+'/pilot/objects'),page.locator('#open').click()]);
 check((await context.cookies()).find(x=>x.name===cookie.name)?.value===cookie.value,'external top-level GET never replaces authenticated session');
 const invalid=await page.request.post(c.origin+'/pilot/logout',{form:{_csrf:'invalid-csrf'},maxRedirects:0});check(invalid.status()===400,'same-origin POST still requires correct CSRF');
 await page.goto(c.origin+'/pilot/objects');check(new URL(page.url()).pathname==='/pilot/objects','invalid CSRF logout leaves current session authenticated');
 // Separate browser clone proves an external POST cannot send the authenticated Lax cookie.
 attackBrowser=await chromium.launch({headless:true,args:['--no-sandbox']});const attackContext=await attackBrowser.newContext({storageState:await context.storageState()}),attackPage=await attackContext.newPage();await attackPage.goto(externalOrigin);
 const attackResponse=attackPage.waitForResponse(r=>r.url()===c.origin+'/pilot/logout'&&r.request().method()==='POST');await attackPage.locator('#attack').click();const denied=await attackResponse;
 const sent=(await denied.request().allHeaders()).cookie||'';check(!sent.includes(cookie.name+'='),'cross-site POST cannot send authenticated session cookie');check([400,403].includes(denied.status()),'cross-site POST is rejected');
 await attackBrowser.close();attackBrowser=null;
 await page.goto(c.origin+'/pilot/objects');check(new URL(page.url()).pathname==='/pilot/objects','unauthenticated external POST cannot destroy original server session');
 const csrf=await page.locator('input[name=_csrf]').first().inputValue();const logout=await page.request.post(c.origin+'/pilot/logout',{form:{_csrf:csrf},maxRedirects:0});check(logout.status()===303,'explicit valid logout succeeds');
 await page.goto(c.origin+'/pilot/objects');check(new URL(page.url()).pathname==='/pilot/login','explicit logout is still enforced');
 console.log('PASS persistent 30-day cookie, restart, external GET and protected POST/logout');
}finally{if(attackBrowser)await attackBrowser.close();if(external)await new Promise(r=>external.close(r));await context.close();}
