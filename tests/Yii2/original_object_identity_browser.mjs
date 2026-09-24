import fs from 'node:fs';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url),config=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
const {chromium}=require(config.playwright),browser=await chromium.launch({headless:true});
const expected={
  initial:['TEST-4512','Москва, Тестовая, 1','Подъезд 2','Заводской номер: Z-77'],
  manual:['РЕГ-&-НОВЫЙ','Очень длинный <script>alert(1)</script> & адрес объекта монтажа','Подъезд 12А','Заводской номер: ЗАВ-<42>'],
  missing:['Регистрационный номер не указан','Адрес без номера','Подъезд 7','Заводской номер не указан'],
}[config.mode];
const check=(value,message)=>{if(!value)throw new Error(message);};
try {
  const context=await browser.newContext({viewport:{width:1440,height:900}}),page=await context.newPage();
  await page.goto(config.origin+'/pilot/login');await page.locator('[name=email]').fill(config.email);await Promise.all([page.waitForNavigation(),page.locator('[name=email]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);await page.locator('[name=password]').fill(config.password);await Promise.all([page.waitForNavigation(),page.locator('[name=password]').locator('xpath=ancestor::form').locator('button[type=submit]').click()]);
  const suffix=config.surface==='form'?'/submit':'/history';const url=config.origin+'/pilot/objects/4512/assignment-orders/81/originals'+suffix;let response=await page.goto(url);check(response.status()===200,'original identity surface status');
  const verify=async(width,height)=>{await page.setViewportSize({width,height});for(const text of expected)check(await page.getByText(text,{exact:false}).first().isVisible(),'visible '+text+' at '+width);check(!await page.getByText(/Объект(?: монтажа)? № 4512/).count(),'no internal ID label at '+width);check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'no page overflow '+width);await page.screenshot({path:`${config.artifacts}/original-identity-${config.mode}-${width}.png`,fullPage:true});return true;};
  const desktop=await verify(1440,900),narrow=await verify(320,568);const hostileElement=await page.locator('script').evaluateAll(nodes=>nodes.some(node=>node.textContent.includes('alert(1)')));
  fs.writeFileSync(config.result,JSON.stringify({mode:config.mode,surface:config.surface,desktop,narrow,hostileElement,screenshots:[1440,320]}));
} finally {await browser.close();}
