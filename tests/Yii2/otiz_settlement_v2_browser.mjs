import fs from "node:fs";
import path from "node:path";
import assert from "node:assert/strict";
import { createRequire } from "node:module";
import { verifyReferenceTable, openRowDecision, openCommonDeduction } from "./otiz_v2_table_assertions.mjs";
import { verifyDraftWorkflow, verifyUnsavedApproval } from "./otiz_v2_acceptance_assertions.mjs";
import { verifyOtizLayout, verifyOtizRegisterLayouts } from "./otiz_v2_layout_assertions.mjs";
const c = JSON.parse(fs.readFileSync(process.argv[2], "utf8"));
const { chromium } = createRequire(import.meta.url)(c.playwright);
const result = { pageErrors: [], failedResponses: [] };
let browser;
try {
  browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    acceptDownloads: true,
    viewport: { width: 1440, height: 1000 },
  });
  const page = await context.newPage();
  page.setDefaultTimeout(7000);
  page.on("pageerror", (e) => result.pageErrors.push(e.message));
  page.on("response", (r) => {
    if (r.status() >= 500)
      result.failedResponses.push([new URL(r.url()).pathname, r.status()]);
  });
  await page.goto(c.origin + "/pilot/otiz/payments");
  assert.equal(new URL(page.url()).pathname, "/pilot/otiz/login");
  await page.locator("input[name=email]").fill(c.email);
  await Promise.all([
    page.waitForNavigation(),
    page.locator("button[type=submit]").click(),
  ]);
  await page.locator("input[name=password]").fill(c.password);
  await Promise.all([
    page.waitForNavigation(),
    page.locator("button[type=submit]").click(),
  ]);
  assert.equal(new URL(page.url()).pathname, "/pilot/otiz/payments");
  await verifyOtizRegisterLayouts(page, c);
  await page.getByRole("link", { name: "Ожидают выплаты" }).waitFor();
  assert.equal(await page.getByRole("link", { name: "История" }).count(), 1);
  assert.equal(
    (await page.getByRole("button", { name: "Новый расчёт" }).count()) +
      (await page.getByRole("link", { name: "Новый расчёт" }).count()),
    1,
  );
  await page.goto(c.origin + `/pilot/otiz/calculations/${c.draft.calculationId}`);
  assert.equal(await page.locator('[data-grouping="objects"] > .shlz-table-wrap > table table').count(), 0, 'reference object expansion shares one table column grid, never a nested mini-table');
  assert.equal(await page.locator('[data-grouping="employees"] > .shlz-table-wrap > table table').count(), 0, 'reference employee expansion shares one table column grid');
  await verifyReferenceTable(page, c);
  await verifyDraftWorkflow(page, c);
  const draftId = c.draft?.calculationId;
  assert.ok(draftId, "root fixture created a real draft");
  await page.goto(c.origin + `/pilot/otiz/calculations/${draftId}`);
  await verifyOtizLayout(page, "saved calculation detail");
  const totals = page.getByRole("region", { name: "Итоги всего расчёта" });
  await totals.waitFor();
  assert.match(await totals.innerText(), /Предварительно к выплате/);
  assert.match((await totals.innerText()).replace(/[\s\u00a0\u202f]/g, ""), /15000,00/);
  for (const action of ["Обновить черновик", "Удалить черновик"])
    assert.equal(
      await page.getByRole("button", { name: action, exact: true }).count(),
      1,
      "draft exposes lifecycle action " + action,
    );
  const objectTab = page.getByRole("radio", { name: "По объектам" });
  const employeeTab = page.getByRole("radio", { name: "По монтажникам" });
  await objectTab.click();
  const toggles = page.locator("[data-otiz-group-toggle]");
  assert.equal(
    await toggles.count(),
    2,
    "two object groups use one immutable revision",
  );
  await toggles.first().focus();
  await page.keyboard.press("Enter");
  await toggles.nth(1).click();
  assert.deepEqual(
    await toggles.evaluateAll((nodes) =>
      nodes.map((n) => n.getAttribute("aria-expanded")),
    ),
    ["true", "true"],
    "multiple object groups remain expanded",
  );
  const objectDetails = await page
    .locator('[data-grouping="objects"]')
    .innerText();
  const proof = page.locator('details[data-object-basis-summary]');
  await proof.locator('summary').click();
  const basisText = await proof.innerText();
  for(const label of ['Учтено ранее','Подтверждено','Объём этого расчёта','До уменьшений','За сроки','Общее удержание','Личные удержания'])assert.ok(basisText.includes(label),'expanded proof explains actual saved monetary basis '+label);
  assert.match(basisText, /9[\s\u00a0]000,00/);
  assert.match(basisText, /6[\s\u00a0]000,00/);
  await proof.locator('summary').click();
  for (const label of ['Объект / получатель','Объём / доля','До уменьшений','Уменьшение','К выплате','Уволен']) assert.ok(objectDetails.includes(label), 'reference object table explains '+label);
  assert.equal(await page.locator('[data-grouping="objects"] [data-v2-deduction-open][data-employee-id=""]').count(),2,'common deduction action on each object');
  await employeeTab.click();
  const employeeToggles = page.locator("[data-otiz-employee-toggle]");
  for (let i = 0; i < (await employeeToggles.count()); i++)
    await employeeToggles.nth(i).click();
  const employeeDetails = await page
    .locator('[data-grouping="employees"]')
    .innerText();
  assert.match(employeeDetails, /Сидоров Сергей/);
  for (const label of ['Монтажник / объект','Объектов','Доля по объекту','К выплате','Уволен']) assert.ok(employeeDetails.includes(label), 'reference employee table explains '+label);
  assert.ok(
    (await page.locator("[data-otiz-employee-group]").count()) >= 3,
    "employee grouping has stable recipient parents",
  );
  assert.ok(
    (await page.locator("[data-object-contribution]").count()) >= 4,
    "employee grouping exposes per-object child contributions",
  );
  await openRowDecision(page);
  const decisionForm = page.locator('form[action$="/decisions"]').first();
  await decisionForm.getByRole("radio", { name: "Не платить" }).check();
  await decisionForm.getByLabel(/Основание решения/).fill("Browser decision");
  await decisionForm.getByRole("button", { name: "Сохранить решение" }).click();
  await openCommonDeduction(page);
  const deductionForm = page.locator('form[action$="/deductions"]').first();
  await deductionForm.getByLabel(/Сумма удержания/).fill("1 000,00");
  await deductionForm.getByLabel(/Причина/).fill("Browser deduction");
  await deductionForm
    .getByRole("button", { name: /Добавить удержание/ })
    .click();
  await page.screenshot({
    path: path.join(c.artifacts, "desktop.png"),
    fullPage: true,
  });
  await page.getByRole('radio',{name:'По объектам'}).click();
  for(const toggle of await page.locator('[data-grouping="objects"] [data-otiz-group-toggle]').all())if(await toggle.getAttribute('aria-expanded')==='false')await toggle.click();
  await page.screenshot({path:path.join(c.artifacts,'objects-expanded.png'),fullPage:true});
  const downloadPromise = page.waitForEvent("download");
  await page.getByRole("link", { name: "Скачать Excel" }).click();
  const download = await downloadPromise;
  await download.saveAs(path.join(c.artifacts, "settlement.xlsx"));
  await page
    .getByRole("button", { name: "Утвердить расчёт", exact: true })
    .click();
  await Promise.all([
    page.waitForNavigation(),
    page
      .getByRole("button", { name: "Подтвердить утверждение", exact: true })
      .click(),
  ]);
  assert.equal(
    new URL(page.url()).pathname,
    `/pilot/otiz/calculations/${draftId}`,
    "acceptance remains on canonical v2 calculation route",
  );
  for (const action of ["Отменить расчёт", "Заменить расчёт"])
    assert.equal(
      await page.getByRole("button", { name: action, exact: true }).count(),
      1,
      "unpaid acceptance exposes lifecycle action " + action,
    );
  fs.writeFileSync(
    path.join(c.artifacts, "accepted.html"),
    await page.content(),
  );
  for(const [action,kind]of [['Отменить расчёт','cancel'],['Заменить расчёт','replace']]){
    await page.getByRole('button',{name:action,exact:true}).click();
    const dialog=page.locator(`[data-v2-lifecycle-dialog="${kind}"]`);await dialog.waitFor({state:'visible'});
    assert.equal(await dialog.locator('input[name="reason"][type="hidden"]').count(),0,'lifecycle has no fabricated hidden reason');
    assert.equal(await dialog.getByLabel(/Причина/).getAttribute('required')!==null,true,'lifecycle requires entered reason');
    assert.ok((await dialog.innerText()).includes('#'+draftId),'lifecycle confirmation names exact calculation');
    await page.keyboard.press('Escape');assert.equal(await dialog.isVisible(),false,'dismissed confirmation is neutral');
  }
  assert.equal(await page.locator('a[href$="export.xlsx?mode=payment"]').count(),1,'accepted unpaid has explicit current payment export');
  assert.equal(await page.locator('a[href$="export.xlsx?mode=history"]').count(),1,'saved history remains separately available');
  const paymentDownloadPromise=page.waitForEvent('download');
  await page.locator('a[href$="export.xlsx?mode=payment"]').click();
  await (await paymentDownloadPromise).saveAs(path.join(c.artifacts,'payment.xlsx'));
  await page
    .getByRole("button", { name: "Отметить выплату", exact: true })
    .click();
  const confirmation=page.locator('[data-v2-payment-dialog] [data-confirm-summary]');
  const confirmationText=(await confirmation.innerText()).replace(/\u00a0/g,' ');
  for(const text of ['#'+draftId,'26.09.2026','2 объекта','14 000,00','вне FMonitor'])assert.ok(confirmationText.includes(text),'whole payment scope visible: '+text);
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 900 });
    const modal = page.locator('[data-v2-payment-dialog]');
    const cancel = await modal.getByRole('button', {name:'Отмена',exact:true}).boundingBox();
    const confirm = await modal.getByRole('button', {name:'Подтвердить выплату',exact:true}).boundingBox();
    const surface = await modal.locator('.shlz-modal__surface').boundingBox();
    assert.ok(Math.max(confirm.x-cancel.x-cancel.width,cancel.x-confirm.x-confirm.width,confirm.y-cancel.y-cancel.height,cancel.y-confirm.y-confirm.height)>=8,'payment buttons are separated');
    for (const box of [cancel,confirm]) assert.ok(box.x>=surface.x+12 && box.x+box.width<=surface.x+surface.width-12 && box.y+box.height<=surface.y+surface.height-12,'payment actions have surface padding');
    await modal.locator('[data-shlz-popover-trigger]').click();
    await page.waitForFunction(()=>document.querySelector('[data-v2-payment-dialog] .shlz-date-picker__popover')?.style.position==='fixed');
    const calendar = modal.locator('.shlz-date-picker__popover');
    const geometry = await calendar.evaluate(el=>{const b=el.getBoundingClientRect();return {left:b.left,right:b.right,top:b.top,bottom:b.bottom,width:b.width,title:el.querySelector('.shlz-calendar__title').getBoundingClientRect().height,days:[...el.querySelectorAll('.shlz-calendar__day')].map(e=>{const r=e.getBoundingClientRect();return{left:r.left,right:r.right,width:r.width};})};});
    assert.ok(geometry.width>=278&&geometry.width<=282,'public calendar is280px');
    assert.ok(geometry.left>=7&&geometry.right<=width-7&&geometry.top>=7&&geometry.bottom<=893,'calendar stays inside viewport');
    assert.ok(geometry.title<=20&&geometry.days.every(d=>d.left>=geometry.left&&d.right<=geometry.right&&d.width<=31),'calendar typography and all7 columns remain intact');
    await page.screenshot({path:path.join(c.artifacts,`payment-calendar-${width}.png`)});
    await calendar.locator('.shlz-calendar__day[data-in-month="true"]').first().click();
  }
  await page.setViewportSize({width:1440,height:1000});
  const visiblePaymentDate = page.getByLabel("Дата", { exact: true });
  assert.equal(
    await visiblePaymentDate.count(),
    1,
    "one visible shlz payment-date control at " + page.url(),
  );
  await visiblePaymentDate.fill("25.09.2026");
  await visiblePaymentDate.press("Tab");
  const paymentDate = page.locator(
    'form[data-v2-payment-form] input[type="hidden"][name="paymentDate"]',
  );
  assert.equal(
    await paymentDate.inputValue(),
    "2026-09-25",
    "shlz date control retains exact ISO payment date before confirmation",
  );
  await Promise.all([
    page.waitForNavigation(),
    page
      .getByRole("button", { name: "Подтвердить выплату", exact: true })
      .click(),
  ]);
  assert.match(await page.locator("main").innerText(), /Выплата отмечена/);
  assert.equal(await page.locator('a[href$="export.xlsx?mode=payment"]').count(),0,'paid history is not another payment instruction');
  assert.equal(await page.locator('input[value="financial"]').count(),0,'no invented financial refund action');
  assert.equal(
    await page
      .getByRole("button", { name: "Отменить отметку выплаты", exact: true })
      .count(),
    1,
    "paid calculation exposes reversal action",
  );
  await page.getByRole('button',{name:'Отменить отметку выплаты',exact:true}).click();
  const reversal=page.locator('[data-v2-reversal-dialog]');
  await reversal.waitFor({state:'visible'});
  assert.match(await reversal.innerText(),/ошибочн/i);
  assert.match(await reversal.innerText(),/перечисления.*не было/i);
  assert.equal(await reversal.locator('[name="kind"]').inputValue(),'erroneous_mark','posted operation explicitly voids an erroneous mark');
  assert.equal(await reversal.locator('[name="reason"]').getAttribute('required')!==null,true,'reason is required');
  await reversal.getByLabel(/Причина/).fill('Browser: перечисления не было');
  await reversal.getByRole('checkbox',{name:/перечисления.*не было/i}).check();
  await Promise.all([page.waitForNavigation(),reversal.getByRole('button',{name:'Подтвердить отмену отметки',exact:true}).click()]);
  assert.equal(await page.getByRole('button',{name:'Отметить выплату',exact:true}).count(),1,'same obligation awaits payment again');
  const audit=page.locator('[data-calculation-history]');
  await audit.locator('summary').click();
  assert.match(await audit.innerText(),/Выплата отмечена/);
  assert.match(await audit.innerText(),/Browser: перечисления не было/,'original payment and reasoned cancellation stay in history');
  await page.goto(c.origin+'/pilot/otiz/payments?filter=waiting&year=all');
  assert.equal(await page.locator(`a[href="/pilot/otiz/calculations/${draftId}"]`).count(),1,'reversed calculation is back in payable queue');
  await page.goto(c.origin+`/pilot/otiz/calculations/${draftId}`);
  await page.setViewportSize({ width: 390, height: 844 });
  await page.reload();
  await page.screenshot({
    path: path.join(c.artifacts, "narrow.png"),
    fullPage: true,
  });
  const overflow = await page.evaluate(
    () =>
      document.documentElement.scrollWidth -
      document.documentElement.clientWidth,
  );
  assert.ok(overflow <= 1, "no body overflow at 390px");
  assert.ok(
    await page.getByRole("link", { name: "Скачать Excel" }).isVisible(),
  );
  await verifyUnsavedApproval(page, c);
  fs.writeFileSync(
    path.join(c.artifacts, "result.json"),
    JSON.stringify(result),
  );
} finally {
  if (browser) await browser.close();
}
