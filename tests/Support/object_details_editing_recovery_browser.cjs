const [base,cookiesJson,moduleRoot]=process.argv.slice(2);
const{chromium}=require(moduleRoot);
(async()=>{
  const browser=await chromium.launch({headless:true});
  try{
    const context=await browser.newContext();
    const cookies=JSON.parse(cookiesJson);
    await context.addCookies(Object.entries(cookies).map(([name,value])=>({name,value,url:base+'/pilot/'})));
    const page=await context.newPage();page.setDefaultTimeout(3000);
    const posts=[];page.on('request',request=>{if(request.method()==='POST'&&request.url().endsWith('/pilot/objects/4512/details'))posts.push(new URLSearchParams(request.postData()||''));});
    const trigger=page.getByRole('button',{name:'Редактировать данные объекта'});
    const dialog=page.getByRole('dialog',{name:'Редактировать данные объекта'});
    const choose=async label=>{await dialog.getByRole('combobox',{name:/Материал шахты/}).click();await dialog.getByRole('option',{name:label,exact:true}).click();};
    const patchKeys=data=>[...data.keys()].filter(key=>!['_csrf','requestId','expectedRevision'].includes(key)).sort();
    await page.goto(base+'/pilot/objects/4512');

    await trigger.click();await dialog.getByLabel('Заводской номер').fill('CANCELLED');await dialog.getByRole('button',{name:'Отмена'}).click();
    await trigger.click();await choose('Кирпич');await dialog.getByRole('button',{name:'Сохранить'}).click();await page.waitForLoadState('domcontentloaded');
    const cancelPatch=patchKeys(posts.at(-1));await page.getByRole('tab',{name:'История'}).click();
    const cancelPreserved=await page.getByText(/Заводской номер:.*CANCELLED/).count()===0&&await page.getByText(/Материал шахты:.*Кирпич/).count()>0;

    await trigger.click();await choose('Кирпич и металл');await dialog.getByLabel('Этажность').fill('bad');await dialog.getByRole('button',{name:'Сохранить'}).click();await page.waitForLoadState('domcontentloaded');
    const validationShown=await dialog.isVisible()&&await dialog.locator('[aria-invalid="true"]').count()>0;
    await dialog.getByLabel('Этажность').fill('10');await dialog.getByRole('button',{name:'Сохранить'}).click();await page.waitForLoadState('domcontentloaded');
    const retryPatch=patchKeys(posts.at(-1));await page.getByRole('tab',{name:'История'}).click();
    const retryPreserved=await page.getByText(/Материал шахты:.*Кирпич и металл/).count()>0&&await page.getByText(/Этажность:.*10/).count()>0;

    await trigger.click();await dialog.getByLabel('Заводской номер').fill('00123-А');await dialog.getByRole('button',{name:'Сохранить'}).click();await page.waitForLoadState('domcontentloaded');
    const textPatch=patchKeys(posts.at(-1));await page.getByRole('tab',{name:'История'}).click();const textHistory=await page.getByText(/Заводской номер:.*00123-А/).count()>0;
    process.stdout.write(JSON.stringify({cancelPatch,cancelPreserved,validationShown,retryPatch,retryPreserved,textPatch,textHistory}));
  }finally{await browser.close();}
})().catch(error=>{process.stderr.write(error.stack||String(error));process.exit(1)});
