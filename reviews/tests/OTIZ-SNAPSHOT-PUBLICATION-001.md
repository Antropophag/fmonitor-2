# OTIZ-SNAPSHOT-PUBLICATION-001 — independent Gate 3 review v1

- Date: `2026-09-08`
- Reviewer: separately tasked agent `/root/otiz_review`
- Test authors: other agents/root; this reviewer authored no spec or executable test
- Reviewed commit: `b9b77e0da55c8089e9d7412492e4eb3768903c04`
- Public seam: `SnapshotPublication(MariaDbSnapshotStore, Closure inputs, Closure clock)` with `buildAndPublish`, `accept`, `read`, and `history`
- Verdict: **CHANGES_REQUESTED for complete Gate 3; A01 RED is valid and the bounded atomic-publication implementation loop may start**

## Review

The normative spec, OpenSpec delta/design/tasks, and public seam agree on the
essential behavior: build and publication share one MariaDB transaction,
acceptance remains explicit, receipt-backed completeness is checked from stored
rows, and all four public methods enforce current `otiz.manage`. The independent
numeric example is literal and sufficiently sensitive to the unchanged formula.

The constructor seam is acceptable only with one explicit composition invariant:
the input closure must call `NativeOperationalPremiumInputs` on the same `mysqli`
connection owned by `MariaDbSnapshotStore`. Starting `REPEATABLE READ` and
`WITH CONSISTENT SNAPSHOT` in the store cannot govern reads performed by a closure
bound to another connection. The production composition and focused test must
make this invariant observable. A closure over the same connection is adequate;
no production fault flag or broader abstraction is required for this slice.

The focused application test has a useful core. It verifies literal totals and
allocations, one publication event, serial replay/conflict, build/accept denial,
date and operation validation, rollback on input/object/receipt failures,
second-connection invisibility at the input callback, explicit acceptance,
acceptance replay, damaged allocation rejection, legacy pending/no-receipt
rejection, and not-found. Its RED is the absent public application seam, after
the database fixture is constructed.

The HTTP A01 test is a valid behavioral RED against unchanged production code.
It performs real login/password/session/CSRF, reaches the existing calculate
route, injects the controlled native-input failure through an invalid private
legacy prefix, observes HTTP 500 after the legacy header write, then reaches the
existing accept route. Acceptance returns 303 and changes the incomplete draft
to `accepted`; the required assertion expected `draft`. This is the audited A01
defect rather than setup failure.

## Blocking findings for complete Gate 3

1. Task 1.3 explicitly requires a concurrent input-change test, but none exists.
   The current callback checks only that an uncommitted header is invisible. It
   never changes a native input from the second connection and proves that two
   reads inside one build use one consistent cut. A build that reads inputs on
   another connection or mixes versions can pass.

2. Receipt/manifest sensitivity is incomplete. The test never asserts publication
   metadata, the `otiz-publication-v1` version, the golden canonical JSON/digest,
   receipt row counts, or rejection after changing a header/object value while
   preserving row counts. Deleting one allocation catches only a count mismatch;
   an implementation that trusts receipt counts or hashes the wrong columns can pass.

3. Concurrent replay and concurrent acceptance are specified scenarios but are
   not executed. Serial replay and serial second acceptance do not prove unique-key
   loser recovery or `FOR UPDATE` serialization. Add one bounded two-connection
   race for the same actor/operation/date and one for acceptance; assert one
   snapshot/publication event and one acceptance event. No larger race matrix is
   needed.

4. Ordinary rejection coverage is missing for open blockers, and authorization is
   asserted only for build/accept. Add a blocker-bearing complete publication and
   assert `BLOCKERS` with unchanged status/history. Also assert `read` and `history`
   return `FORBIDDEN` for the denied active user, because the public contract says
   every command/read checks authorization.

5. Failure-history comparison captures `$before` only after a successful draft
   exists and compares `history()` headers. It does not assert absence of orphan
   object/allocation/issue/event/receipt rows after each injected failure. Since an
   incorrect `history()` implementation could hide partial rows, add exact table
   counts (or a complete public observer where available) for these three bounded
   failure points.

The separate runtime schema test covers additive migration shape, repeat,
history preservation, conflict refusal, canonical catalogue registration, and
source-level absence of runtime DDL. It does not replace the missing application
and concurrency assertions above. Its manifest-version fixture currently inserts
`otiz-manifest-v1`, while the normative spec requires
`otiz-publication-v1`; correct this literal before approval.

## RED evidence

```text
$ php tests/Otiz/snapshot_publication_http_001_test.php
exit 255
TestFailure at line 47: A01: incomplete calculation MUST NOT be accepted
Expected: 'draft'
Actual: 'accepted'

$ php tests/Otiz/snapshot_publication_001_test.php
exit 255
OTIZ-SNAPSHOT-PUBLICATION-001: public atomic publication operation exists
Expected: true
Actual: false
```

HTTP prerequisites passed before the failing assertion: login/password/session/
CSRF, controlled calculate 500, and accept 303. Fixture cleanup ran in `finally`.
Production code remained unchanged at the reviewed commit.

## Exact reviewed hashes

```text
c17ccca16e5ab1a907f3708b37d4fbeb5ae7988ef7ac0196c106fe0bbea4c08f  specs/OTIZ-SNAPSHOT-PUBLICATION-001.md
f64c2a4b8a14440d97660af79a50b1f25fb490d5ddc9c917069fbfcb2fcefa8c  openspec/changes/atomic-otiz-snapshot-publication/proposal.md
01db632de6e55662f9c1c2692869bfcf95b2855f74380efb7ab82e61201bfdb5  openspec/changes/atomic-otiz-snapshot-publication/design.md
5b622d16478a606172eab431dce29ac9bbd83991437596e84b42f86463fe7b0a  openspec/changes/atomic-otiz-snapshot-publication/tasks.md
0bb7574af0cfd3642a019c1cd6ffe46842b27823bbd42cfb8c1f1316b401a8f0  openspec/changes/atomic-otiz-snapshot-publication/specs/otiz/snapshot-publication/spec.md
19d772e904c2c8da337b04ca0d3aae312d80cb4278e4896cb9de84044a5df221  tests/Otiz/snapshot_publication_001_test.php
5015d9c7909c4e0766c43eeecfe2852a21eda85beb17c142be8f65488353bc48  tests/Otiz/snapshot_publication_http_001_test.php
55fa17e9a7f26967283e1c147400386d0398f5a2a70b0a93535279bddb5e4504  tests/Otiz/runtime_schema_001_test.php
```

Gate 3 remains **CHANGES_REQUESTED** until the five focused corrections are
reviewed in a new append-only record. The valid A01 RED authorizes a bounded
implementation of transaction ownership and incomplete-acceptance rejection,
but task 1.4 must remain unchecked and the full contract must not be called
approved yet.
