import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const [port,modulePath,artifacts,resultPath,configuredReportDate,configuredEmail,configuredPassword]=process.argv.slice(2);
const reportDate=configuredReportDate||'2026-09-08';
const email=configuredEmail||'test31@shlz.ru';
const password=configuredPassword||'Synthetic-Otiz-Browser-2026!';
const require=createRequire(import.meta.url);
const {chromium}=require(modulePath);
const result={consoleErrors:[],pageErrors:[],failedRequests:[],responses:[]};
let browser;
try {
  browser=await chromium.launch({headless:true});
  const context=await browser.newContext({acceptDownloads:true});
  const page=await context.newPage();
  page.setDefaultTimeout(15000);
  await page.goto(`http://127.0.0.1:${port}/pilot/login`);
  await page.locator('input[name="email"]').fill(email);
  await page.locator('button[type="submit"]').click();
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/pilot\/objects/);
  await page.goto(`http://127.0.0.1:${port}/pilot/otiz/payments`);
  if(new URL(page.url()).pathname==='/pilot/otiz/login'){
    await page.locator('input[name="email"]').fill(email);await page.locator('button[type="submit"]').click();
    await page.locator('input[name="password"]').fill(password);await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/pilot\/otiz\/payments/);
  }
  page.on('console',message=>{if(message.type()==='error')result.consoleErrors.push(message.text());});
  page.on('pageerror',error=>result.pageErrors.push(error.message));
  page.on('requestfailed',request=>result.failedRequests.push({url:new URL(request.url()).pathname,error:request.failure()?.errorText||''}));
  page.on('response',response=>{if(response.status()>=400)result.responses.push({url:new URL(response.url()).pathname,status:response.status()});});
  result.paymentUrl=page.url();result.paymentText=(await page.locator('body').innerText()).slice(0,500);
  const createForm=page.locator('form[action="/pilot/otiz/calculations"]');
  const operation=await createForm.locator('input[name="operationId"]').inputValue();
  result.operationIdPresent=/^[a-f0-9-]{36}$/.test(operation);
  const [year,month,day]=reportDate.split('-');await createForm.getByRole('textbox',{name:'Расчётная дата',exact:true}).fill(`${day}.${month}.${year}`);await createForm.getByRole('textbox',{name:'Расчётная дата',exact:true}).press('Tab');
  await Promise.all([page.waitForNavigation(),createForm.getByRole('button',{name:'Новый расчёт'}).click()]);
  await page.waitForURL(/\/pilot\/otiz\/calculations\/\d+\?created=1/);
  result.snapshotUrl=page.url().replace(`http://127.0.0.1:${port}`,'');
  result.objectRows=await page.locator('[data-grouping="objects"] [data-otiz-parent-row]').count();
  await page.screenshot({path:path.join(artifacts,'draft.png'),fullPage:true});
  const downloadPromise=page.waitForEvent('download');
  const exportResponsePromise=page.waitForResponse(response=>new URL(response.url()).pathname.endsWith('/export.xlsx'));
  await page.getByRole('link',{name:'Скачать Excel'}).click();
  const [download,exportResponse]=await Promise.all([downloadPromise,exportResponsePromise]);const xlsx=path.join(artifacts,'otiz.xlsx');await download.saveAs(xlsx);
  result.xlsxBytes=fs.statSync(xlsx).size;result.xlsxFilename=download.suggestedFilename();result.xlsxContentType=exportResponse.headers()['content-type'];result.xlsxDisposition=exportResponse.headers()['content-disposition'];
  await page.getByRole('button',{name:'Утвердить расчёт',exact:true}).click();
  await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Подтвердить утверждение',exact:true}).click()]);
  await page.screenshot({path:path.join(artifacts,'accepted.png'),fullPage:true});
  result.generatedSettlementCompleted=await page.getByRole('button',{name:'Отметить выплату',exact:true}).count()===1;
  result.consoleErrors=result.consoleErrors.filter(message=>!message.includes('favicon'));
  result.failedRequests=result.failedRequests.filter(item=>!(item.url.endsWith('/export.xlsx')&&item.error.includes('ERR_ABORTED')));
  if(!result.operationIdPresent||!result.generatedSettlementCompleted||result.objectRows<1||result.consoleErrors.length||result.pageErrors.length||result.failedRequests.length||result.responses.length)throw new Error(`browser assertions failed: ${JSON.stringify(result)}`);
  fs.writeFileSync(resultPath,JSON.stringify(result,null,2),{mode:0o600});
  console.log('OTIZ_BROWSER_CLICK_FLOW_OK');
} catch(error) {
  result.failure=error.stack||String(error);fs.writeFileSync(resultPath,JSON.stringify(result,null,2),{mode:0o600});throw error;
} finally {if(browser)await browser.close();}
