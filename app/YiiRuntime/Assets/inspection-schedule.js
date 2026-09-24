(() => {
  const dialog = document.querySelector("[data-inspection-dialog]");
  if (!dialog) return;
  const form = dialog.querySelector("[data-inspection-form]"),
    date = form.querySelector("[name=inspectionDate]"),
    action = form.querySelector("[name=action]"),
    plan = form.querySelector("[name=planId]"),
    version = form.querySelector("[name=expectedVersion]"),
    request = form.querySelector("[name=requestId]"),
    title = dialog.querySelector("[data-inspection-title]"),
    submit = dialog.querySelector("[data-inspection-submit]");
  let trigger = null,
    lastAction = "";
  const labels = {
    create: ["Запланировать инспекцию", "Запланировать"],
    reschedule: ["Перенести инспекцию", "Перенести"],
    cancel: ["Отменить инспекцию", "Отменить"],
  };
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
    if (lastAction !== command || date.value === "")
      date.value = button.dataset.inspectionDate || date.min;
    lastAction = command;
    request.value = crypto.randomUUID();
    date.required = command !== "cancel";
    date.closest("[data-inspection-date-field]").hidden = command === "cancel";
    title.textContent = labels[command][0];
    submit.textContent = labels[command][1];
    date.tabIndex = 0;
    date.toggleAttribute("autofocus", command !== "cancel");
    dialog.showModal();
    (command === "cancel" ? submit : date).focus({ preventScroll: true });
  };
  document
    .querySelectorAll("[data-inspection-action]")
    .forEach((button) => {
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
      date.focus();
    }),
  );
  const close = () => {
    dialog.close();
    trigger?.focus();
  };
  dialog
    .querySelectorAll("[data-inspection-close]")
    .forEach((button) => button.addEventListener("click", close));
  dialog.addEventListener("cancel", (event) => {
    event.preventDefault();
    close();
  });
  dialog.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      event.preventDefault();
      close();
    }
  });
  if (dialog.hasAttribute("data-inspection-failed")) {
    lastAction = action.value;
    date.required = action.value !== "cancel";
    date.closest("[data-inspection-date-field]").hidden =
      action.value === "cancel";
    title.textContent = labels[action.value]?.[0] || labels.create[0];
    submit.textContent = labels[action.value]?.[1] || labels.create[1];
    dialog.showModal();
    queueMicrotask(() =>
      (action.value === "cancel" ? submit : date).focus(),
    );
  }
  form.addEventListener("submit", () => {
    submit.disabled = true;
  });
  form.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && event.target === date && !submit.disabled) {
      event.preventDefault();
      form.requestSubmit(submit);
    }
  });
})();
