const fs = require("node:fs"),
  crypto = require("node:crypto");
const c = JSON.parse(fs.readFileSync(process.argv[2], "utf8")),
  { chromium } = require(c.playwright);
(async () => {
  const browser = await chromium.launch({ headless: true }),
    mode = c.mode || "populated",
    evidence = { mode, viewports: {}, screenshots: {} };
  try {
    for (const width of [1440, 1201, 1200, 1051, 900, 681, 680, 390]) {
      const context = await browser.newContext({
          viewport: { width, height: 1000 },
        }),
        page = await context.newPage(),
        browserErrors = [];
      page.on("console", (message) => {
        if (message.type() === "error") browserErrors.push(message.text());
      });
      page.on("pageerror", (error) => browserErrors.push(error.message));
      await page.goto(c.origin + "/pilot/login");
      await page.locator("[name=email]").fill(c.email);
      await page
        .locator("[name=email]")
        .locator("xpath=ancestor::form")
        .locator("button[type=submit]")
        .click();
      await page.locator("[name=password]").fill(c.password);
      await page
        .locator("[name=password]")
        .locator("xpath=ancestor::form")
        .locator("button[type=submit]")
        .click();
      const response = await page.goto(c.origin + "/pilot/dashboard"),
        expectedStatus = mode === "error" ? 503 : 200;
      const csp = response.headers()["content-security-policy"] || "";
      if (response.status() !== expectedStatus)
        throw new Error(mode + " dashboard status " + response.status());
      const bars = page.locator("[data-dashboard-bar]"),
        count = await bars.count();
      if (mode === "error") {
        if (
          count !== 0 ||
          !(await page.locator("main").innerText()).includes(
            "Данные временно недоступны",
          )
        )
          throw new Error("error state leaked charts");
      } else {
        for (const title of [
          "Объекты по этапам процесса",
          "Плановая нагрузка на 6 недель",
          "Риск срыва ближайших стартов",
        ])
          if (!(await page.locator("main").innerText()).includes(title))
            throw new Error("missing " + title);
        if (count !== 23) throw new Error("expected 23 bars, got " + count);
        if (mode === "empty") {
          for (const text of await bars.allTextContents())
            if (!/\b0\b/.test(text))
              throw new Error("fabricated empty bar " + text);
          if (
            !(await page.locator("main").innerText()).includes(
              "Объектов пока нет",
            )
          )
            throw new Error("empty explanation missing");
        } else {
          const first = bars.first();
          await first.focus();
          const name = await first.getAttribute("aria-label");
          if (!name || !name.includes(":") || !name.match(/\d/))
            throw new Error("bar accessible name " + name);
          const expectedHref = await first.getAttribute("href");
          if (!expectedHref) throw new Error("bar has no drill-down href");
          const focus = await first.evaluate(
            (e) =>
              getComputedStyle(e).outlineStyle !== "none" ||
              getComputedStyle(e).boxShadow !== "none",
          );
          if (!focus) throw new Error("bar focus is not visible");
          await first.press("Enter");
          await page.waitForURL(
            (url) =>
              url.pathname === "/pilot/objects" &&
              url.searchParams.has("chart"),
          );
          if (!page.url().endsWith(expectedHref))
            throw new Error(
              "keyboard activation mismatch " + page.url() + " " + expectedHref,
            );
          await page.goBack();
        }
      }
      const geometry = await page.evaluate(() => ({
        scroll: document.documentElement.scrollWidth,
        viewport: document.documentElement.clientWidth,
        widgets: document.querySelectorAll("[data-dashboard-chart]").length,
        unsafeInlineMarks: document.querySelectorAll(
          '[data-dashboard-bar] [style*="--fm2-bar"]',
        ).length,
        marks: [...document.querySelectorAll("[data-dashboard-bar]")].map(
          (bar) => ({
            value: Number(
              bar.querySelector("[data-dashboard-value]")?.textContent || 0,
            ),
            rectHeight: Number(
              bar.querySelector(".fm2-chart-bar__mark rect")?.getAttribute("height") || 0,
            ),
          }),
        ),
        weekPairs: [...document.querySelectorAll(".fm2-chart-week__bars")].map(
          (pair) => {
            const frames = [
              ...pair.querySelectorAll(".fm2-chart-bar__mark-frame"),
            ].map((element) => element.getBoundingClientRect());
            return {
              widths: frames.map((box) => box.width),
              overlap: frames.length === 2 && frames[0].right > frames[1].left,
            };
          },
        ),
        stageColors: [...document.querySelectorAll('[data-dashboard-chart="stages"] .fm2-chart-bar__mark')].map((e) => getComputedStyle(e).color),
        weekColors: [...document.querySelectorAll('[data-dashboard-chart="weeks"] .fm2-chart-bar__mark')].slice(0,2).map((e) => getComputedStyle(e).color),
        riskColors: [...document.querySelectorAll('[data-dashboard-chart="start-risk"] .fm2-chart-bar__mark')].map((e) => getComputedStyle(e).color),
        stageMarks: [...document.querySelectorAll('[data-dashboard-chart="stages"] [data-dashboard-bar]')].map((bar)=>({value:Number(bar.querySelector('[data-dashboard-value]').textContent),height:Number(bar.querySelector('rect').getAttribute('height'))})),
        kpis: [...document.querySelectorAll(".fm2-dashboard-metric")].map((e) => ({height:e.getBoundingClientRect().height,valueY:e.querySelector(".fm2-dashboard-value").getBoundingClientRect().y,rowY:e.getBoundingClientRect().y})),
        stageValueY: [...document.querySelectorAll('[data-dashboard-chart="stages"] .fm2-chart-bar__value')].map((e) => e.getBoundingClientRect().y),
        stageTracks: [...document.querySelectorAll('[data-dashboard-chart="stages"] [data-dashboard-bar]')].map((bar)=>({markY:bar.querySelector('.fm2-chart-bar__mark-frame').getBoundingClientRect().y,valueY:bar.querySelector('.fm2-chart-bar__value').getBoundingClientRect().y,labelY:bar.querySelector('.fm2-chart-bar__label').getBoundingClientRect().y})),
        weekRanges: [...document.querySelectorAll(".fm2-chart-week__label")].map((e) => ({parts:e.querySelectorAll("span").length,height:e.getBoundingClientRect().height})),
        repeatedWeekLabels: [...document.querySelectorAll('[data-dashboard-chart="weeks"] .fm2-chart-bar__label')].length,
        weekColumns: document.querySelector(".fm2-chart-weeks") ? getComputedStyle(document.querySelector(".fm2-chart-weeks")).gridTemplateColumns.split(" ").length : 0,
        textClipped: [...document.querySelectorAll("[data-dashboard-chart] h2,[data-dashboard-chart] p,.fm2-chart-bar__label,.fm2-chart-week__label")].some((e) => e.scrollWidth>e.clientWidth+1||e.scrollHeight>e.clientHeight+1),
        pairedOffsets: [...document.querySelectorAll('[data-dashboard-chart="weeks"],[data-dashboard-chart="start-risk"]')].map((chart)=>({header:chart.querySelector('.shlz-chart-widget__header').getBoundingClientRect().height,mark:chart.querySelector('.fm2-chart-bar__mark-frame').getBoundingClientRect().y-chart.getBoundingClientRect().y})),
        labels: [...document.querySelectorAll("[data-dashboard-bar]")].map(
          (e) => ({
            text: e.textContent.trim(),
            name: e.getAttribute("aria-label"),
            href: e.getAttribute("href"),
          }),
        ),
        boxes: [...document.querySelectorAll("[data-dashboard-chart]")].map(
          (e) => {
            const r = e.getBoundingClientRect(),
              bars = [...e.querySelectorAll("[data-dashboard-bar]")];
            return {
              x: r.x,
              y: r.y,
              width: r.width,
              clientWidth: e.clientWidth,
              scrollWidth: e.scrollWidth,
              barsInside: bars.every((bar) => {
                const b = bar.getBoundingClientRect();
                return b.left >= r.left - 1 && b.right <= r.right + 1;
              }),
            };
          },
        ),
        clipped: [
          ...document.querySelectorAll(
            "main [data-dashboard-chart],main [data-dashboard-bar]",
          ),
        ].some((e) => {
          const r = e.getBoundingClientRect();
          return (
            r.left < 0 || r.right > document.documentElement.clientWidth + 1
          );
        }),
      }));
      if (
        geometry.scroll > geometry.viewport + 1 ||
        geometry.clipped ||
        geometry.boxes.some(
          (b) => b.scrollWidth > b.clientWidth + 1 || !b.barsInside,
        )
      )
        throw new Error(
          mode + " responsive/widget geometry " + JSON.stringify(geometry),
        );
      if (mode !== "error" && geometry.widgets !== 5)
        throw new Error("widget count " + geometry.widgets);
      if (mode === "populated") {
        const nonzero = geometry.marks.filter((mark) => mark.value > 0);
        const zeros = geometry.marks.filter((mark) => mark.value === 0);
        const kpiRows = Object.values(Object.groupBy(geometry.kpis,(item)=>Math.round(item.rowY)));
        if (
          browserErrors.some((error) => /content security policy|refused|uncaught/i.test(error)) ||
          !/style-src 'self'(?:;|$)/.test(csp) || /unsafe-inline/.test(csp) ||
          geometry.unsafeInlineMarks !== 0 ||
          nonzero.length === 0 ||
          nonzero.some((mark) => mark.rectHeight <= 4) ||
          new Set(nonzero.map((mark) => mark.value)).size > 1 && new Set(nonzero.map((mark) => mark.rectHeight)).size < 2 ||
          zeros.some((mark) => mark.rectHeight !== 4) ||
          JSON.stringify(geometry.stageColors) !== JSON.stringify(["rgb(212, 126, 46)","rgb(36, 91, 153)","rgb(65, 145, 179)","rgb(129, 49, 167)","rgb(37, 152, 62)","rgb(169, 66, 167)"]) ||
          JSON.stringify(geometry.weekColors) !== JSON.stringify(["rgb(61, 136, 222)","rgb(212, 126, 46)"]) ||
          JSON.stringify(geometry.riskColors) !== JSON.stringify(["rgb(179, 38, 30)","rgb(212, 126, 46)","rgb(195, 154, 36)","rgb(36, 91, 153)","rgb(37, 152, 62)"]) ||
          JSON.stringify(geometry.stageMarks) !== JSON.stringify([{value:8,height:104},{value:3,height:41},{value:9,height:116},{value:2,height:29},{value:1,height:16},{value:1,height:16}]) ||
          kpiRows.some((row) => Math.max(...row.map((x)=>x.height))-Math.min(...row.map((x)=>x.height))>1 || Math.max(...row.map((x)=>x.valueY))-Math.min(...row.map((x)=>x.valueY))>1) ||
          Math.max(...geometry.stageValueY)-Math.min(...geometry.stageValueY)>1 ||
          ['markY','valueY','labelY'].some((key)=>Math.max(...geometry.stageTracks.map((track)=>track[key]))-Math.min(...geometry.stageTracks.map((track)=>track[key]))>1) ||
          geometry.weekRanges.some((range)=>range.parts!==2||range.height<20) ||
          geometry.repeatedWeekLabels !== 0 ||
          geometry.textClipped ||
          (width>1200 && (Math.abs(geometry.pairedOffsets[0].header-geometry.pairedOffsets[1].header)>1 || Math.abs(geometry.pairedOffsets[0].mark-geometry.pairedOffsets[1].mark)>1)) ||
          (width<=680 ? geometry.weekColumns!==2 : geometry.weekColumns!==6) ||
          geometry.weekPairs.some(
            (pair) =>
              pair.widths.length !== 2 ||
              pair.overlap ||
              Math.abs(pair.widths[0] - pair.widths[1]) > 1,
          )
        )
          throw new Error(
            "INTENDED_RED CSP-safe equal proportional marks " +
              JSON.stringify(geometry),
          );
      }
      if (
        mode !== "error" &&
        width > 1200 &&
        !(
          geometry.boxes[0].width>geometry.boxes[1].width &&
          Math.abs(geometry.boxes[1].width-geometry.boxes[2].width)<2 &&
          Math.abs(geometry.boxes[1].y-geometry.boxes[2].y)<2 &&
          Math.abs(geometry.boxes[3].width-geometry.boxes[4].width)<2 &&
          Math.abs(geometry.boxes[3].y-geometry.boxes[4].y)<2 &&
          geometry.boxes[0].y<geometry.boxes[1].y && geometry.boxes[1].y<geometry.boxes[3].y
        )
      )
        throw new Error(
          "desktop full/paired geometry " + JSON.stringify(geometry.boxes),
        );
      if (
        mode !== "error" && width <= 1200 && width > 900 &&
        !(
          geometry.boxes[0].y<geometry.boxes[1].y &&
          Math.abs(geometry.boxes[1].y-geometry.boxes[2].y)<2 &&
          geometry.boxes[2].y<geometry.boxes[3].y &&
          geometry.boxes[3].y<geometry.boxes[4].y
        )
      )
        throw new Error(
          "intermediate primary pair geometry " + JSON.stringify(geometry.boxes),
        );
      if (
        mode !== "error" &&
        width <= 900 &&
        !(
          geometry.boxes[0].y < geometry.boxes[1].y &&
          geometry.boxes[1].y < geometry.boxes[2].y &&
          geometry.boxes[2].y < geometry.boxes[3].y &&
          geometry.boxes[3].y < geometry.boxes[4].y
        )
      )
        throw new Error(
          "mobile document flow " + JSON.stringify(geometry.boxes),
        );
      if (mode === "populated" && [680,390].includes(width)) {
        const target = page.locator('[data-dashboard-chart="start-risk"] [data-dashboard-value]').last();
        await target.scrollIntoViewIfNeeded();
        if (await target.evaluate((element) => { const r=element.getBoundingClientRect(),hit=document.elementFromPoint(r.left+r.width/2,r.top+r.height/2);return !(hit===element||element.contains(hit)); })) throw new Error("INTENDED_RED mobile navigation obscures required chart value");
      }
      const shot = c.artifacts + `/dashboard-bar-charts-${mode}-${width}.png`;
      await page.screenshot({ path: shot, fullPage: true });
      const bytes = fs.readFileSync(shot);
      evidence.viewports[width] = geometry;
      evidence.screenshots[width] = {
        path: shot,
        sha256: crypto.createHash("sha256").update(bytes).digest("hex"),
        bytes: bytes.length,
      };
      if (mode === "populated" && width === 1440) {
        let version = null;
        for (const route of ["dashboard","calendar","objects","admin/users","admin/roles","objects"]) {
          await page.goto(c.origin + "/pilot/" + route);
          const paint = await page.evaluate(() => {
            const read = (name) => {
              const path = document.querySelector(`[data-shlz-icon="${name}"] path`);
              if (!path) return null;
              const style = getComputedStyle(path);
              return { fill: style.fill, stroke: style.stroke, width: style.strokeWidth };
            };
            const hrefs = [...document.styleSheets].map((sheet) => sheet.href || "").filter((href) => /pilot\.css\?v=/.test(href));
            return { icons:Object.fromEntries(["bar-chart-square-plus","docs","calendar-interface","graph","settings","eye","user"].map((name)=>[name,read(name)])), hrefs };
          });
          for (const name of ["eye","user"]) { const icon=paint.icons[name]; if(!icon||icon.fill!=="none"||icon.width!=="1.5px") throw new Error("INTENDED_RED stable stroke navigation "+route+" "+JSON.stringify(paint)); }
          for (const name of ["bar-chart-square-plus","docs","calendar-interface","graph","settings"]) { const icon=paint.icons[name]; if(!icon||icon.fill==="none") throw new Error("INTENDED_RED stable fill navigation "+route+" "+JSON.stringify(paint)); }
          for (const href of paint.hrefs) {
            const current = new URL(href).searchParams.get("v");
            const response=await page.request.get(href),digest=crypto.createHash("sha256").update(await response.body()).digest("hex").slice(0,12);
            if (current!==digest || (version !== null && version !== current)) throw new Error("INTENDED_RED shared content asset version " + JSON.stringify(paint.hrefs));
            version = current;
          }
        }
      }
      await context.close();
    }
    fs.writeFileSync(c.result, JSON.stringify(evidence));
  } finally {
    await browser.close();
  }
})().catch((e) => {
  process.stderr.write(e.stack || String(e));
  process.exit(1);
});
