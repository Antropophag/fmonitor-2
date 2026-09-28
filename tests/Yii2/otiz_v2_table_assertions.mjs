import assert from 'node:assert/strict';

async function expandObjects(page) {
  await page.getByRole('radio', {name:'По объектам',exact:true}).check();
  for (const toggle of await page.locator('[data-grouping="objects"] [data-otiz-group-toggle]').all()) {
    if (await toggle.getAttribute('aria-expanded') !== 'true') await toggle.click();
  }
}

export async function openRowDecision(page) {
  await expandObjects(page);
  await page.locator('[data-grouping="objects"] [data-v2-decision-open]').first().click();
}

export async function openCommonDeduction(page, objectId) {
  await page.getByRole('radio', {name:'По объектам',exact:true}).check();
  const scope = objectId ? `[data-object-id="${objectId}"]` : '';
  await page.locator(`[data-grouping="objects"] [data-v2-deduction-open][data-employee-id=""]${scope}`).first().click();
}

export async function openPersonalDeduction(page, objectId, employeeId) {
  await expandObjects(page);
  await page.locator(`[data-grouping="objects"] [data-v2-deduction-open][data-object-id="${objectId}"][data-employee-id="${employeeId}"]`).click();
}

export async function verifyReferenceTable(page, config) {
  const url=config.origin+`/pilot/otiz/calculations/${config.draft.calculationId}`;
  const posts=[];
  const observe=request=>{if(request.method()==='POST')posts.push(request.url());};
  page.on('request',observe);
  await expandObjects(page);
  for (const [axis,label,count] of [['objects','По объектам',6],['employees','По монтажникам',5]]) {
    await page.getByRole('radio',{name:label,exact:true}).check();
    const panel=page.locator(`[data-grouping="${axis}"]`);
    for (const toggle of await panel.locator('[data-otiz-group-toggle],[data-otiz-employee-toggle]').all()) if(await toggle.getAttribute('aria-expanded')!=='true')await toggle.click();
    assert.equal(await panel.locator('table table').count(),0,'detail rows share parent table');
    const geometry=await panel.locator('table').evaluate((table)=>{
      const header=[...table.tHead.rows[0].cells].map(cell=>({x:cell.getBoundingClientRect().x,width:cell.getBoundingClientRect().width}));
      const children=[...table.querySelectorAll('tr[data-otiz-allocation-row]')].map(row=>[...row.cells].map(cell=>({x:cell.getBoundingClientRect().x,width:cell.getBoundingClientRect().width})));
      return {header,children};
    });
    assert.equal(geometry.header.length,count,axis+' reference columns');
    const expectedWidths=axis==='objects'?[30,16,14,17,19,4]:[42,9,17,27,5];
    const tableWidth=geometry.header.reduce((sum,cell)=>sum+cell.width,0);
    for(let i=0;i<count;i++)assert.ok(Math.abs(100*geometry.header[i].width/tableWidth-expectedWidths[i])<1,axis+' reference column proportion '+i+': '+JSON.stringify(geometry.header));
    assert.ok(geometry.children.length>=4,'all saved allocations visible');
    for(const cells of geometry.children){assert.equal(cells.length,count);for(let i=0;i<count;i++)assert.ok(Math.abs(cells[i].x-geometry.header[i].x)<1&&Math.abs(cells[i].width-geometry.header[i].width)<1,'child and parent columns align');}
    const trigger=panel.locator('[data-v2-decision-open][data-employee-id="B"]').first();
    assert.equal(await trigger.locator('xpath=ancestor::tr').count(),1,'dismissed decision belongs to worker row');
    await trigger.click();
    const decision=page.locator('dialog[open] form[data-recovery-decision]');
    assert.equal(await decision.locator('[name="employeeId"]').inputValue(),'B','decision edits exact stable worker from either axis');
    await page.keyboard.press('Escape');
    const personal=panel.locator('[data-v2-deduction-open][data-object-id="4512"][data-employee-id="A"]');
    assert.equal(await personal.locator('xpath=ancestor::tr[@data-otiz-allocation-row]').count(),1,'personal action belongs to allocation row');
    await personal.click();
    const form=page.locator('dialog[open] form[data-recovery-deduction]');
    assert.equal(await form.locator('[name="objectId"]').inputValue(),'4512');
    assert.equal(await form.locator('[name="employeeId"]').inputValue(),'A','row action preselects person');
    await page.keyboard.press('Escape');
    await page.screenshot({path:config.artifacts+`/reference-${axis}-expanded.png`,fullPage:true});
  }
  await openCommonDeduction(page,'4512');
  const form=page.locator('dialog[open] form[data-recovery-deduction]');
  assert.equal(await form.locator('[name="employeeId"]').inputValue(),'','common action clears previous personal scope');
  await page.keyboard.press('Escape');
  const column=await page.locator('[data-grouping="objects"] [data-v2-deduction-open][data-object-id="4512"][data-employee-id=""]').evaluate(el=>el.closest('td').cellIndex);
  assert.equal(column,3,'common deduction belongs in reduction column');
  await page.locator('[data-v2-holds-open]').click();
  assert.match(await page.locator('dialog[open]').innerText(),/Дисциплинарные удержания/);
  await page.keyboard.press('Escape');
  assert.deepEqual(posts,[],'opening contextual UI never submits facts');
  page.off('request',observe);
  await page.goto(url);
}
