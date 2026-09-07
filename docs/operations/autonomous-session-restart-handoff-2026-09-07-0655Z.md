# Session restart checkpoint —2026-09-07 06:55 UTC /09:55 МСК

User requested a suitable point for session restart. STOP feature work at this
boundary in the current session; continue from here in the new session. Persistent
goal stays ACTIVE without token budget (not complete/blocked):

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Closing HEAD follows commit in answer. Base before this checkpoint:
f1f0b824cba97ff7b95582b98e5284295ce5d479, branch codex/remove-pilot-work-navigation-v2.
No production code changed during current delivery planning/RED work. All live
verification processes terminal, all owned TLS/PHP workers and temp fixture keys
cleaned. Review agents completed, none pending. Goal turn was progress, not a wait.

## Immediate next action: independent Gate3, not implementation

Change `fetch-bitrix-workforce-delivery`,2/6tasks done. Full proposal/design/delta/
tasks exist and strict validate PASS. Spec BITRIX-WORKFORCE-DELIVERY-001 v0.2,
SHA136c20f8a3d167cda67414198acaad605ec933f1b4bc76fd6188e723a949e7fc.
Gate1-v2 APPROVED in reviews/tests/BITRIX-WORKFORCE-DELIVERY-001-gate1-v2.md;
first changes_requested record retained. Reuse approval, don't redo it.

Delivery public factory/client/config/result under proposed namespace
FMonitor2\Workforce; NO implementation yet. Scope: native readonly HTTPS user.get,
complete pagination only, no normalization/publication/DB/cron/grant. Exact contract
covers safe config/token file, TLS peer+hostname/no redirects/proxy/netrc, bounded
body/person count, retries, monotonic deadline and immutable raw selected records.
No actual Bitrix run authorized. Both pending business decisions remain independent.

Final candidate tests needing independent review:
- tests/InstallationProcess/bitrix_workforce_delivery_001_test.php
- tests/Support/BitrixDeliveryFixture.php
- tests/Support/bitrix_delivery_client_worker.php
- tests/Support/bitrix_delivery_https_server.py

Final native REDv4:9healthy TLS setup/9cleanup/9 intended missing-factory failures,
exit1. All checks after absent factory still require actual GREEN; don't mistake
RED setup for full transport behavior verification. Final hashes, commands and
review scope: docs/operations/bitrix-delivery-red-checkpoint-2026-09-07.md.
Raw /Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907.

Reuse /root/original_gate3 via followup if available, or task a new independent
agent if this is a new thread. Give spec, all4files, final hashes and RED log.
Do not implement until Gate3 APPROVED. Reviewer must inspect true native TLS,
bounds/retries/deadline/privacy/copy/cleanup sensitivity; tests were authored by root.
Gate1 reviewer original_gate1_v3 and code reviewer original_gate5 remain reusable.

### Technical checkpoint details

Actual environment: PHP8.5.10/Curl8.22/OpenSSL3.6.4, Curl ASYNCHDNS and
CURLINFO_PRETRANSFER_TIME_T present; current old preview also supports Curl async,
pretransfer and POSIX. Native CurlHandle releases should avoid deprecated PHP8.5
curl_close noise. No production helper/factory/transport code was written.

Fixture generates CA/leaf via native openssl, serves loopback Python HTTPS,
and probes fixture-health with native Curl peer+hostname verification. Wrong-host
fixture uses matching wrong.invalid +Curl RESOLVE→127.0.0.1 only for healthy setup;
production candidate gets127.0.0.1 and must reject hostname. All target fetches run
in bounded PHP worker so broken pagination/flock/network can't hang the suite.
Worker keeps prior batches to test immutable values; only safe summaries on stdout,
records go to task-owned private files. Constructor network and actual TCP attempt
counts are checked. Certificate subprocesses bounded10s, PHP fetch budget+3s.

Body limits include exact1MiB valid large ignored time-string and exact16MiB total
(32pages×512KiB), plus overflows. Connect timeout uses actual TCP acceptance with
stalled TLS; request timeout stalls after native pretransfer. No native interception,
privilege/owner or OS-denial permission probes. Invalid secret mode0644 remains
readable and tests metadata rejection. Fixture roots use canonical OS temp for
Linux/Mac portability. Initial RED had a hardcoded Mac root; final draft does not.

Potential future work: many older SelectedOriginalFixture paths are still Mac-
specific; actual CI is unproven. Do not silently assume fixture portability just
because local makeverify reached its known E2E frontier. No workflow/CI publication
was done in this session.

## Earlier completed work and full context

Read authoritative prior handoffs as needed:
- docs/operations/autonomous-history-workforce-handoff-2026-09-07-0535Z.md
- docs/operations/autonomous-reference-users-handoff-2026-09-07-0425Z.md

They contain completed original upload UI, selected composition/template, original
application reference incl clone repair, production original history/download API,
canonical runner/frontier15 repair and local Users503 recovery. Reuse all approved
gates; don't reimplement/re-audit them without a new finding. No new schema version.
Latest history/download sourceeec882f274902c3d4842aa73d4665411b0a855aa:18native tests,
4regressions/architecture/lint/diff and Gate5 approved; not wired to full HTTP roles.

Latest full makeverify was clean-start18916aef904e37ddfbcf8afd9adb6abcdf642654,
exit2: only pilot_demo_bootstrap and protected pilot_e2e_flow, stagesdb/e2e. Other
stages PASS. It predates clone repair/history API and current delivery tests.
No literal VERIFY_OK, no launch readiness. Protected E2E SHA remains
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.
Do not weaken tests or restore obsolete manual-registration writers to get green.

## Required owner answers — still unanswered

1. After composition applied but before opening, allow explicit reapplication of
   a corrected original date preserving the earlier application fact, or freeze
   applied date? apply-assignment-order-original-to-composition remains only
   proposal+NEEDS_GRILL. No uniqueness/schema/version16 decision before answer.
2. Allow new assignment from confirmed current status of a FULL workforce snapshot
   when Bitrix doesn't provide employment start, retaining unknown date, or require
   confirmed employment date? Never substitute sync-day as employed_from. Native
   selection currently requires a known date; changing it needs fresh gates.

Goal continuations/elapsed time/default selected options are not answers. Document
new answers before dependent behavior. Prospective new order's effective date is
already approved DOCUMENT DATE, not upload/apply time; do not ask that again.
Freshness age threshold is also not established merely by hourly cadence.

## Persistent limits and preview

No remote mutation in this session, no PR10 modification/merge, no QG/bootstrap CI
publication before first full literalVERIFY_OK. No real Bitrix run/production imports.
Only public official Bitrix docs were read; live payload/credential readiness remains
unverified. Broad BITRIX-WORKFORCE-HISTORY-001 is NOT EXECUTABLE, not Gate2 authority.
Current actor/grant mapping/full history HTTP needs actual native applied engineer;
never substitute legacy responsstroicontrol. Application/opening/golden path pending.

Preview8092 still old1eba93cf imageID8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b,
Users200 recovered with strict preservation. Same volumes and readonly runtime-only
startup override +old healthcheck mount. Do NOT revert to ordinary bootstrap during
restart; it updates auth metadata. New feature source NOT deployed. Credentials/
cookies/raw DB snapshots external0600; NEVER print preview.env. See prior handoffs.

Read AGENTS,PRODUCT,CONTEXT,pilot specs/data-model and development-process. Public
shlz-ui only; owner overrode Windows ServiceDesk search. ../fmonitor read-only.
Primary evidence/secrets outside repo, no baseline ratchet/new>=150line production
hotspots. PATH /opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin.
Test DB127.0.0.1:23306 root/fmonitor2_test_root_local, only for synthetic tests.
