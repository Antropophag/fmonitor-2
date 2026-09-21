const [base,cookiesJson,moduleRoot]=process.argv.slice(2);
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
    const hasKshah=await dialog.getByLabel('Кшах').count()>0;
    const box=await dialog.getByRole('button',{name:'Закрыть'}).boundingBox();
    await dialog.getByRole('button',{name:'Отмена'}).click();const cancelClosed=!(await dialog.isVisible());
    await trigger.click();await page.keyboard.press('Escape');const escapeClosed=!(await dialog.isVisible());
    process.stdout.write(JSON.stringify({groups,hasKshah,closeTarget:!!box&&box.width>=40&&box.height>=40,cancelClosed,escapeClosed}));
  }finally{await browser.close();}
})().catch(error=>{process.stderr.write(error.stack||String(error));process.exit(1)});
