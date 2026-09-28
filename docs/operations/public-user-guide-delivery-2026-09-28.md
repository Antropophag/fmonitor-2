# Public user guide — 2026-09-28

Owner request in this session authorizes updating the three Downloads guide/handoff files against production, adding real screenshots, publishing a public site section, removing administration instructions, and proofreading all Russian text. The owner explicitly selected fast delivery without the normal gate cycle and reiterated that production is the source of truth. This authorization applies only to the guide and its navigation links; it does not resume the unrelated paused delivery queue or authorize application/domain changes.

Target: `https://fmonitor.antropophag.ru/help/` (public; no login).
Source: `public/help/`. Navigation links: main sidebar and login page.

## Evidence and authors

Root scoped the change, prepared deployment/rollback, integrated screenshots, made the two navigation links, and ran focused browser/HTTP checks. Independent gpt-5.6-sol/low author `guide_content` checked and edited the guide; `guide_screenshots` captured real production UI backed by an isolated synthetic database. Root performed the final editorial adjustments. Independent gpt-5.6-sol/low `guide_final_review` reviewed content, routing and screenshots.

The production image label was stale. Actual `app/`, `config/`, and `public/` files were read from the running PHP container. A subsequent complete SHA-256 inventory matched that snapshot. No production data were copied into the fixture. No production mounting, financial, invitation or administration commands were submitted for screenshots.

Private evidence, source snapshot, screenshot manifest, payload, checks and review:
`/Users/antropophag/.local/share/fmonitor-2/guide-20260928/`.
Do not publish this evidence directory or the supplied CODEX_HANDOFF.

## Content and checks

25 chapters; 14 genuine UI screenshots with synthetic data. Removed administration, user/role management, integration monitoring and administrative feedback handling. Rewrote ОТиЗ for the actual production v2 workflow; corrected cross-device photo access and replacement-before-revocation guidance. All chapters, FAQs and tables received a Russian editorial pass.

Focused checks: PHP lint on navigation/login; JavaScript syntax; real Yii navigation permission matrix; browser widths 320/390/768/1024/1440; search (including е/ё), no-results recovery, Escape, section links, copied URLs, mobile menu, details and print. All 14 screenshot assets loaded under the strict CSP. No horizontal document overflow, JavaScript/CSP errors, or missing assets were observed. The navigation test's pre-existing completion-register expectations were aligned with the observed production permission split; production permissions were not modified.

Normal lifecycle/CI gates were not run under this explicit owner exception. Full local `make test` / `make verify` were not run. This is bounded guide verification, not a full-matrix GREEN claim.

## Deployment and rollback

`deploy/help/Caddyfile` preserves the production reverse proxy and adds only `/help` → `/help/` and an allowlisted static `/help/*` handler. It exposes HTML/CSS/JS/Markdown/WebP only. Working application routes retain their original authorization. CSP does not permit inline scripts/styles, network commands, forms, frames or external services.

The guide is stored in the gateway's existing persistent data volume at `/data/fmonitor-help/releases/20260928-public-guide`; `/data/fmonitor-help/current` points to that release. It survives gateway recreation. The live Caddyfile remains the existing host bind mount. Source assets and the two navigation files are also retained in the production checkout for future builds. Only the gateway needs a short restart to activate routing because its admin API is disabled.

Server deployment directory: `/home/antropophag/fmonitor-deployments/guide-20260928/`.
It contains the exact payload, prior Caddyfile/navigation/login copies, publication bytes and deployment timestamp. To roll back, restore those three `before/` files to the host paths, copy the two PHP files back to the PHP container, and restart only the gateway. Leave the static release and audit evidence intact.

## Publication result

Published successfully on 2026-09-28. Independent final review: APPROVED within the bounded guide scope. The anonymous production browser verified all 14 screenshot loads, search/recovery, copied production URLs, mobile menu, print and widths 320–1440. The served HTML matches the exact deployment payload. Anonymous `/help/` returns 200; `/help` redirects to `/help/`; original application and admin pages still redirect guests to login. Login exposes the guide link. Handoff, environment and traversal probes return 404. No asset, script or CSP failures were observed.
