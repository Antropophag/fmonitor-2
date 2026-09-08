# DATA-INTEGRITY-001 existing test compatibility inventory

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `86a605239243b2ebf497c8173cd002703c070623`.
Status: read-only compatibility inventory before Gate 1 disposition.

No production code, tests or specifications were changed. This inventory does
not approve a patch or convert an existing failure into a skip.

## Reviewed contract

```text
0f116462166cf7db03c13a7f126357464c3a5eb05da12c65224243ea6bd14af7  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
```

The contract is under separate independent Gate 1 review. The items below are
conditional compatibility work after exact Gate 1 approval; they are not authority
to edit tests beforehand.

## Summary

Three patch classes must remain distinct:

1. **Mechanical public-shape repair**: add complete lineage metadata/methods or
   pass the new optional dependency without changing an existing expected result.
   These still require an exact reviewed test patch because interface changes can
   hide behavior failures behind setup fatals.
2. **Positive fixture truth repair**: replace noncanonical content identity or
   composition hash with exact contract literals. These change an existing oracle
   and require fresh mismatch evidence and independent Gate 3 before application.
3. **Behavior expectation change**: replace ordinary repository “second lookup”
   recovery with the new fresh-reader protocol and update exact traces/call counts.
   This is a new normative dependency path and requires RED/review; it is not a
   helper-only compatibility edit.

## A. Complete FOUND lineage helpers

Every FOUND lineage must implement
`AssignmentOrderOriginalCompleteLineageLookup` and expose:

```text
installationCaseId = 4512
assignmentOrderId = 81 (or the fixture's exact order)
revisionIds = ordered immutable revision IDs
currentDocumentDate = exact current date
currentPdfSha256 = exact current PDF hash
```

The base root/current/revision/composition getters and `containsRevision()` must
agree with that list. Do not infer these new fields from caller arguments in the
application.

Concrete patch sites:

- `tests/Support/AssignmentOrderOriginalDynamicPortsFixture.php:35-45` —
  `OriginalDynamicRepository::findLineage()` returns a FOUND
  `AssignmentOrderOriginalMariaDbLineage` for correction. Add exact case `4512`,
  order `81`, and `['revision-0001']`; retain date `2026-09-01` and PDF hash
  `4028af...8784`.
- `tests/Support/AssignmentOrderOriginalLogIsolationFixture.php:91-101` — the
  CAS-conflict assignment-order lookup needs the same case/order/list metadata.
- `tests/Support/AssignmentOrderOriginalLifecycleFixture.php:121-127` — conflict
  winner lineage needs case `4512`, order `81`, and the winner's single revision
  ID. Its existing current evidence must remain tied to that winner.
- `tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php:58-69`
  — `Gate5DomainLineage` must implement the complete extension. Use the contained
  commit's `installationCaseId`, `assignmentOrderId`, and one-element ordered
  `[newRevisionId]`, not fixed caller values.

`AssignmentOrderOriginalInitialLineageLookup` in
`tests/Support/AssignmentOrderOriginalInitialFixture.php:222-231` is NOT_FOUND.
The extension is optional for negative states, so it does not require a patch;
its existing null base metadata is compatible.

These are public-shape repairs. Any existing test that deliberately supplies
FOUND without the extension to prove protocol failure belongs in the new
DATA-INTEGRITY matrix and must not reuse these positive helpers.

## B. Canonical accepted-result and prior-winner literals

Existing positive `AssignmentOrderOriginalResultValue` instances have canonical
UUIDs, status/reason/retryability, root/revision IDs, revision number, date, PDF
hash, size and UTC second in these files:

- `tests/Support/AssignmentOrderOriginalLifecycleFixture.php:100-101`;
- `tests/Support/AssignmentOrderOriginalLogIsolationFixture.php:105,113`;
- `tests/Support/AssignmentOrderOriginalShapeFixture.php:33`.

Their result payloads need no scalar rewrite. Preserve the rule that terminal
FOUND result request ID equals the query; fingerprint winners may keep a different
stored request ID and are replayed under the current caller request.

However, the literal prior/winner `AcceptedCommit` values backing positive FOUND
evidence contain a noncanonical private identity at these sites:

- `tests/Support/AssignmentOrderOriginalLifecycleFixture.php:98`;
- `tests/Support/AssignmentOrderOriginalShapeFixture.php:26-29`.

Replace `private-content-0001` with:

```text
content-sha256-4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
```

This is a positive fixture truth repair. It must be reviewed together with every
expected evidence/trace string affected by the same identity.

## C. Shared content fixture and expected evidence

`tests/Support/AssignmentOrderOriginalInitialFixture.php:133-143` returns
`private-content-0001` from the successful content object. After accepted-commit
validation this makes every otherwise valid application commit malformed.

Change the helper to return `content-sha256-<its exact digest>`. This propagates to:

- `tests/InstallationProcess/assignment_order_original_upload_001_test.php` —
  update the exact expected evidence at line 140 to the full content-sha256 value;
- `tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php`;
- `tests/InstallationProcess/assignment_order_original_command_shape_001_test.php`;
- `tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php`;
- `tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php`;
- `tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php`;
- every graph using `AssignmentOrderOriginalInitialStorage` through those shared
  helpers.

Only the upload evidence test currently asserts the old private identity directly,
but all positive paths depend on it semantically. This is an expectation change,
not a harmless constructor addition. Preserve a fresh pre-patch failure showing
the new validator rejects the old synthetic identity, then independently review
the exact helper plus expected-evidence patch.

The lifecycle fixture has its own content class at
`tests/Support/AssignmentOrderOriginalLifecycleFixture.php:30-36`. Change its
valid identity to the same digest-derived literal and update
`tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php:18`
and every exact FINALIZE_DONE trace derived from `$finalize`. Keep `bad/id` as the
intentional malformed case. This lifecycle oracle patch needs its own fresh RED
and Gate 3 because many literal traces change.

Real worker/race/lease fixtures already use `content-sha256-<pdfSha256>` and need
no expectation repair for this rule.

## D. Composition FOUND helpers and hash recomputation

Already canonical positive helpers:

- `AssignmentOrderOriginalInitialCompositionReader` in
  `tests/Support/AssignmentOrderOriginalInitialFixture.php:88-105` — case/order
  echo, identity `composition-81-v1`, installers `[7001,7002]`, engineer `31`,
  hash `388c7d...0faba5` all agree.
- `OriginalShapeComposition` in
  `tests/Support/AssignmentOrderOriginalShapeFixture.php:14-18` — same canonical
  tuple/hash.
- lifecycle anonymous composition at
  `tests/Support/AssignmentOrderOriginalLifecycleFixture.php:151` — same.
- log-isolation graphs use the shared initial composition reader.

Intentional invalid composition rows in
`tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php:142`
must remain invalid. Its FOUND snapshot deliberately has empty installers/null
engineer and must continue selecting `INVALID_COMPOSITION`; do not “repair” its
zero hash into a positive control.

One positive helper is wrong:

- `tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php:48-60`
  computes `hash('sha256', 'composition-'.$orderId)`, which is not the normative
  canonical JSON hash. Replace it with exact independently fixed values for its
  one-installer fixtures:

```text
order 81:
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001],"orderId":81}
7c824b76b7999bc74e2c8f1fbda74e6e07f388be9b85851c4eebb0d57ec1aaa0

order 82:
{"caseId":4512,"compositionIdentity":"composition-82-v1","engineerUserId":31,"installers":[7001],"orderId":82}
9e5c95c9133e9c4fe7858b2d337977368c10647bf2f9a162ec14edc4f102b96e
```

Use a closed literal map or independently asserted test canonicalization. Do not
derive the expected hash from production composition code. This is a positive
oracle correction requiring fresh RED/review because the current hash would
change the tested outcome to INVALID_COMPOSITION before its intended branch.

## E. Trailing `freshTerminalReaders` dependency

The following test construction sites use the existing 12 positional arguments:

- `tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php:31`;
- `tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php:153`;
- `tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php:41`;
- `tests/InstallationProcess/assignment_order_original_upload_001_test.php:95-110`;
- `tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php:58-72,142`;
- `tests/Support/AssignmentOrderOriginalLifecycleFixture.php:155`;
- `tests/Support/AssignmentOrderOriginalShapeFixture.php:60`.

Because the new dependency is optional and trailing, ordinary non-recovery tests
may omit it and intentionally receive the explicit unavailable provider. Adding
a no-op/unavailable provider everywhere is unnecessary and would only restate
the default. A mechanical named/positional patch is needed only where a test
reflects exact constructor arity/property presence.

Any test expecting successful unknown recovery must supply a real/scripted fresh
provider. The sole pure fixture family doing that today is lifecycle.

## F. Lifecycle unknown recovery must move to the fresh protocol

Current scripted recovery lives incorrectly in
`OriginalLifecycleRepository::findTerminalRequest()` at
`tests/Support/AssignmentOrderOriginalLifecycleFixture.php:103-110`. It switches
behavior after `commitCalls > 0`, making a second call on the ordinary writer
repository look “fresh.” DATA-INTEGRITY-001 expressly forbids that fallback.

Required test-owner replacement:

- ordinary repository terminal lookup remains the initial request read only;
- add a test-only `AssignmentOrderOriginalFreshTerminalReaderFactory` whose
  `open()` is counted and scripted by the existing unknown/commit-throw modes;
- its one-shot reader implements one `findTerminalRequest(requestId)` and one
  cached `close()` result;
- FOUND values use the same canonical literal prior/committed result snapshot;
- NOT_FOUND and UNAVAILABLE use closed null-payload lookup values;
- `unknown_recovery_throw` becomes typed open/read unavailable or explicit getter
  Throwable at the fresh seam;
- `unknown_result_getter_throw` stays a fresh lookup getter-failure case;
- pass this provider as trailing `freshTerminalReaders` only for recovery modes.

Update `tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php`
commit-mode traces. Replace the second generic `request` token with exact fresh
protocol tokens, for example the ultimately approved literal event sequence for
factory open, one read and one close. Assert:

- open ≤1, read ≤1, close exactly once after a returned reader;
- fresh result is copied/validated before close;
- close precedes lease release;
- close failure emits only the exact approved close diagnostic and preserves the
  selected validated result;
- no second ordinary repository terminal call, commit, clock, allocation or
  resource acquisition occurs.

This is a behavior expectation change and needs fresh RED plus independent Gate
3. Do not retain the old trace label while silently changing providers.

The lifecycle `conflict_*` modes that use accepted-commit semantic CONFLICT still
perform fingerprint/current-lineage rereads on the ordinary repository and must
not open the fresh provider. Only accepted OUTCOME_UNKNOWN/unconfirmed commit and
authorized-attempt conflict/unknown use the new provider.

## G. Other result/recovery helpers

- `OriginalShapeRepository` terminal FOUND has a literal prior AcceptedCommit and
  canonical result. After repairing its content identity, it is suitable for
  terminal validation; no fresh provider is needed.
- `OriginalLogIsolationRepository` creates terminal results only from its accepted
  or attempt commit DTOs. These values are canonical for their intended branches;
  add complete lineage metadata at its assignment-order lookup and repair shared
  content identity transitively.
- `OriginalDynamicRepository` terminal/fingerprint lookups are NOT_FOUND or typed
  UNAVAILABLE. Its correction lineage needs the complete extension, but it does
  not model unknown recovery.
- `Gate5DomainRepository::findTerminalRequest/findAcceptedFingerprint` currently
  construct status-only lookup values. They are valid only while their configured
  statuses remain NOT_FOUND/UNAVAILABLE. Do not set them to FOUND without a
  canonical non-null Result; new malformed-combination tests belong to the new
  data-integrity matrix.
- `AssignmentOrderOriginalInitialReferenceLookup` currently returns
  NOT_FOUND/false, an impossible closed combination under the new reference rule.
  Change it to FOUND/false for the ordinary “not referenced” control, or create a
  distinct NOT_FOUND/null fixture where that negative status is specifically
  tested. This mechanical correction affects maintenance/reference consumers and
  should accompany their exact test-owner patch.

## H. Production/worker compatibility is code work

These are not fixture patches:

- `ProductionAssignmentOrderOriginalFactory` must accept the optional provider
  and expose the required recovery-ready construction path;
- `AssignmentOrderOriginalVerificationWorkerBootstrap` must construct one lazy
  fresh provider from its already validated trusted connection configuration;
- `AssignmentOrderOriginalMariaDbRepository` must stop serving a second ordinary
  terminal lookup as recovery;
- worker tests must continue asserting the same public results but add evidence
  that recovery used a new connection/provider and that the writer can be unusable
  after acknowledgement loss.

Existing worker result expectations should not be rewritten to failure merely
because the provider is initially absent. Implement the same-provider production
wiring required by the new contract, then obtain the real fresh-connection RED/
GREEN evidence.

## I. Patch order

After Gate 1 approval, use separate reviewable patches in this order:

1. complete-lineage interface shape additions to positive helpers;
2. canonical shared/lifecycle content identity plus exact evidence/trace oracle
   updates, with fresh mismatch evidence;
3. gate5-domain canonical composition hash literals, with fresh mismatch evidence;
4. lifecycle fresh-provider fixture and trace expectations, with demonstrated
   protocol RED;
5. production worker/factory/provider implementation only after those tests are
   independently approved.

Keep intentional invalid snapshots and malformed lookup cases unchanged. No
failure may become a skip, broad catch or “allowed alternative.” Denial audit
cardinality remains deferred and is not touched by any compatibility patch above.
