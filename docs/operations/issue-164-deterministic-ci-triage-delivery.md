# Delivery №164 — deterministic known-CI triage

## Authorization and authorship

- Owner assignment: 2026-09-16, issue #164 bounded T03 of #145, PR-ready only; no merge/deploy/settings.
- Autonomous continuation: owner explicitly authorized autonomous work through merge-ready on 2026-09-16 after Gate 2.
- Scope/spec/tests author: root Codex session.
- Gate 3 reviewer: `/root/gate3_review`, gpt-5.6-sol/low; initial suite and Gate 5 test deltas received append-only independent approvals after correction returns.
- Implementation author: `/root/executor`, gpt-5.6-sol/low; changed `tools/delivery/harness_context.py` only, including the correction requested by the first Gate 5 review.
- Gate 5 reviewer: `/root/gate5_review`, gpt-5.6-sol/low; two append-only correction returns followed by final `APPROVED` verdict.

## Bound source and evidence

- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` (merge PR #163).
- Contract: `specs/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md`.
- OpenSpec: `openspec/changes/deterministic-known-ci-triage/`.
- Planner: CRITICAL; required reviews `gate3`, `final`; no unresolved obligations.
- Gate 2 RED: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525474577776000-a9a93e18eaa9458ab96c9351a4f40e02.json`; eight failures solely because public `ci.triage` is absent.
- Gate 3 approval package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T022444Z-4b9d29de28/package.json`; candidate source `c568f52592ca9d4e07c8ecf64eee128cee60babb86ba4445a7d289dc2d85be30`.
- Gate 3 review: `reviews/tests/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md`.
- First Gate 5 return: `reviews/code/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md`; it found an incomplete native `REGRESSION_FAILURE` inventory and missing exact `e2e` setup coverage.
- Corrective Gate 3 RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526801929104000-44e7430e39ef48558c1b42caee861de0.json`; exactly two intended failures exercised the public native collector and `e2e` setup route.
- Corrective focused GREEN: triage `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526994377301000-f901258f5b404fc98d27c2f97afe4dda.json`; change verification `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527007704855000-36cbd151feb14394bb80bd0c51616c55.json`.
- Second Gate 5 return proved that real `gh run view --log` prefixes machine messages with job/step/timestamp columns. Gate 3 approved the realistic fixture; the executor added an exact three-column parser, and Gate 5 v3 independently reproduced it against live PR #144 evidence.
- Final pre-publication focused GREEN: triage `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527377495643000-bbdaf482cb4b470dae9bb7a9d09d2447.json`; change verification `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527382609415000-1193a09609f94e4e90d63bc6392db125.json`.

## Verification policy

Local canonical `make test`/`make verify` is forbidden. Executor runs planner-selected focused checks. Root performs one exact-source GitHub CI run after final approval. Full logs remain outside checkout; UNKNOWN is never approval or GREEN.

## Measurement

Historical PR #144 proxy (run `34933440293`, attempt 1):

| Path | Mandatory log payloads materialized before decision | Model-driven triage steps | Automatic retry count |
|---|---:|---:|---:|
| BEFORE: root manually inspected failed job log and decided | 1 full job log | 1 | 0 |
| AFTER: public deterministic triage reads one applicable job diagnostic | 1 bounded/applicable job-log payload; unrelated failed jobs 0 | 0 | 0 |

T03 returns permission/action only and does not dispatch the retry, so automatic retry count remains zero. The improvement is removal of semantic model investigation, not a claimed reduction in fetched log count. Owner-triggered retry in PR #144 is historical evidence, not an automatic action. Token usage and token savings are `UNKNOWN` because supported telemetry is absent.
