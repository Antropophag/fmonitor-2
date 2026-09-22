const [base,cookiesJson,moduleRoot,materialCode,materialLabel]=process.argv.slice(2);
const{chromium}=require(moduleRoot);
(async()=>{
  const browser=await chromium.launch({headless:true});
  try{
    const context=await browser.newContext();
    const cookies=JSON.parse(cookiesJson);
    await context.addCookies(Object.entries(cookies).map(([name,value])=>({name,value,url:base+'/pilot/'})));
    const page=await context.newPage();page.setDefaultTimeout(3000);
    await page.goto(base+'/pilot/objects/4512');
    const trigger=page.getByRole('button',{name:'Редактировать данные объекта'});await trigger.click();
    const dialog=page.getByRole('dialog',{name:'Редактировать данные объекта'});
    const groups=await dialog.locator('fieldset').count();
    const initialFocus=await dialog.evaluate(element=>element.contains(document.activeElement));
    const hasKshah=await dialog.getByLabel('Кшах').count()>0;
    const box=await dialog.getByRole('button',{name:'Закрыть'}).boundingBox();
    await dialog.getByRole('button',{name:'Отмена'}).click();const cancelClosed=!(await dialog.isVisible());
    await trigger.click();await page.keyboard.press('Escape');const escapeClosed=!(await dialog.isVisible());
    await trigger.click();await dialog.click({position:{x:2,y:2}});const backdropClosed=!(await dialog.isVisible());
    await trigger.click();
    let submitted=null;page.on('request',candidate=>{if(candidate.method()==='POST'&&candidate.url().endsWith('/pilot/objects/4512/details'))submitted=new URLSearchParams(candidate.postData()||'');});
    await dialog.getByRole('combobox',{name:/Материал шахты/}).click();
    await dialog.getByRole('option',{name:materialLabel,exact:true}).click();
    await dialog.getByRole('button',{name:'Сохранить'}).click();
    await page.waitForLoadState('domcontentloaded');
    const validationOpen=await dialog.isVisible();let savedHistory=false;
    if(!validationOpen){await page.getByRole('tab',{name:'История'}).click();savedHistory=await page.getByText(new RegExp(`Материал шахты:.*${materialLabel}`)).count()>0;}
    if(submitted===null)throw new Error('details POST was not observed');
    const patchKeys=[...submitted.keys()].filter(key=>!['_csrf','requestId','expectedRevision'].includes(key)).sort();
    process.stdout.write(JSON.stringify({groups,hasKshah,closeTarget:!!box&&box.width>=40&&box.height>=40,cancelClosed,escapeClosed,backdropClosed,initialFocus,savedHistory,validationOpen,patchKeys}));
  }finally{await browser.close();}
})().catch(error=>{process.stderr.write(error.stack||String(error));process.exit(1)});
