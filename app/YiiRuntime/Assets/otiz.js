import {DatePickerController} from '/pilot/assets/shlz-behaviors.js';
// OTIZ progressive enhancement. Financial writes remain ordinary server forms.
(() => {
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
  const publication=document.querySelector('form[action="/pilot/otiz/calculate"]');
  if(publication){const key='fm2.otiz.publication';try{if(new URL(location.href).searchParams.get('created')==='1')sessionStorage.removeItem(key);const saved=JSON.parse(sessionStorage.getItem(key)||'null');if(saved){publication.elements.operationId.value=saved.operationId;publication.elements.reportDate.value=saved.reportDate;}publication.addEventListener('submit',()=>{const prior=JSON.parse(sessionStorage.getItem(key)||'null');if(prior&&prior.reportDate!==publication.elements.reportDate.value)publication.elements.operationId.value=crypto.randomUUID();sessionStorage.setItem(key,JSON.stringify({operationId:publication.elements.operationId.value,reportDate:publication.elements.reportDate.value}));});}catch{}}

  const paymentForm=document.querySelector('[data-payment-form]'),paymentDialog=document.querySelector('[data-otiz-payment-dialog]');
  if(paymentForm&&paymentDialog){paymentForm.querySelector('[data-payment-open]')?.addEventListener('click',()=>paymentDialog.setAttribute('aria-hidden','false'));paymentDialog.querySelector('[data-payment-cancel]')?.addEventListener('click',()=>paymentDialog.setAttribute('aria-hidden','true'));paymentDialog.querySelector('[data-payment-confirm]')?.addEventListener('click',()=>paymentForm.requestSubmit());}
})();
