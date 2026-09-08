# Independent focused review — manual feedback runtime package

- Verdict: **APPROVED**
- Reviewer: `/root/workforce_schedule_audit`; reviewer did not author the reviewed runtime or focused tests.
- Date: 2026-09-07.
- Scope: status vocabulary, applied Team/Documents projection, SHLZ document rows,
  original history/download presentation and authorization, and the final online bulk revision protocol.
- Limitation: focused manual-pilot review only; schema tests edited by this reviewer
  are excluded, and this is not full Gate 5, full verification, or production readiness.

## Findings

No blocking findings.

`InstallationStatusLabels` maps the six current presentation states and only the
known predecessor labels used by the native/rapid projections. It does not alias
`registered` into a current installation status. Object list, card and filter copy
share the mapping while persisted values remain unchanged.

The applied-card reader retains the native application composition for Team and
loads exactly one accepted original revision belonging to the applied order. It
projects the stored byte size, revision number and UTC upload time and supplies the
native revision download URL. `ObjectCardView` prefers that supplied URL and uses
the upload timestamp rather than the application/template timestamp. The common
`PilotDocumentView` escapes filename, URL and metadata and uses public SHLZ file-type
assets. Original history uses the same document row. Download admission remains
server-side: broad FKR/manager/OTiZ read roles are exact, a construction-control
engineer is additionally restricted to the currently applied assignment, and the
history/download owner scopes the requested revision to object and order. No legacy
`registered` writer or legacy artifact URL is introduced by this package.

The final checklist and construction-control clients share the first observed server
revision and serialize online bulk sends. The executable checklist harness observes
ordered revisions and stops after conflict; the companion control-queue harness
resumes the exact same dependency protocol. A shuffled IndexedDB reload containing
successor 2, the head, then successor 1 is reordered by persisted batch sequence and
sends items 28, 29, 30 with bases 0, 1, 2. Offline and unrelated concurrent-click
matrices remain outside this focused claim.

Original history renders the shared SHLZ document row and successful GET/HEAD HTML
receives the scripted shell CSP. File-type routes resolve public compiled SHLZ exports
and fall back to the public generic export; normalized sources and private assets are
not exposed. Status extraction targets the current badge, and raw
`needs_assignment_change` is explicitly presented as “Требуется изменение”.

The global qualification of PHP built-ins is mechanical and does not change inputs,
SQL, authorization or output behavior.

## Source evidence

```text
f78e6a16ec0365a395ade328cc0b0416e7e4f5e546743d68066aa53304744f0b  app/PilotHttp/InstallationStatusLabels.php
88d33fde8436105a86e2355098fd6d7bc758138e87c3c6bdaa20d0822ce12cb6  app/PilotHttp/ObjectListView.php
9e385786e79b81f7467731df415cb91815a8f1fdb2bbd878dbbdc3311993b689  app/PilotHttp/ObjectCardView.php
77bbab2bb45c9e10e28efe5e27f9221df3c35bf206ab9aab44a1bfb9df712b9b  rapid-pilot/ObjectQueue.php
d68a098abea24805379f3729b15a69e431a108ba9db8fdd3b981bcf468ac0250  rapid-pilot/ObjectDetails.php
355a634fb8182a9e787c7fd0863bb8fffb0acf7c0f84b929524db7452ed6d279  app/PilotHttp/MariaDbAppliedObjectCardReader.php
ea279d8b9f27eb96032d154e9fec8f01d65be33ff35ec5691e3fb1b30d214102  app/PilotHttp/PilotDocumentView.php
aee65f2a1a17498feb5bb7051ff6634dd30fe87173e3d125e1746c6c6cd13668  app/PilotHttp/OriginalHistoryHttpHandler.php
ae4cb4e7ad54749afecda2027705a2588de2b469d67a949bc32e7d42fc1b0986  app/PilotHttp/MariaDbOriginalHistoryAccess.php
f752e1a7b6230ec066359139ca5f726e651bb4b496cdb0ef040fa85f3d14dc72  app/PilotHttp/checklist.js
80f9137b1ef9993fd3f1f36b14687b31e512141c5f0cc987b9271f458e72f44c  app/PilotHttp/control-queue.js
0fda8fad99591d5a74157eeb7d7405984ad3eb672bf7f77c5f3afe6fb4c6b4cc  rapid-pilot/router.php
0c265799b26ea608b5ad5662fcd6d58ef48c7f1f96f790462e30f75bd8b614ee  tests/InstallationProcess/installation_status_labels_manual_test.php
51149fd9a18d5085196153df32ddd047a50764517bed3fad3b4f52d207ee918e  tests/AssignmentOrderComposition/manual_execution_http_smoke_test.php
acb55255dc9ffa56b71c0d820c06a44d70f6a18efb3fb53cacb9416aa9007d1e  tests/InstallationProcess/checklist_bulk_online_sequence_manual_test.php
a0dedb87cdbd51cc6cf49ebaf230aedea2c9216915b008c4edb24cc3d6b3a459  tests/InstallationProcess/control_queue_bulk_protocol_manual_test.php
```

## Verification

- Installation status vocabulary focused test — PASS.
- Manual original → application → opening → current object-card smoke — PASS.
- Original application-reference suite, including object/order ownership, correction,
  stale snapshot and nondisclosure cases for prefixes 0/25 — PASS.
- Checklist online bulk sequence test — PASS.
- Control-queue bulk protocol test — PASS.
- Exact HTTP checklist asset test — PASS.
- Checklist/control-queue JavaScript parse checks — PASS.
- Focused PHP lints and `git diff --check` — PASS.

Root's final headless golden run used an artificial 700 ms first-item response and
observed `bulkPaintedBeforeReply=true`, then completed 41 items, 7 photos and 7 work
sections from 85% through documentary closure to 100%, with no browser errors. This
review records the supplied result; the reviewer did not operate the stand.
