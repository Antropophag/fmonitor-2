# Protected pilot E2E current-flow — independent test review

Reviewer: `/root/photo_review`; artifact author: `/root/e2e`.
Verdict: **APPROVED** for the bounded protected verifier package.
Reviewed source HEAD: `795ac3e` plus the exact uncommitted artifacts below.

The historical protected SHA
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`
and retained/superseded map were checked independently. The final package retains
authorization, upload/opening/checklist transport, replay/concurrency/rollback,
original history/download, append-only data, fresh-connection, redacted-failure and
cleanup evidence through twelve explicit child contracts plus the current browser
journey. Removed assertions are limited to superseded list representation, manual
registration, separate apply UI and legacy split artifacts.

Review findings were resolved before this verdict: missing HTTP opening/history
contracts were added; template evidence now uses original server PDF bytes and proves
media, inline disposition, length, digest, passive profile and no browser download;
current original GET/HEAD proves header parity, empty HEAD, exact bytes and zero
DB/storage delta; fixture setup asserts exact canonical v19; and the assertion map
now cites the actual authority/fresh-connection/sentinel evidence.

The checklist waits for server-observed accepted item, photo and completed-section
counts after every section. This fixes premature verifier automation after an
optimistic section badge without adding a blanket retry or ignoring 409 responses.

Exact reviewed artifacts:

```text
71d02b054b42ef63483ce49d4494d21f08e4b62b7362f8eb1ea71e04a2590deb  tests/InstallationProcess/pilot_e2e_flow_001_test.php
9193e3454f758af023d8e40499139ec9b122d1f876a906a8fd03c00bd469eae7  tests/Support/pilot_current_flow_browser.cjs
b59339b637336017306aa97ac8a23957209d86c42351a4ca4034adddacb9108f  tests/Support/SelectionHttpFixture.php
d90abbf0959268f63854ce0304544d2aeacf5646fd18e6d416849ba1eaf87f34  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
c9577a8da0cbd780dff7ca691c671f55fb5760c3b4944d0519ddb2ce5db0275e  openspec/changes/reconcile-protected-pilot-e2e-current-flow/assertion-map.md
0acd93475732fdf42cdd0d3696c3e2a948869aec02f80963a6efd59809c20107  openspec/changes/reconcile-protected-pilot-e2e-current-flow/specs/verification/protected-pilot-e2e-current-flow/spec.md
7674ee96145e1e7baf69933be2ea0bf439be8f9185b8d349871a3d0218a98964  openspec/changes/reconcile-protected-pilot-e2e-current-flow/tasks.md
93910e82cc0085a061f2df3914aad3971acfb84609291fe7a96dbb904f0380f6  docs/operations/protected-current-e2e-evidence-2026-09-07.md
```

The tasks/evidence hashes were updated after approval only to record the reviewed
verdict and already-proven bounded checks. Tasks 4.3 (standalone protected plus whole
bootstrap GREEN evidence) and 4.5 (full exact-SHA verify) correctly remain open;
behavior artifacts did not change.

`protected-current-bootstrap-final-4.log` SHA-256
`15345f96643b7966fc3be3b7d51d2d70bdb21c1ecd74c9bcf3fdf2d6401731ad`
reached bootstrap line 149, which is possible only after its protected-child checks
observed exit 0, empty stderr and PASS. The caller later failed its unrelated existing
post-spawn CSS adversary 12-second bind assertion; no whole-bootstrap GREEN is claimed.
Root separately inspected synthetic `checklist-85.png` and `card-100.png`: the full
UI showed 41/41, seven photos, 85%, then documents and 100%, without clipped blocking
content. This is synthetic evidence, not stand deployment.

Evidence limitation: an earlier HTTP 409 diagnostic raw log was overwritten before
preservation. Its revision-18 excerpt exists only in the tool/session transcript;
no raw file or hash is reconstructed. The later diagnosed cause was verifier timing,
and final server-accepted waits are reviewed above. Full exact-SHA `make verify` and
deployment remain separate.
