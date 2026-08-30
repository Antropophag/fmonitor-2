import { enhanceTabs } from "/pilot/assets/shlz-tabs.js";

enhanceTabs();
const assignmentForm = document.querySelector('.fm2-order-form');
if (assignmentForm) {
  assignmentForm.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    if (!(submitter instanceof HTMLButtonElement)) {
      event.preventDefault();
      return;
    }
    const intent = submitter.value;
    if ((intent === 'template' && assignmentForm.dataset.templateSubmitted === 'true') || assignmentForm.dataset.uploadSubmitted === 'true') {
      event.preventDefault();
      return;
    }
    if (intent === 'template') assignmentForm.dataset.templateSubmitted = 'true';
    else assignmentForm.dataset.uploadSubmitted = 'true';
    submitter.textContent = intent === 'template' ? 'Шаблон формируется…' : 'Распоряжение загружается…';
    setTimeout(() => {
      if (intent === 'template') {
        submitter.disabled = true;
        submitter.setAttribute('aria-disabled', 'true');
        return;
      }
      assignmentForm.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
      });
    }, 0);
  });
}
