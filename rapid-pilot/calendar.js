import { enhanceCalendarGrids } from "/pilot/assets/shlz-calendar-grid.js";

const page = document.querySelector("[data-calendar-page]");
if (page) {
  enhanceCalendarGrids(page);
  page.addEventListener("click", (event) => {
    const button = event.target.closest("[data-calendar-date]");
    if (!button) return;
    const url = new URL(window.location.href);
    url.searchParams.set("date", button.dataset.calendarDate);
    url.searchParams.set("month", button.dataset.calendarDate.slice(0, 7));
    window.location.assign(url);
  });
}
