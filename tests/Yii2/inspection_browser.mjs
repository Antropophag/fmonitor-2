import fs from 'node:fs';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url),c=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const {chromium}=require(c.playwright),browser=await chromium.launch({headless:true});
const check=(v,m)=>{if(!v)throw new Error(m);};
try {
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage(),errors=[],assetErrors=[];
 page.on('pageerror',e=>errors.push(e.message));page.on('response',r=>{if(new URL(r.url()).pathname.startsWith('/pilot/assets/')&&r.status()>=400)assetErrors.push(r.url());});
 async function login(email){await page.goto(c.origin+'/pilot/login');await page.locator('[name=email]').fill(email);await page.locator('[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click();await page.locator('[name=password]').fill(c.password);await Promise.all([page.waitForResponse(r=>r.url().endsWith('/pilot/login')&&r.status()===303),page.locator('[name=password]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);}
 await login(c.email);
 const response=await page.goto(c.origin+'/pilot/objects/4512/checklist');check(response.status()===200,'INTENDED_RED Yii browser checklist');
 check(!response.headers()['content-security-policy'].includes('unsafe-inline'),'CSP no unsafe-inline');
 await page.waitForFunction(()=>document.querySelector('[data-checklist]')?.dataset.userId==='73');
 await page.locator('[data-check-section="1"] .fm2-section-toggle').click();
 let lost=false,acceptedId=null;
 await page.route('**/pilot/objects/4512/checklist/operations',async route=>{const data=route.request().postDataJSON();if(!lost&&data.type==='item_completed'){const answer=await route.fetch();const body=await answer.json();check(body.status==='accepted','first click accepted before response loss');lost=true;acceptedId=data.clientOperationId;await route.abort('connectionreset');}else await route.continue();});
 await page.locator('[data-check-item="28"] .fm2-check-toggle').click();
 await page.waitForFunction(()=>document.querySelector('[data-sync-now]')&&!document.querySelector('[data-sync-now]').hidden);
 const replay=page.waitForResponse(r=>r.url().endsWith('/checklist/operations')&&r.status()===200);
 await page.locator('[data-sync-now]').click();check((await (await replay).json()).status==='duplicate','retry same accepted operation');
 await page.unroute('**/pilot/objects/4512/checklist/operations');
 await page.reload();await page.waitForFunction(()=>document.querySelector('[data-check-item="28"] [role=checkbox]')?.getAttribute('aria-checked')==='true');
 await page.screenshot({path:c.artifacts+'/inspection-desktop.png',fullPage:true});
 await page.setViewportSize({width:390,height:844});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'mobile no overflow');await page.screenshot({path:c.artifacts+'/inspection-mobile.png',fullPage:true});
 // Preserve real stores/scope, failed intentions, mixed operation/photo/section ordering.
 const localRows=()=>page.evaluate(()=>new Promise((resolve,reject)=>{const r=indexedDB.open('fmonitor2-fast-pilot',2);r.onerror=()=>reject(r.error);r.onsuccess=()=>{const db=r.result,t=db.transaction(['operations','meta','photoBlobs']),q=t.objectStore('operations').getAll(),d=t.objectStore('meta').get('deviceInstallationId'),p=t.objectStore('photoBlobs').getAll();t.oncomplete=()=>{resolve({stores:[...db.objectStoreNames],rows:q.result,device:d.result,photos:p.result});db.close();};};}));
 const pollLocal=async(predicate,message,timeout=30000)=>{const deadline=Date.now()+timeout;let current;do{current=await localRows();if(predicate(current))return current;await new Promise(resolve=>setTimeout(resolve,50));}while(Date.now()<deadline);throw new Error(message+'; observed '+JSON.stringify(current.rows.map(r=>({id:r.clientOperationId,type:r.type,status:r.status}))));};
 let local=await localRows();check(JSON.stringify(local.stores.sort())===JSON.stringify(['meta','operations','photoBlobs']),'exact IDB v2 stores');check(local.rows.every(r=>r.scope===`73:${r.deviceInstallationId}:4512`),'user device object scope');
 await page.locator('[data-check-section="1"] .fm2-section-toggle').click();
 await context.setOffline(true);await page.locator('[data-check-item="29"] .fm2-check-toggle').click();
 await pollLocal(s=>s.rows.some(r=>r.itemId===29),'offline item durably queued');
 const offlineId=(await localRows()).rows.find(r=>r.itemId===29).clientOperationId;
 const failures=[{status:'conflict',code:409},{status:'rejected',code:422},{status:'retryable',code:503}];let injected=0;
 await page.route('**/pilot/objects/4512/checklist/operations',async route=>{const op=route.request().postDataJSON();if(op.clientOperationId===offlineId&&injected<failures.length){const failure=failures[injected++];await route.fulfill({status:failure.code,contentType:'application/json',body:JSON.stringify({status:failure.status,revision:1})});}else await route.continue();});
 await context.setOffline(false);
 for(let i=0;i<3;i++){const expected=['conflict','rejected','retryable_error'][i];await pollLocal(s=>s.rows.some(r=>r.clientOperationId===offlineId&&r.status===expected),'failed intention retained as '+expected);check((await localRows()).rows.some(r=>r.clientOperationId===offlineId),'failed intention retained');await page.locator('[data-sync-now]').click();}
 await pollLocal(s=>s.rows.some(r=>r.clientOperationId===offlineId&&r.status==='accepted'),'retried item accepted');
 await page.unroute('**/pilot/objects/4512/checklist/operations');await page.reload();await page.waitForFunction(()=>document.querySelector('[data-check-item="29"] [role=checkbox]')?.getAttribute('aria-checked')==='true');
 // Queue the remaining section and photo while offline; automatic section completion follows them.
 if(await page.locator('[data-check-section="1"] .fm2-section-toggle').getAttribute('aria-expanded')==='false')await page.locator('[data-check-section="1"] .fm2-section-toggle').click();
 const sent=[];page.on('request',r=>{if(r.method()==='POST'&&r.url().includes('/checklist/')){if(r.url().endsWith('/photos'))sent.push('photo_uploaded');else sent.push(r.postDataJSON().type);}});
 await context.setOffline(true);await page.locator('[data-check-section="1"] [data-check-all]').click();await page.locator('[data-bulk-confirm]').click();
 await page.locator('[data-check-section="1"] input[data-photo-input]').last().setInputFiles({name:'offline.png',mimeType:'image/png',buffer:Buffer.from(c.png,'base64')});
 await pollLocal(s=>s.photos.length>0,'offline photo bytes durably queued');
 await context.setOffline(false);await pollLocal(s=>s.rows.some(r=>r.type==='section_completed'&&r.status==='accepted'),'mixed section actually accepted');
 check(sent.includes('photo_uploaded')&&sent.includes('section_completed'),'mixed photo and section actually sent');check(sent.indexOf('photo_uploaded')>sent.lastIndexOf('item_completed')&&sent.indexOf('section_completed')>sent.indexOf('photo_uploaded'),'ordered item then photo then section');
 await page.reload();check(await page.locator('[data-check-section="1"] [data-section-state]').textContent().then(x=>x.includes('Заверш')),'section persisted on refresh');
 // The existing sync-context endpoint returns a usable fresh native CSRF token.
 const sync=await page.evaluate(async()=>{const r=await fetch('/pilot/construction-control/objects/4512/sync-context',{credentials:'include'});return {status:r.status,body:await r.json()};});check(sync.status===200&&typeof sync.body.csrf==='string'&&sync.body.revision===11,'sync context revision/token after mixed queue');
 await page.locator('.fm2-back-link[href="/pilot/objects/4512"]').click();await page.waitForURL(c.origin+'/pilot/objects/4512');
 await page.goto(c.origin+'/pilot/construction-control/objects/4512/checklist');await page.locator('.fm2-back-link[href="/pilot/construction-control"]').click();await page.waitForURL(c.origin+'/pilot/construction-control');
 check(await page.getByRole('navigation',{name:'Основная навигация'}).getByRole('link',{name:'Стройконтроль',exact:true}).count()===1,'capability-gated shell entry to construction queue');
 const queueRows=page.locator('[data-control-row]');check(JSON.stringify(await queueRows.evaluateAll(rows=>rows.map(r=>Number(r.dataset.objectId))))===JSON.stringify([4513,4514,4512]),'only-working queue sorted no activity first');
 check(await page.locator('[data-control-row][data-object-id="4513"]').getAttribute('data-engineer-id')==='94','legacy engineer fallback without application');
 check(await page.locator('[data-control-row][data-object-id="4514"]').getAttribute('data-engineer-id')==='73','legacy engineer fallback for completed case');
 check(await page.locator('[data-control-row][data-object-id="4512"]').getAttribute('data-engineer-id')==='73','latest application engineer overrides legacy field');
 check(await page.locator('[data-control-row]:visible').count()===1,'mine filter excludes other engineer and completed');await page.locator('[name=ownership][value=all]').check();check(await page.locator('[data-control-row]:visible').count()===2,'all includes colleague');await page.locator('[data-show-completed]').check();check(await page.locator('[data-control-row]:visible').count()===3,'completed flag includes closed row');await page.locator('[data-control-search]').fill('QUEUE-4514');check(await page.locator('[data-control-row]:visible').count()===1,'literal search');await page.locator('[data-control-search]').fill('NO-SUCH-OBJECT');check(await page.locator('[data-control-empty]').isVisible(),'empty filter state');await page.locator('[data-clear-filters]').click();
 await page.getByRole('button',{name:'Выйти',exact:true}).click();await page.waitForURL(c.origin+'/pilot/login');await login(c.otherEmail);
 const denied=await page.goto(c.origin+'/pilot/objects/4512/checklist');check(denied.status()===403,'different account cannot read cached engineer page');
 await context.setOffline(true);await page.goto(c.origin+'/pilot/objects/4512/checklist').catch(()=>{});check(await page.locator('[data-checklist][data-user-id="73"]').count()===0,'offline cache does not reveal previous account');await context.setOffline(false);
 check(errors.length===0,'no browser exceptions '+errors.join(','));check(assetErrors.length===0,'all assets loaded '+assetErrors.join(','));
 fs.writeFileSync(c.result,JSON.stringify({lost,acceptedId,offline:true}));
}finally{await browser.close();}
