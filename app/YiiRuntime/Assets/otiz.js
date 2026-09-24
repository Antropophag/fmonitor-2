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
