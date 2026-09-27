import assert from "node:assert/strict";

export async function verifyOtizLayout(page, name) {
  const issues = await page.locator(".fm2-otiz-page").evaluate((root) => {
    const issues = [];
    const visible = (el) => el.getClientRects().length > 0;
    for (const input of root.querySelectorAll("input.shlz-input")) {
      if (!visible(input)) continue;
      const box = input.getBoundingClientRect();
      if (box.height < 28 || box.height > 56)
        issues.push(`field ${input.name} has unusable height ${box.height}`);
      if (!input.closest(".shlz-field__control"))
        issues.push(`field ${input.name} lacks the public SHLZ control container`);
    }
    for (const cell of root.querySelectorAll(".shlz-table th, .shlz-table td")) {
      if (!visible(cell)) continue;
      if (!cell.classList.contains("shlz-table__cell"))
        issues.push("table cell lacks public SHLZ spacing and alignment");
      if (parseFloat(getComputedStyle(cell).paddingLeft) < 6)
        issues.push("table cell has no readable horizontal spacing");
    }
    const textNodes = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    while (textNodes.nextNode()) {
      const node = textNodes.currentNode;
      if (!/^[−-]?[\d\s.,]+₽$/.test(node.textContent.trim())) continue;
      if (!node.parentElement.closest(".shlz-table") || !visible(node.parentElement)) continue;
      const range = document.createRange();
      range.selectNodeContents(node);
      if ([...range.getClientRects()].filter((r) => r.width > 0 && r.height > 0).length > 1)
        issues.push("monetary amount wraps across lines and cannot be scanned");
    }
    if (window.innerWidth >= 1200) {
      for (const wrap of root.querySelectorAll("dialog[open] .fm2-otiz-v2-preview .shlz-table-wrap")) {
        if (wrap.scrollWidth > wrap.clientWidth + 2)
          issues.push("desktop editor clips the before/after monetary preview");
      }
    }
    const toolbar = root.querySelector('form[method="get"]');
    const table = root.querySelector(".shlz-table-wrap");
    if (toolbar && table && visible(toolbar) && visible(table)) {
      const bottom = toolbar.getBoundingClientRect().bottom;
      for (const control of toolbar.querySelectorAll("input,button,.shlz-field")) {
        if (visible(control) && control.getBoundingClientRect().bottom > bottom + 2)
          issues.push("filter control escapes its toolbar and overlaps following content");
      }
      if (table.getBoundingClientRect().top < bottom - 2)
        issues.push("table overlaps filter toolbar");
    }
    if (document.documentElement.scrollWidth > window.innerWidth + 2)
      issues.push("page overflows viewport instead of containing table scroll");
    return [...new Set(issues)];
  });
  assert.deepEqual(issues, [], `${name}: approved OTIZ layout remains usable`);
}

export async function verifyOtizRegisterLayouts(page, c) {
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const route of ["objects", "payments"]) {
      await page.goto(c.origin + "/pilot/otiz/" + route);
      await verifyOtizLayout(page, `${route} at ${width}px`);
      await page.screenshot({ path: `${c.artifacts}/layout-${route}-${width}.png`, fullPage: true });
    }
  }
  await page.setViewportSize({ width: 1440, height: 1000 });
}
