import { enhanceCalendarGrids } from "/pilot/assets/shlz-calendar-grid.js";

const page = document.querySelector("[data-calendar-page]");
if (page) {
  enhanceCalendarGrids(page);

  const grid = page.querySelector("[data-shlz-calendar-grid]");
  const today = grid?.querySelector(
    '[data-shlz-calendar-grid-header-row="dates"] [data-shlz-calendar-grid-state="today"]',
  );
  const previousDay = today?.previousElementSibling;
  const rowHeader = grid?.querySelector("thead > tr:first-child > th:first-child");

  if (grid && previousDay && rowHeader) {
    const positionToday = () => {
      const target = previousDay.offsetLeft - rowHeader.offsetWidth;
      grid.scrollLeft = Math.max(0, Math.min(target, grid.scrollWidth - grid.clientWidth));
    };
    requestAnimationFrame(positionToday);
    window.addEventListener("load", positionToday, { once: true });
  }

  page.addEventListener("click", (event) => {
    const button = event.target.closest("[data-calendar-date]");
    if (!button) return;
    const url = new URL(window.location.href);
    url.searchParams.set("date", button.dataset.calendarDate);
    url.searchParams.delete("month");
    window.location.assign(url);
  });
}
