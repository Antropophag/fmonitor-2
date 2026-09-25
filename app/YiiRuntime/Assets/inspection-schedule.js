import { DatePickerController } from "/pilot/assets/shlz-behaviors.js";

(() => {
  const dialog = document.querySelector("[data-inspection-dialog]");
  if (!dialog) return;
  const form = dialog.querySelector("[data-inspection-form]"),
    dateHost = form.querySelector("[data-inspection-date-picker]"),
    picker = dateHost
      ? new DatePickerController(dateHost, {
      mode: "single",
      label: "Дата инспекции",
      calendarLabel: "Календарь даты инспекции",
      name: "inspectionDate",
      value: dateHost.dataset.value,
      visibleMonth: dateHost.dataset.value?.slice(0, 7),
      required: true,
      locale: "ru-RU",
        })
      : null,
    date = picker?.field.formInput || form.querySelector("[name=inspectionDate]"),
    visibleDate = picker?.field.input || date,
    dateContainer = dateHost || date?.closest("[data-native-date-fallback]"),
    action = form.querySelector("[name=action]"),
    plan = form.querySelector("[name=planId]"),
    version = form.querySelector("[name=expectedVersion]"),
    request = form.querySelector("[name=requestId]"),
    title = dialog.querySelector("[data-inspection-title]"),
    submit = dialog.querySelector("[data-inspection-submit]"),
    cancel = dialog.querySelector("[data-inspection-cancel]");
  let trigger = null,
    lastAction = "";
  const labels = {
    create: ["Запланировать инспекцию", "Запланировать"],
    reschedule: ["Перенести инспекцию", "Перенести"],
    cancel: ["Отменить инспекцию", "Отменить"],
  };
  const showDate = (shown) => {
    if (dateContainer) dateContainer.hidden = !shown;
    if (picker) picker.setDisabled(!shown);
    else if (date) date.disabled = !shown;
  };
  const focusDate = () => visibleDate.focus({ preventScroll: true });
  const open = (button) => {
    const command = button.dataset.inspectionAction || "create";
    trigger = button;
    form.action =
      "/pilot/construction-control/objects/" +
      button.dataset.inspectionObjectId +
      "/inspection-plan";
    action.value = command;
    plan.value = button.dataset.planId || "";
    version.value = button.dataset.planVersion || "0";
    if (lastAction !== command || date.value === "") {
      const value = button.dataset.inspectionDate || "";
      if (picker) picker.field.setValue(value);
      else date.value = value;
    }
    lastAction = command;
    request.value = crypto.randomUUID();
    showDate(command !== "cancel");
    title.textContent = labels[command][0];
    submit.textContent = labels[command][1];
    cancel.hidden = command !== "reschedule";
    dialog.showModal();
    (command === "cancel" ? submit : visibleDate).focus({ preventScroll: true });
    requestAnimationFrame(() =>
      (command === "cancel" ? submit : visibleDate).focus({ preventScroll: true }),
    );
  };
  document.querySelectorAll("[data-inspection-action]").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      open(button);
    });
    button.addEventListener("keydown", (event) => {
      if (!["Enter", " "].includes(event.key)) return;
      event.preventDefault();
      open(button);
    });
  });
  document.querySelectorAll("[data-inspection-schedule]").forEach((button) =>
    button.addEventListener("click", () => {
      form.action =
        "/pilot/objects/" + button.dataset.objectId + "/inspection-schedule";
      dialog.showModal();
      focusDate();
    }),
  );
  const close = () => {
    dialog.close();
    trigger?.focus();
  };
  dialog
    .querySelectorAll("[data-inspection-close]")
    .forEach((button) => button.addEventListener("click", close));
  cancel?.addEventListener("click", () => {
    action.value = "cancel";
    showDate(false);
    title.textContent = labels.cancel[0];
    submit.textContent = labels.cancel[1];
    cancel.hidden = true;
    request.value = crypto.randomUUID();
    submit.focus({ preventScroll: true });
  });
  dialog.addEventListener("cancel", (event) => {
    event.preventDefault();
    close();
  });
  dialog.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !picker?.popover.expanded) {
      event.preventDefault();
      close();
    }
  });
  if (dialog.hasAttribute("data-inspection-failed")) {
    lastAction = action.value;
    showDate(action.value !== "cancel");
    title.textContent = labels[action.value]?.[0] || labels.create[0];
    submit.textContent = labels[action.value]?.[1] || labels.create[1];
    cancel.hidden = action.value !== "reschedule";
    dialog.showModal();
    queueMicrotask(() =>
      (action.value === "cancel" ? submit : visibleDate).focus(),
    );
  }
  form.addEventListener("submit", () => {
    submit.disabled = true;
  });
  form.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && event.target === visibleDate && !submit.disabled) {
      event.preventDefault();
      form.requestSubmit(submit);
    }
  });
})();
