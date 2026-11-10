import assert from 'node:assert/strict';
import { openRowDecision, openCommonDeduction, openPersonalDeduction } from './otiz_v2_table_assertions.mjs';
import { verifyOtizLayout } from './otiz_v2_layout_assertions.mjs';

// U01: the browser must confirm exactly the saved revision that the owner accepts.
export async function verifyUnsavedApproval(page, config) {
  await page.setViewportSize({width:1440,height:1000});
  const [unsaved,saving]=config.previewDrafts;
  const url=d=>config.origin+'/pilot/otiz/calculations/'+d.calculationId;
  const form=()=>page.locator('form[data-recovery-deduction]');
  const decision=()=>page.locator('dialog[open] form[data-recovery-decision]');
  const dialog=()=>page.locator('[data-v2-lifecycle-dialog="accept"]');
  const money=s=>s.replace(/\u00a0/g,' ').trim();
  const mainAmounts=async()=>page.locator('[data-grouping="objects"] [data-otiz-allocation-row]').evaluateAll(rows=>Object.fromEntries(rows.map(row=>[row.dataset.employeeId,row.querySelector('[data-allocation-amount]').textContent.replace(/\u00a0/g,' ').trim()])));
  const confirm=async amount=>{if(await page.locator('dialog[open]').count())await page.keyboard.press('Escape');await page.getByRole('button',{name:'Утвердить расчёт',exact:true}).click();await dialog().waitFor({state:'visible'});const surface=await dialog().locator('.shlz-modal__surface').boundingBox();const cancel=await dialog().getByRole('button',{name:'Отмена',exact:true}).boundingBox();const confirm=await dialog().getByRole('button',{name:'Подтвердить утверждение',exact:true}).boundingBox();assert.ok(Math.max(confirm.x-cancel.x-cancel.width,cancel.x-confirm.x-confirm.width,confirm.y-cancel.y-cancel.height,cancel.y-confirm.y-confirm.height)>=8,'approval actions have a gap');for(const box of [cancel,confirm])assert.ok(box.x>=surface.x+12&&box.x+box.width<=surface.x+surface.width-12&&box.y+box.height<=surface.y+surface.height-12,'approval actions have surface padding');assert.ok(money(await dialog().locator('[data-confirm-summary]').innerText()).includes(amount),'U01 browser confirmation uses saved amount, never unsaved preview');};
  const previewDeduction=async()=>{await openCommonDeduction(page);await form().getByLabel(/Сумма удержания/).fill('1 000,00');await form().getByLabel(/Причина/).fill('U01 явное сохранение отдельно');await form().getByRole('button',{name:'Предпросмотр',exact:true}).click();await page.locator('[data-preview-kind="deduction"]').waitFor();assert.equal(await page.locator('[data-preview-kind="deduction"]').getAttribute('data-preview-total-cents'),'1900000');};
  await page.goto(url(unsaved));await previewDeduction();
  await confirm('20 000,00');await page.screenshot({path:config.artifacts+'/u01-unsaved-approval.png',fullPage:true});await page.keyboard.press('Escape');
  assert.deepEqual(await mainAmounts(),{A:'12 000,00 ₽',B:'8 000,00 ₽'},'U01 main recipient table is saved while deduction preview is unsaved');
  await page.goto(url(unsaved));await openRowDecision(page);await decision().getByRole('radio',{name:'Не платить',exact:true}).check();await decision().getByLabel(/Основание решения/).fill('U01 не сохранять решение');await decision().getByRole('button',{name:'Предпросмотр',exact:true}).click();await page.locator('[data-preview-kind="decision"]').waitFor();
  assert.equal(await page.locator('[data-preview-kind="decision"] [data-employee-id="A"]').getAttribute('data-after-cents'),'1200000');assert.deepEqual(await mainAmounts(),{A:'12 000,00 ₽',B:'8 000,00 ₽'},'U01 reserved-share preview never replaces saved approval recipients');
  await confirm('20 000,00');await Promise.all([page.waitForNavigation(),dialog().getByRole('button',{name:'Подтвердить утверждение',exact:true}).click()]);assert.equal(await page.getByRole('button',{name:'Отметить выплату',exact:true}).count(),1,'saved20000 accepted');
  await page.goto(url(saving));await previewDeduction();await confirm('20 000,00');await page.keyboard.press('Escape');if(!await form().isVisible())await openCommonDeduction(page);await form().getByRole('button',{name:'Добавить удержание',exact:true}).click();
  assert.deepEqual(await mainAmounts(),{A:'11 400,00 ₽',B:'7 600,00 ₽'},'explicit save changes the persisted recipient projection');await confirm('19 000,00');await page.screenshot({path:config.artifacts+'/u01-saved-approval.png',fullPage:true});
  const fields=await dialog().locator('form').evaluate(form=>Object.fromEntries(new FormData(form)));
  assert.equal(Number(fields.expectedRevision),Number(saving.revision)+1,'explicit save advances revision');
  const stale=await page.request.post(url(saving)+'/accept',{form:{...fields,expectedRevision:String(saving.revision),operationId:'dddddddd-bbbb-4ddd-8ddd-000000000001'},maxRedirects:0});assert.equal(stale.status(),409,'stale expectedRevision cannot accept after save');
  await Promise.all([page.waitForNavigation(),dialog().getByRole('button',{name:'Подтвердить утверждение',exact:true}).click()]);assert.equal(await page.getByRole('button',{name:'Отметить выплату',exact:true}).count(),1,'fresh saved19000 accepted');
}

// OTIZ-SETTLEMENT-V2-001: agreed user actions on the real Yii runtime.
export async function verifyDraftWorkflow(page, config) {
  const url = config.origin + `/pilot/otiz/calculations/${config.draft.calculationId}`;
  await page.goto(url);
  const navigation=page.getByRole('navigation',{name:'Разделы ОТиЗ'});
  assert.deepEqual((await navigation.getByRole('link').allTextContents()).map(x=>x.trim()).sort(),['Расчёты','Экономика объектов'].sort(),'one register and economy; no duplicate archive section');
  assert.equal(await page.getByRole('button',{name:'Подтвердить утверждение',exact:true}).isVisible(),false,'accept confirmation is not a second always-visible primary action');
  await page.getByRole('button',{name:'Утвердить расчёт',exact:true}).click();
  const acceptDialog=page.locator('[data-v2-lifecycle-dialog="accept"]');await acceptDialog.waitFor({state:'visible'});
  assert.ok((await acceptDialog.innerText()).includes('#'+config.draft.calculationId),'approval preview names exact calculation');
  assert.ok((await acceptDialog.innerText()).includes('15 000,00'),'approval preview shows full immutable sum');
  await page.keyboard.press('Escape');
  await page.getByRole('button',{name:'Удалить черновик',exact:true}).click();
  const deleteDialog=page.locator('[data-v2-lifecycle-dialog="delete"]');
  await deleteDialog.waitFor({state:'visible'});
  assert.equal(await deleteDialog.locator('input[name="reason"][type="hidden"]').count(),0,'deletion has no fabricated fixed reason');
  assert.equal(await deleteDialog.getByLabel(/Причина/).getAttribute('required')!==null,true,'deletion requires explicit reason');
  await page.keyboard.press('Escape');
  assert.equal(await deleteDialog.isVisible(),false,'escape leaves original draft intact');
  const decision = () => page.locator('dialog[open] form[action$="/decisions"]');
  const openDecision = async () => {
    await openRowDecision(page);
    assert.equal(await decision().count(),1,'one opened editor for exact selected pair');assert.equal(await decision().locator('[name=objectId]').inputValue(),'4512');assert.equal(await decision().locator('[name=employeeId]').inputValue(),'B');assert.equal(await page.locator('form[action$="/decisions"]').count(),4,'each of four object/installer pairs has its own editor');
  };
  await openDecision();
  const revision = await decision().locator('[name="expectedRevision"]').inputValue();
  await decision().getByRole('radio', { name: 'Не платить', exact: true }).check();
  await decision().getByLabel(/Основание решения/).fill('Проверка только первого объекта');
  await decision().getByRole('button', { name: 'Предпросмотр', exact: true }).click();
  const preview = page.locator('[data-preview-kind="decision"]');
  await preview.waitFor();
  for(const text of ['014903','Иванов','Сидоров'])assert.ok((await preview.innerText()).includes(text),'preview explains selected object and coworkers '+text);for(const text of ['014904','Петров'])assert.equal((await preview.innerText()).includes(text),false,'unrelated object hidden from pair preview');
  assert.equal(await preview.getAttribute('data-preview-total-cents'),'540000','selected object keeps coworker own payable share');assert.equal(await preview.getAttribute('data-preview-reserved-cents'),'360000','selected pair own share is reserved');assert.equal(await preview.locator('tbody tr').count(),2,'only selected object pairs shown');
  for (const [object,employee,before,after] of [[4512,'A',540000,540000],[4512,'B',360000,0]]) {
    const row = preview.locator(`[data-object-id="${object}"][data-employee-id="${employee}"]`);
    assert.deepEqual([await row.getAttribute('data-before-cents'),await row.getAttribute('data-after-cents')],[String(before),String(after)],'decision preview exact independent allocation');
  }
  assert.equal(await decision().locator('[name="expectedRevision"]').inputValue(), revision, 'preview changes no revision');
  await verifyOtizLayout(page,'decision preview modal');
  await page.screenshot({path:config.artifacts+'/decision-preview.png',fullPage:true});
  await page.setViewportSize({width:390,height:844});
  await verifyOtizLayout(page,'decision preview modal at390px');
  await page.screenshot({path:config.artifacts+'/decision-preview-narrow.png',fullPage:true});
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth)<=1,'preview and controls stay inside narrow body');
  await page.setViewportSize({width:1440,height:1000});
  await decision().getByRole('button', { name: 'Сохранить решение', exact: true }).click();
  await page.goto(url);
  assert.equal(await page.locator('[data-v2-deduction-open][data-object-id="4512"][data-employee-id="B"]').count(),0,'excluded pair exposes no personal deduction');assert.ok(await page.locator('[data-v2-deduction-open][data-object-id="4513"][data-employee-id="B"]').count()>0,'same employee on other object remains payable');for(const [object,employee,amount]of [['4512','A','540000'],['4512','B','0'],['4513','B','300000'],['4513','C','300000']])assert.equal(await page.locator(`[data-grouping="objects"] [data-otiz-allocation-row][data-object-id="${object}"][data-employee-id="${employee}"] [data-allocation-amount-cents]`).getAttribute('data-allocation-amount-cents'),amount,'saved exact pair amount');
  await openDecision();
  assert.equal(await decision().getByRole('radio', { name: 'Не платить', exact: true }).isChecked(), true, 'saved choice survives reload');
  assert.equal(await decision().getByLabel(/Основание решения/).inputValue(), 'Проверка только первого объекта', 'saved reason survives reload');
  // Navigate through the real shared editors; returning to the page closes a modal.
  await page.goto(url);
  await openCommonDeduction(page);
  const deduction = () => page.locator('form[action$="/deductions"]').first();
  await deduction().getByLabel(/Сумма удержания/).fill('1 000,00');
  await deduction().getByLabel(/Причина/).fill('Удаляемое удержание');
  await deduction().getByRole('button', { name: 'Предпросмотр', exact: true }).click();
  const deductionPreview = page.locator('[data-preview-kind="deduction"]');
  await deductionPreview.waitFor();
  assert.equal(await deductionPreview.getAttribute('data-preview-total-cents'), '1080000', 'common deduction reduces pool before 60/40 allocation');
  const reduced = deductionPreview.locator('[data-object-id="4512"][data-employee-id="A"]');
  assert.deepEqual([await reduced.getAttribute('data-before-cents'),await reduced.getAttribute('data-after-cents')],['540000','480000'],'common deduction applies exactly 60 percent to payable coworker');
  const reservedRow=deductionPreview.locator('[data-object-id="4512"][data-employee-id="B"]');assert.deepEqual([await reservedRow.getAttribute('data-before-reserved-cents'),await reservedRow.getAttribute('data-after-reserved-cents')],['360000','320000'],'common deduction applies other40percent to reserve without redistribution');
  await deduction().getByRole('button', { name: 'Добавить удержание', exact: true }).click();
  await page.goto(url);
  await page.locator('[data-v2-holds-open]').click();
  const saved = page.locator('[data-deduction-id]').filter({ hasText: 'Удаляемое удержание' });
  assert.equal(await saved.count(), 1, 'saved deduction has an addressable identity and reason');
  assert.match(await saved.innerText(),/014903/,'saved common deduction names exact object');
  await saved.getByLabel(/Причина/).fill('Ошибочный ввод');
  await saved.getByRole('button', { name: 'Удалить удержание', exact: true }).click();
  await page.goto(url);
  assert.equal(await page.locator('[data-deduction-id]').filter({ hasText: 'Удаляемое удержание' }).count(), 0, 'removed deduction disappears from active list');
  await openDecision();
  assert.equal(await decision().getByRole('radio', { name: 'Не платить', exact: true }).isChecked(), true, 'deduction removal preserves independent decision');
  await decision().getByRole('radio', { name: 'Платить', exact: true }).check();
  await decision().getByLabel(/Основание решения/).fill('Возвращено исходное распределение');
  await decision().getByRole('button', { name: 'Сохранить решение', exact: true }).click();
  await page.goto(url);
  await openDecision();
  assert.equal(await decision().getByRole('radio', { name: 'Платить', exact: true }).isChecked(),true,'return-to-pay survives reload without drift');
  await page.goto(url);
  assert.ok(await page.locator('a[href="/pilot/objects/4512"]').count() > 0, 'calculation links to canonical object');
  for (const [grouping,panel,target] of [['По объектам','objects','employees'],['По монтажникам','employees','objects']]) {
    await page.getByRole('radio', { name: grouping }).click();
    const container=page.locator(`[data-grouping="${panel}"]`);
    const toggle=container.locator('button[aria-expanded]').first();if(await toggle.getAttribute('aria-expanded')!=='true')await toggle.click();
    const link=container.locator('[data-otiz-crosslink]').first();
    const targetId=await link.getAttribute('data-target-id');await link.click();
    assert.equal(await page.locator(`[data-grouping="${target}"]`).isVisible(),true,'crosslink switches hidden panel');
    assert.equal(await page.locator(`[data-grouping="${target}"] #${targetId} button[aria-expanded]`).getAttribute('aria-expanded'),'true','crosslink opens exact destination group');
  }
  await page.goto(url+'?group=objects&q=Иванов&pageSize=10');
  assert.equal(await page.locator('[data-grouping="objects"] [data-otiz-group-toggle]').count(),1,'search matches child and retains whole parent');
  assert.ok(await page.locator('[data-grouping="objects"]').getByText('Сидоров Сергей',{exact:false}).count()>0,'matching parent retains complete child allocation');
  assert.equal(await page.locator('input[type="search"][name="q"]').inputValue(),'Иванов','server search query preserved');
  assert.equal(await page.locator('[name="sort"]').count(),0,'amount sorting controls remain removed');
  const pagedUrl=config.origin+'/pilot/otiz/calculations/'+config.pagedDraft.calculationId;
  await page.goto(pagedUrl+'?group=objects&pageSize=10&q=PAGE');
  let panel=page.locator('[data-grouping="objects"]');
  assert.equal(await panel.locator('[data-otiz-group-toggle]').count(),10,'supported page size shows ten complete parents');
  assert.equal(await panel.locator('select[name="pageSize"]').evaluate(el=>Boolean(el.closest('section').querySelector('table').compareDocumentPosition(el)&Node.DOCUMENT_POSITION_FOLLOWING)),true,'page-size control is below table');
  const next=panel.getByRole('link',{name:/Следующая/});const nextUrl=new URL(await next.getAttribute('href'),config.origin);
  assert.deepEqual([nextUrl.searchParams.get('page'),nextUrl.searchParams.get('pageSize'),nextUrl.searchParams.get('q')],['2','10','PAGE'],'real pagination preserves size and search');
  await next.click();panel=page.locator('[data-grouping="objects"]');
  assert.equal(await panel.locator('[data-otiz-group-toggle]').count(),1,'last page contains the remaining whole object');
  assert.ok((await panel.innerText()).includes('PAGE-11'),'natural registration order is deterministic');
  assert.deepEqual(await panel.locator('[data-otiz-allocation-row]').evaluateAll(rows=>rows.map(r=>[r.dataset.objectId,r.dataset.employeeId])),[['4610','A'],['4610','B']],'pagination never splits an object crew');
  await Promise.all([page.waitForNavigation(),panel.locator('select[name="pageSize"]').selectOption('25')]);
  panel=page.locator('[data-grouping="objects"]');assert.equal(await panel.locator('[data-otiz-group-toggle]').count(),11,'footer page-size change returns all eleven parents');
  assert.equal(await page.locator('input[type="search"][name="q"]').inputValue(),'PAGE','footer size change retains search');
  await page.goto(url+'?group=employees&pageSize=10');
  const employeeNames=await page.locator('[data-grouping="employees"] [data-otiz-employee-toggle]').allTextContents();
  assert.ok(employeeNames[0].includes('Иванов')&&employeeNames[1].includes('Петров')&&employeeNames[2].includes('Сидоров'),'employee groups use stable name order');
  await page.goto(url);
  await openCommonDeduction(page);
  const personal=page.locator('form[action$="/deductions"]').first();
  await page.keyboard.press('Escape');
  await openPersonalDeduction(page, '4512', 'A');
  assert.equal(await personal.locator('select[name="employeeId"]').inputValue(),'A','personal scope uses exact stable recipient');
  await personal.getByLabel(/Сумма удержания/).fill('1 000,00');
  await personal.getByLabel(/Причина/).fill('Личное удержание А');
  await personal.getByRole('button',{name:'Предпросмотр',exact:true}).click();
  const personalPreview=page.locator('[data-preview-kind="deduction"]');
  for(const [employee,amount] of [['A','440000'],['B','360000']])assert.equal(await personalPreview.locator(`[data-object-id="4512"][data-employee-id="${employee}"]`).getAttribute('data-after-cents'),amount,'personal deduction leaves other recipient unchanged');
  await page.locator('form[action$="/deductions"]').first().getByRole('button',{name:'Добавить удержание',exact:true}).click();
  await page.locator('[data-v2-holds-open]').click();
  const personalSaved=page.locator('[data-deduction-id]').filter({hasText:'Личное удержание А'});
  for(const identity of ['014903','Иванов'])assert.ok((await personalSaved.innerText()).includes(identity),'saved personal deduction names '+identity);
  await personalSaved.getByLabel(/Причина/).fill('Отмена личного удержания');
  await personalSaved.getByRole('button',{name:'Удалить удержание',exact:true}).click();
  await page.goto(url);
  await openCommonDeduction(page);
  const restored=page.locator('form[action$="/deductions"]').first();
  await restored.locator('select[name="employeeId"]').selectOption('A');
  await restored.getByLabel(/Сумма удержания/).fill('1 000,00');
  await restored.getByLabel(/Причина/).fill('Проверка восстановления без сохранения');
  await restored.getByRole('button',{name:'Предпросмотр',exact:true}).click();
  for(const [employee,amount]of [['A','540000'],['B','360000']])assert.equal(await page.locator(`[data-preview-kind="deduction"] [data-object-id="4512"][data-employee-id="${employee}"]`).getAttribute('data-before-cents'),amount,'deleting personal fact restores original exact recipient amounts');
  await page.keyboard.press('Escape');
  await openPersonalDeduction(page, '4512', 'A');
  assert.equal(await restored.getByLabel(/Причина/).inputValue(),'Проверка восстановления без сохранения','same row context retains pending input');
  assert.equal(await page.locator('[data-preview-kind="deduction"]').isVisible(),true,'same row context retains matching preview');
  await page.keyboard.press('Escape');
  await openCommonDeduction(page, '4512');
  assert.equal(await restored.locator('[name="employeeId"]').inputValue(),'','changing row context selects common deduction');
  assert.equal(await page.locator('[data-preview-kind="deduction"]').isVisible(),false,'personal preview is not shown for common scope');
  await page.keyboard.press('Escape');
  await openCommonDeduction(page,'4513');
  const secondObject=page.locator('dialog[open] form[data-recovery-deduction]');
  await secondObject.getByLabel(/Сумма удержания/).fill('100,00');
  await secondObject.getByLabel(/Причина/).fill('Preview второго объекта');
  await secondObject.getByRole('button',{name:'Предпросмотр',exact:true}).click();
  await page.locator('[data-preview-kind="deduction"]').waitFor({state:'visible'});
  assert.equal(await page.locator('dialog[open] form[data-recovery-deduction] [name="objectId"]').inputValue(),'4513','second object preview opens its own editor');
  assert.equal(await page.locator('[data-preview-kind="deduction"]').getAttribute('data-preview-total-cents'),'1490000');
  assert.equal(await page.locator('[data-preview-kind="deduction"] [data-object-id="4513"][data-employee-id="B"]').getAttribute('data-after-cents'),'295000','second object preview affects only selected object');


}

export async function verifyDismissedReserves(page, config) {
  assert.ok(config.dismissedDraft, 'separate current-policy dismissal fixture exists');
  await page.goto(config.origin+'/pilot/otiz/calculations/'+config.dismissedDraft.calculationId);
  for(const [axis,label]of [['objects','По объектам'],['employees','По монтажникам']]){
    await page.getByRole('radio',{name:label,exact:true}).check();
    const panel=page.locator(`[data-grouping="${axis}"]`);
    for(const toggle of await panel.locator('[data-otiz-group-toggle],[data-otiz-employee-toggle]').all())if(await toggle.getAttribute('aria-expanded')!=='true')await toggle.click();
    assert.ok((await panel.innerText()).includes('Уволен'),'dismissal warning remains visible in '+axis);
    for(const object of ['4512','4513'])assert.equal(await panel.locator(`[data-otiz-allocation-row][data-object-id="${object}"][data-employee-id="B"] [data-allocation-amount-cents]`).getAttribute('data-allocation-amount-cents'),'0','dismissed own share is not payable');
    if(axis==='objects')for(const [object,reserve]of [['4512','3 600,00'],['4513','3 000,00']])assert.ok((await panel.locator(`[data-otiz-allocation-row][data-object-id="${object}"][data-employee-id="B"]`).innerText()).replace(/\u00a0/g,' ').includes('Сохранено '+reserve),'own reserve displayed per object');
  }
}
