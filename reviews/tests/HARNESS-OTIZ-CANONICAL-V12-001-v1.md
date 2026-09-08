# Gate 3 test-amendment review — HARNESS-OTIZ-CANONICAL-V12-001 v1

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
The reviewer authored none of the specification, RED evidence, proposed patch,
existing harness, migration owner, OTIZ oracle, or protected dependencies.

Verdict: **APPROVED**.

This approval applies only to the exact unapplied patch hash below. It permits
the patch author to apply those bytes and proceed to minimal test-only GREEN.
It is not production approval, Gate 5, parent OpenSpec completion, integration
approval or launch approval. Any test change beyond the exact reviewed patch
returns to Gate 2/3.

## Traceability and RED

The approved supporting specification requires a prepared canonical v12
database, then an exact in-harness no-op migration result. The RED record proves
that prerequisite independently before invoking the old harness:

```text
make migrate
exit 0
stdout {"ok":true,"schemaVersion":12,"appliedVersions":[]} plus LF
stderr empty
```

The unchanged old harness then exited 255 at its exact terminal-version
assertion: expected 11, actual 12. The embedded migration evidence again showed
status 0, the exact v12/empty-list stdout and empty stderr. The failure is
therefore caused by the stale fixture expectation after landed production v12,
not missing production behavior, environment setup, database availability or a
business assertion. The raw log hash matches the durable RED record and the
old harness remains byte-identical at review time.

## Proposed-patch review

The patch applies cleanly to the exact old harness and changes one file only:

- replaces status-only plus last-JSON-line/subset checks with one exact array
  comparison over the complete child result: status 0, stdout exactly one
  v12/empty-appliedVersions JSON line plus LF, stderr empty;
- removes arbitrary unique-subset/range acceptance and cannot accept extra
  stdout lines or JSON keys;
- adds only `fm2_pilot_object_details` and
  `fm2_pilot_object_detail_quarantine` to the existing canonical-state table
  inventory, causing their SHOW CREATE definitions and ordered rows to enter
  the same before/after, injected-failure and final-restoration comparisons;
- changes only three assertion/summary labels from v1–v11 to v1–v12.

The expected process bytes come directly from the approved canonical v12 public
contract and the independently prepared RED prerequisite, not from a loose
production-output parser. The two table identities come from the exact approved
v12 manifest. No optional membership, dynamic catalog discovery, broad version
comparison or implementation-derived range is introduced.

No fixture row is inserted into either v12 table. They are absent from the
sentinel installer/remover and auto-increment restoration list, as required.
Their existing definitions and rows are only observed and compared. Missing or
unreadable tables remain setup failures through `hoccCanonicalState`; any
mutation remains a regression failure.

The patch does not alter OTIZ isolation invocation, financial transcript regex,
sentinel values, permission/count assertions, two-run determinism, injected
`REGRESSION_FAILURE: injected after fixtures`, private/noncanonical cleanup,
dependency-safe sentinel cleanup, original state restoration, environment,
imports or production code. It adds no AI restoration path and touches no
protected E2E file/spec/fixture/dependency/registration.

## Sensitivity and application boundary

The old assertion fails on the healthy v12 predecessor and the reviewed patch
would make that exact mismatch pass while continuing to reject nonempty applied
versions, malformed/extra output, missing v12 tables, preservation damage or
changed OTIZ behavior. This is the minimum sensitive amendment.

Apply only `docs/operations/patches/harness-otiz-canonical-v12-v1.patch` after
rechecking its SHA-256. GREEN must run the harness against an independently
prepared exact v12 database, both normal OTIZ runs and injected cleanup, plus
relevant characterization, syntax/architecture and diff checks. The harness's
documented unprepared-input behavior is outside this test input and must not be
used to manufacture GREEN.

## Exact reviewed SHA-256

```text
97cc7fd60a6fa469eb45c298934e6bd2e47355e62cb0945d94872e7609a332e1  docs/operations/patches/harness-otiz-canonical-v12-v1.patch
f0c2e1119ef37f149fc0355c5c6de36edc0ca6afe31f8e4655ccd8d0dd39afc2  specs/HARNESS-OTIZ-CANONICAL-V12-001.md
ffae163c5177a950d8dcf66e8a52ee00947af9350bce356577f9c1d6ff95601f  docs/operations/harness-otiz-canonical-v12-red-2026-09-05.md
f79330443ead1c099480c33747f9c6b988b70a3b651743e6a9c269abd3b4cbfe  /tmp/fmonitor2-otiz-v12-red.log
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php (unapplied input)
eacaa338c4beaa6320a67b5c4ca23ef3c2b6e4756da6d7b9e2b6c94f412dcea2  docs/operations/harness-otiz-canonical-v12-gate1-review-2026-09-05.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (protected unchanged)
```
