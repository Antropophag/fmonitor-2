# OTIZ-SNAPSHOT-PUBLICATION-001 — independent Gate 3 review v3

- Date: `2026-09-08`
- Reviewer: separately tasked agent `/root/otiz_review`
- Test/spec authors: other agents/root; reviewer authored only append-only review records
- Reviewed baseline commit: `b9b77e0da55c8089e9d7412492e4eb3768903c04`
- Public seam: `SnapshotPublication(MariaDbSnapshotStore, Closure inputs, Closure clock)` → `buildAndPublish`, `accept`, `read`, `history`
- Verdict: **APPROVED**

## Review

The normative specification, OpenSpec delta/design/tasks and focused tests are
coherent. The seam assigns transaction ownership, publication, replay,
completeness admission and authorization to `app/Otiz`; HTTP remains a caller.
The input closure is approved with the tested composition invariant that
`NativeOperationalPremiumInputs` reads through the same `mysqli` connection
owned by `MariaDbSnapshotStore`, so the store's consistent snapshot governs all
native inputs, previous accepted rows and closures.

Expected financial values are independently literal: object pools 8,500 and
17,000 cents, four allocations 4,250/4,250/8,500/8,500, and total 25,500.
The test does not call the production calculator to obtain expectations.

Atomicity is sensitive at the required ordinary failure points. Input failure,
the second object insert and publication-receipt insert are injected through a
closure or disposable MariaDB triggers. A second connection must not observe
the uncommitted header. After each failure, public history and exact counts of
snapshots, objects, allocations, issues, events and receipts must remain
unchanged, detecting orphan children or audit rows.

Consistency and concurrency are executable. The same store connection reads a
source row twice while another connection updates it between reads; the
published result must retain the initial value. Two overlapping subprocesses
with one actor/date/operation require one snapshot identity and one publication
event. Two acceptance workers require exactly one success, one `IMMUTABLE`, and
one acceptance event.

Receipt verification covers the normative `otiz-publication-v1`, zero counts
and the independently specified complete empty-snapshot JSON digest. Acceptance
then detects both a deleted allocation and same-count allocation-value damage,
so trusting counts or receipt bytes cannot pass. Full public reads before and
after `BLOCKERS` and both incomplete refusals must be identical.

The remaining public outcomes are covered without an unbounded matrix:
operation conflict, invalid date/UUID, denied build/accept/read/history,
not-found, serial replay, serial immutable acceptance, legacy pending/no-receipt
draft, explicit successful acceptance and open blockers. Audit event lists and
draft/accepted states are asserted through the public read seam.

The HTTP RED is valid and precedes production implementation. Real password
login, session and CSRF pass; controlled native-input failure produces 500
after the legacy header write; the unchanged accept route returns 303 and
incorrectly changes the incomplete draft from expected `draft` to actual
`accepted`. The application RED independently stops at the absent public class.
Neither is a setup failure, and cleanup runs in `finally`.

The runtime-schema test independently requires canonical additive v20/v21
migrations, exact receipt columns/indexes, repeat behavior, preservation of
accepted history, conflict refusal, adoption of populated historical evidence
tables, catalogue frontier, and readiness-only retained runtime sources. Its
historical fixture uses literal DDL rather than the production runtime method,
and its receipt uses the normative manifest version.

## Reproduced checks

```text
$ php tests/Otiz/snapshot_publication_001_test.php
exit 255: missing SnapshotPublication class (intended application RED)

$ php -l tests/Otiz/snapshot_publication_001_test.php
No syntax errors detected

$ git diff --check -- specs/OTIZ-SNAPSHOT-PUBLICATION-001.md tests/Otiz/snapshot_publication_001_test.php tests/Otiz/runtime_schema_001_test.php tests/Support/OtizPublicationWorker.php
PASS (no output)
```

Separately supplied and reviewed HTTP RED:

```text
$ php tests/Otiz/snapshot_publication_http_001_test.php
exit 255
A01: incomplete calculation MUST NOT be accepted
Expected: 'draft'
Actual: 'accepted'
```

## Exact reviewed hashes

```text
d20a453e321786c407f3e7dc8c703df67a659286bdf1407a5876c436ba53aef0  specs/OTIZ-SNAPSHOT-PUBLICATION-001.md
f64c2a4b8a14440d97660af79a50b1f25fb490d5ddc9c917069fbfcb2fcefa8c  openspec/changes/atomic-otiz-snapshot-publication/proposal.md
01db632de6e55662f9c1c2692869bfcf95b2855f74380efb7ab82e61201bfdb5  openspec/changes/atomic-otiz-snapshot-publication/design.md
5b622d16478a606172eab431dce29ac9bbd83991437596e84b42f86463fe7b0a  openspec/changes/atomic-otiz-snapshot-publication/tasks.md
0bb7574af0cfd3642a019c1cd6ffe46842b27823bbd42cfb8c1f1316b401a8f0  openspec/changes/atomic-otiz-snapshot-publication/specs/otiz/snapshot-publication/spec.md
609d5ee1c27820f0e096efd70d65be3c974ced8c2383bb4d1fd991cf02317b96  tests/Otiz/snapshot_publication_001_test.php
5015d9c7909c4e0766c43eeecfe2852a21eda85beb17c142be8f65488353bc48  tests/Otiz/snapshot_publication_http_001_test.php
11bc7c8e080608b112acb751e814d5ee866c3d388315f31467cf48c2ec545156  tests/Otiz/runtime_schema_001_test.php
4b4b92e3377f945049c4f81c9be152cc0e5eb49bc4f92cc13835ec152059ae61  tests/Support/OtizPublicationWorker.php
```

Gate 3 is **APPROVED** for these exact sources. Production implementation and
canonical migration work may proceed. GREEN, HTTP integration, Gate 5, full CI
and broader readiness remain later evidence.
