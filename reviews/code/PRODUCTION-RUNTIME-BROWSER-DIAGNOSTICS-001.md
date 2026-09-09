# Independent review — durable browser failure diagnostics

- Reviewer: `/root/review_startup`
- Author: `/root/root_compose_smoke`
- Date: 2026-09-09
- Verdict: **APPROVED**

Reviewed exact files:

```text
bd258859e02540b5105f027ee8cdb01d9558f5a8b9f90dc878afd174ede50494  tests/Support/pilot_current_flow_browser.cjs
640ae2a83f6b992dd25b603f566aabf2775eb439e1c59ef06daef13cf91f5d01  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The change adds failure attribution without changing the browser contract. The
existing `output.errors.push(request.failure()?.errorText)` call remains before the
diagnostic guard, and the existing final `output.errors.length` failure remains
unchanged. No error class is ignored, reclassified or removed from the assertion.

Each monitored page has a fixed test-owned name and a fixed current action label.
Owned popup/context closes and checklist/completion reloads are marked immediately
before execution. A failed request records only error text, HTTP method, URL
pathname without query, Playwright resource type, page name, current action and
closed state. It records at most 20 entries; error text is limited to 120 characters
and pathname to 240. It does not capture headers, bodies, query strings, cookies,
credentials, filesystem paths or full origins. All diagnostic extraction is inside
`try/catch`, after the original failure was recorded, so attribution cannot turn a
browser failure into a diagnostic exception or change the verdict.

The pilot E2E wrapper adds the bounded `requestFailures` value only to its existing
failure message. Successful execution does not print the diagnostic. The production
runtime wrapper already includes the complete bounded result artifact in its failure
message, so both failing CI paths can attribute the next occurrence.

An initial review found that the diagnostic list and strings were unbounded. The
author added the fixed limits above before this approval. Temporary skips and debug
journals are absent.

Focused verification:

```text
node --check tests/Support/pilot_current_flow_browser.cjs
exit 0, no output

php -l tests/InstallationProcess/pilot_e2e_flow_001_test.php
No syntax errors detected

git diff --check
exit 0, no output

isolated VM requestfailed probe
PASS bounded requestfailed diagnostic contract
```

The VM probe loaded the exact pre-main harness functions, emitted 25 synthetic
failures containing a query secret, and proved that all 25 original errors remained,
only 20 diagnostic records were retained, and the diagnostic pathname was exactly
`/safe/path`. A second request threw while reading metadata; its original error was
still appended while the diagnostic handler remained non-throwing.

Local attempts to execute the complete browser flows encountered unrelated retained
setup/overload failures before this instrumentation could be exercised. This review
therefore approves the bounded, exception-safe diagnostic source on the exact hashes
above; it does not claim a new browser GREEN or identify the cause of `ERR_ABORTED`.
The next same-source Linux CI occurrence must supply attribution before any lifecycle
fix is chosen.
