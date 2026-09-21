import fs from "node:fs";
import { createRequire } from "node:module";
const require = createRequire(import.meta.url),
  config = JSON.parse(fs.readFileSync(process.argv[2], "utf8")),
  failures = [];
const check = (value, message) => {
  if (!value) failures.push(message);
};
const { chromium } = require(config.playwright),
  browser = await chromium.launch({ headless: true });
const login = async (page) => {
  await page.goto(config.origin + "/pilot/login");
  await page.locator("[name=email]").fill(config.email);
  await Promise.all([
    page.waitForNavigation(),
    page
      .locator("[name=email]")
      .locator("xpath=ancestor::form")
      .locator("button[type=submit]")
      .click(),
  ]);
  await page.locator("[name=password]").fill(config.password);
  await Promise.all([
    page.waitForNavigation(),
    page
      .locator("[name=password]")
      .locator("xpath=ancestor::form")
      .locator("button[type=submit]")
      .click(),
  ]);
};
try {
  const context = await browser.newContext({
      viewport: { width: 1440, height: 1000 },
    }),
    page = await context.newPage();
  await login(page);
  await page.goto(config.origin + "/pilot/objects/4512", {
    waitUntil: "networkidle",
  });
  const tabs = page.getByRole("tab"),
    count = await tabs.count();
  check(count === 4, "four tabs");
  check(await page.locator(".fm2-sidebar").isVisible(), "production sidebar");
  check(
    await page.evaluate(
      () =>
        getComputedStyle(document.body).backgroundColor !==
        "rgb(255, 255, 255)",
    ),
    "gray canvas",
  );
  check(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth + 1,
    ),
    "desktop overflow",
  );
  const passport = page.locator(".fm2-static-passport");
  const workspace = page.locator(".fm2-object-workspace");
  const [passportBox, workspaceBox] = await Promise.all([
    passport.boundingBox(),
    workspace.boundingBox(),
  ]);
  check(
    Boolean(
      passportBox &&
        workspaceBox &&
        passportBox.x < workspaceBox.x &&
        passportBox.width < workspaceBox.width &&
        Math.abs(passportBox.y - workspaceBox.y) <= 2,
    ),
    "desktop passport-left workspace-right composition",
  );
  check(
    (await workspace.locator(":scope > .fm2-next-action").count()) === 1,
    "primary action inside right workspace",
  );
  for (const label of ["Этажность", "Грузоподъёмность, кг", "Скорость, м/с", "Тип лифта", "Тип шахты", "Материал шахты"]) {
    check(
      (await passport.getByText(label, { exact: true }).count()) === 1,
      "passport technical " + label,
    );
  }
  check(
    (await passport.getByText("Очередность", { exact: true }).count()) === 0,
    "passport excludes work sequence",
  );
  check(
    (await page
      .locator("#object-panel-readiness")
      .getByText("Этажность", { exact: true })
      .count()) === 0,
    "readiness excludes passport technical data",
  );
  if (count === 4) {
    const state = async (active, label) => {
      for (let i = 0; i < 4; i++) {
        const control = await tabs.nth(i).getAttribute("aria-controls"),
          panel = page.locator("#" + control),
          on = i === active;
        check(
          (await tabs.nth(i).getAttribute("aria-selected")) === String(on),
          label + " selected " + i,
        );
        check(
          (await tabs.nth(i).getAttribute("tabindex")) === (on ? "0" : "-1"),
          label + " tabindex " + i,
        );
        check(
          (await panel.getAttribute("hidden")) === (on ? null : ""),
          label + " hidden " + i,
        );
        check((await panel.isVisible()) === on, label + " visible " + i);
      }
    };
    for (let i = 0; i < 4; i++) {
      const id = await tabs.nth(i).getAttribute("id"),
        control = await tabs.nth(i).getAttribute("aria-controls");
      check(Boolean(id && control), "tab ids " + i);
      check(
        (await page.locator("#" + control).getAttribute("aria-labelledby")) ===
          id,
        "panel label " + i,
      );
    }
    await state(0, "initial");
    await tabs.nth(1).click();
    await state(1, "click");
    await page.keyboard.press("ArrowRight");
    await state(2, "ArrowRight");
    await page.keyboard.press("ArrowLeft");
    await state(1, "ArrowLeft");
    await page.keyboard.press("Home");
    await state(0, "Home");
    await page.keyboard.press("End");
    await state(3, "End");
  }
  check(
    (await page.locator(".shlz-document-row").count()) > 0,
    "document rows",
  );
  await page.setViewportSize({ width: 390, height: 844 });
  check(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth + 1,
    ),
    "mobile overflow",
  );
  if (count === 4) {
    await tabs.nth(3).scrollIntoViewIfNeeded();
    check(await tabs.nth(3).isVisible(), "mobile last tab");
  }
  await context.close();
  const noJs = await browser.newContext({
      javaScriptEnabled: false,
      viewport: { width: 390, height: 844 },
    }),
    p = await noJs.newPage();
  await login(p);
  await p.goto(config.origin + "/pilot/objects/4512");
  check(
    (await p.getByText("Сроки и готовность", { exact: true }).count()) > 0,
    "no-js first panel source",
  );
  check(
    (await p.getByText("Плановое начало", { exact: true }).count()) > 0,
    "no-js first panel content",
  );
  await noJs.close();
} catch (error) {
  failures.push("exception " + error.message);
} finally {
  fs.writeFileSync(config.result, JSON.stringify({ failures }));
  await browser.close();
}
