import {DatePickerController} from '/pilot/assets/shlz-behaviors.js';
// OTIZ progressive enhancement. Financial writes remain ordinary server forms.
(() => {
  document.querySelectorAll('dialog[data-otiz-drawer][open]:not([data-otiz-open-on-error])').forEach(dialog=>dialog.close());
  document.querySelectorAll('.fm2-otiz-snapshot-register .shlz-visually-hidden').forEach(link=>{link.hidden=true;});
  const snapshotRegister=document.querySelector('.fm2-otiz-snapshot-register');if(snapshotRegister&&!document.querySelector('.fm2-otiz-trace:not(dialog *)')){const guide=document.createElement('details');guide.className='fm2-otiz-trace';guide.innerHTML='<summary>Как читать расчёт</summary><p>Точная трассировка каждого объекта доступна в его детализации.</p>';snapshotRegister.after(guide);}
  document.querySelectorAll('[data-otiz-ledger-row] td:nth-child(2)').forEach(cell=>{cell.prepend('Дата выплаты ');});
  document.querySelectorAll('form[action$="/closures"] button,form[action$="/reverse"] button').forEach(button => { button.type='submit'; });
  document.querySelectorAll('[data-drawer-open]').forEach(button => {
    button.dataset.shlzDrawerTrigger=button.dataset.drawerOpen;
    delete button.dataset.drawerOpen;
  });
  document.querySelectorAll('[data-otiz-question]').forEach(question=>{question.hidden=false;});
  document.querySelectorAll('.shlz-drawer__close').forEach(button=>button.setAttribute('aria-label','Скрыть панель'));
  document.querySelectorAll('.fm2-otiz-drawer-section dt').forEach(label=>{if(label.textContent.trim()==='Дата факта прогресса')label.textContent='Дата подтверждающего факта / факта прогресса';});
  const groupings=[...document.querySelectorAll('[data-grouping]')];
  const selectGroup=name=>{document.querySelectorAll('.fm2-otiz-v2-tabs [role="tab"]').forEach(item=>{const active=item.dataset.groupTab===name;item.classList.toggle('is-active',active);item.setAttribute('aria-selected',String(active));});groupings.forEach(panel=>{panel.hidden=panel.dataset.grouping!==name;});const input=document.querySelector('.fm2-otiz-v2-query [name="group"]');if(input)input.value=name;};
  document.querySelectorAll('.fm2-otiz-v2-tabs [role="tab"]').forEach(tab=>tab.addEventListener('click',()=>selectGroup(tab.dataset.groupTab)));
  const toggleGroup=button=>{const expanded=button.getAttribute('aria-expanded')!=='true';button.setAttribute('aria-expanded',String(expanded));const detail=document.getElementById(button.getAttribute('aria-controls'));if(detail)detail.hidden=!expanded;};
  document.querySelectorAll('[data-otiz-group-toggle],[data-otiz-employee-toggle]').forEach(button=>button.addEventListener('click',()=>toggleGroup(button)));
  document.querySelectorAll('[data-otiz-crosslink]').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();selectGroup(link.dataset.targetGroup);const target=document.getElementById(link.dataset.targetId);const button=target?.querySelector('[aria-controls]');if(button&&button.getAttribute('aria-expanded')!=='true')toggleGroup(button);target?.scrollIntoView({block:'center'});button?.focus();history.replaceState(null,'','#'+link.dataset.targetId);}));
  document.querySelectorAll('.fm2-otiz-v2-axis a[rel="next"]').forEach(link=>{const label=document.createElement('span');label.className='shlz-visually-hidden';label.textContent='Следующая';link.append(label);});
  document.querySelectorAll('.fm2-otiz-v2-detail-table').forEach(table=>{
    table.querySelectorAll('th').forEach(cell=>{if(cell.textContent.trim()==='Исходный вклад')cell.textContent='Исходный вклад · денежная доля до перераспределения';});
    const labels=new Map([['pay','Платить'],['do_not_pay','Не платить'],['employed','Трудоустроен'],['dismissed','Уволен'],['unknown','Нет данных'],['','Нет данных']]);
    table.querySelectorAll('tbody td').forEach(cell=>{const value=cell.textContent.trim();if(labels.has(value))cell.textContent=labels.get(value);if(value.includes('₽'))cell.style.whiteSpace='nowrap';});
  });
  document.querySelectorAll('.fm2-otiz-v2-preview').forEach(preview=>{const table=preview.querySelector('table');if(table&&!table.parentElement.matches('.shlz-table-wrap')){const wrap=document.createElement('div');wrap.className='shlz-table-wrap';wrap.tabIndex=0;table.before(wrap);wrap.append(table);}if(table){table.style.minWidth='620px';table.querySelectorAll('td:nth-child(3),td:nth-child(4)').forEach(cell=>cell.style.whiteSpace='nowrap');}});
  document.querySelectorAll('.fm2-otiz-v2-editor fieldset label').forEach(label=>label.style.whiteSpace='nowrap');
  const basis=document.querySelector('[data-object-basis-summary]');if(basis){document.querySelectorAll('[data-grouping]').forEach(panel=>{const copy=basis.cloneNode(true);copy.removeAttribute('id');copy.querySelector('[id="basis-title"]')?.removeAttribute('id');copy.style.display='grid';copy.style.gridTemplateColumns='repeat(auto-fit,minmax(260px,1fr))';copy.style.gap='20px';copy.querySelector('h2').style.gridColumn='1 / -1';copy.querySelectorAll('article').forEach(article=>{article.style.minWidth='0';const list=article.querySelector('dl');list.style.display='grid';list.style.gridTemplateColumns='repeat(auto-fit,minmax(120px,1fr))';list.style.gap='12px 16px';list.querySelectorAll('div').forEach(item=>{item.style.minWidth='0';item.querySelector('dd').style.marginInlineStart='0';});});panel.prepend(copy);});basis.remove();}
  document.querySelectorAll('[data-v2-lifecycle-open]').forEach(trigger=>trigger.addEventListener('click',()=>{const dialog=document.querySelector(`[data-v2-lifecycle-dialog="${trigger.dataset.v2LifecycleOpen}"]`);dialog?.showModal();(dialog?.querySelector('[name="reason"]')||dialog?.querySelector('button:not([type="button"])'))?.focus();}));
  document.querySelectorAll('[data-v2-lifecycle-cancel]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));
  document.querySelectorAll('details > summary > button').forEach(button=>button.addEventListener('click',()=>{button.closest('details').open=true;button.setAttribute('aria-expanded','true');}));
  const v2PaymentDialog=document.querySelector('[data-v2-payment-dialog]'),nativePaymentDate=v2PaymentDialog?.querySelector('input[name="paymentDate"]');if(nativePaymentDate){const host=document.createElement('div');nativePaymentDate.closest('label')?.replaceWith(host);new DatePickerController(host,{mode:'single',label:'Дата',calendarLabel:'Календарь даты фактической выплаты',name:'paymentDate',value:'',locale:'ru-RU'});}document.querySelector('[data-v2-payment-open]')?.addEventListener('click',()=>{v2PaymentDialog?.showModal();v2PaymentDialog?.querySelector('input:not([type="hidden"])')?.focus();});document.querySelector('[data-v2-payment-cancel]')?.addEventListener('click',()=>v2PaymentDialog?.close());
  const reversalDialog=document.querySelector('[data-v2-reversal-dialog]');document.querySelector('[data-v2-reversal-open]')?.addEventListener('click',()=>{reversalDialog?.showModal();reversalDialog?.querySelector('[name="reason"]')?.focus();});document.querySelector('[data-v2-reversal-cancel]')?.addEventListener('click',()=>reversalDialog?.close());

  const openers=new Map();
  const close=dialog=>{if(!dialog.open)return;dialog.close();openers.get(dialog)?.focus();};
  document.addEventListener('click',event=>{
    const trigger=event.target.closest('[data-shlz-drawer-trigger]');
    if(trigger){const dialog=document.getElementById(trigger.dataset.shlzDrawerTrigger);if(dialog?.matches('dialog[data-shlz-drawer]')){openers.set(dialog,trigger);dialog.showModal();dialog.querySelector('.shlz-drawer__body')?.focus();}return;}
    const closer=event.target.closest('[data-shlz-drawer-close]');if(closer)close(closer.closest('dialog[data-shlz-drawer]'));
  });
  document.querySelectorAll('dialog[data-shlz-drawer][data-shlz-drawer-backdrop-close]').forEach(dialog=>{
    dialog.addEventListener('click',event=>{if(event.target===dialog)close(dialog);});
    dialog.addEventListener('close',()=>openers.get(dialog)?.focus());
  });

  const dateHost=document.querySelector('#otiz-report-date');
  if(dateHost)new DatePickerController(dateHost,{mode:'single',label:'Дата расчёта',calendarLabel:'Календарь даты расчёта',name:'reportDate',value:dateHost.dataset.value,visibleMonth:dateHost.dataset.value?.slice(0,7),locale:'ru-RU'});
  const publicationKey='fm2.otiz.publication';
  try{if(new URL(location.href).searchParams.get('created')==='1')sessionStorage.removeItem(publicationKey);}catch{}
  const publication=document.querySelector('form[action="/pilot/otiz/calculate"]');
  if(publication){try{const saved=JSON.parse(sessionStorage.getItem(publicationKey)||'null');if(saved){publication.elements.operationId.value=saved.operationId;publication.elements.reportDate.value=saved.reportDate;}publication.addEventListener('submit',()=>{const prior=JSON.parse(sessionStorage.getItem(publicationKey)||'null');if(prior&&prior.reportDate!==publication.elements.reportDate.value)publication.elements.operationId.value=crypto.randomUUID();sessionStorage.setItem(publicationKey,JSON.stringify({operationId:publication.elements.operationId.value,reportDate:publication.elements.reportDate.value}));});}catch{}}

  const paymentForm=document.querySelector('[data-payment-form]'),paymentDialog=document.querySelector('[data-otiz-payment-dialog]');
  if(paymentForm&&paymentDialog){let paymentOpener=null;const closePayment=()=>{if(paymentDialog.open)paymentDialog.close();paymentOpener?.focus();};paymentForm.querySelector('[data-payment-open]')?.addEventListener('click',event=>{paymentOpener=event.currentTarget;paymentDialog.showModal();paymentDialog.querySelector('[data-payment-cancel]')?.focus();});paymentDialog.querySelectorAll('[data-payment-cancel]').forEach(button=>button.addEventListener('click',closePayment));paymentDialog.addEventListener('cancel',event=>{event.preventDefault();closePayment();});paymentDialog.addEventListener('click',event=>{if(event.target===paymentDialog)closePayment();});paymentDialog.querySelector('[data-payment-confirm]')?.addEventListener('click',()=>paymentForm.requestSubmit());}

  const pendingKey='fm2.otiz.financial.pending';
  const confirmedRecovery=document.querySelector('[data-otiz-open-on-error]');
  let pending=null;try{pending=JSON.parse(sessionStorage.getItem(pendingKey)||'null');}catch{}
  const confirmed=new URL(location.href).searchParams;const confirmedType=confirmed.has('closed')?'closure':(confirmed.has('paid')?'payment':(confirmed.get('error')==='closure'?'closure':(confirmed.get('error')==='payment'?'payment':document.querySelector('[data-otiz-confirmed-outcome]')?.dataset.otizConfirmedOutcome)));
  if(pending&&((confirmedRecovery&&pending.type==='closure')||confirmedType===pending.type)){try{sessionStorage.removeItem(pendingKey);}catch{}pending=null;}
  if(pending&&pending.path===location.pathname){let form=null;if(pending.type==='closure')form=[...document.querySelectorAll('form[action$="/closures"]')].find(candidate=>candidate.elements.objectId?.value===pending.objectId);if(pending.type==='payment')form=document.querySelector('form[action$="/payments/complete"]');if(form){for(const name of['operationId','discipline','basis','artifact'])if(typeof pending[name]==='string'&&form.elements[name])form.elements[name].value=pending[name];const notice=document.createElement('p');notice.dataset.otizUnknownOutcome='';notice.dataset.operationId=pending.operationId;notice.setAttribute('role','alert');notice.textContent='Результат отправки неизвестен. Проверьте историю и явно решите, повторять ли действие с тем же идентификатором операции.';form.prepend(notice);const drawer=form.closest('dialog[data-shlz-drawer]');if(drawer&&!drawer.open)drawer.showModal();}}
  if(confirmedRecovery){const target=document.querySelector('[data-otiz-form-error][role="alert"], [aria-invalid="true"]');target?.focus();}
  document.querySelectorAll('[data-financial-form]').forEach(form=>form.addEventListener('submit',event=>{if(form.dataset.inFlight==='1'){event.preventDefault();return;}form.dataset.inFlight='1';form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(control=>control.disabled=true);try{if(form.matches('form[action$="/closures"]'))sessionStorage.setItem(pendingKey,JSON.stringify({type:'closure',path:location.pathname,objectId:form.elements.objectId.value,operationId:form.elements.operationId.value,discipline:form.elements.discipline.value,basis:form.elements.basis.value,artifact:form.elements.artifact.value}));if(form.matches('form[action$="/payments/complete"]'))sessionStorage.setItem(pendingKey,JSON.stringify({type:'payment',path:location.pathname,operationId:form.elements.operationId.value}));}catch{}}));
  addEventListener('pageshow',()=>document.querySelectorAll('[data-financial-form]').forEach(form=>{delete form.dataset.inFlight;form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(control=>control.disabled=false);}));
})();
