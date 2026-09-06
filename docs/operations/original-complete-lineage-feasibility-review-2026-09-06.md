# Original command — complete lineage metadata feasibility review

- Date: `2026-09-06`
- Reviewer: separately tasked read-only agent `/root/registry_engine_gate1`
- Reviewed HEAD: `9d8cd1423b69c6d93b55b874db3bdb27211f8c06`
- Scope: minimum public lineage metadata and MariaDB cross-table validation for data-integrity/fresh recovery
- Verdict: **OPTIONAL COMPLETE-LINEAGE EXTENSION IS THE MINIMAL VIABLE DESIGN**

No code, test or specification was edited. This is a feasibility record, not
Gate 1 and not a product-policy decision.

## Recommended public boundary

Keep `AssignmentOrderOriginalLineageLookup` unchanged for source compatibility
and add one read-only extension required whenever status is `FOUND`:

```php
interface AssignmentOrderOriginalCompleteLineageLookup
    extends AssignmentOrderOriginalLineageLookup,
            AssignmentOrderOriginalCurrentEvidenceLookup
{
    public function installationCaseId(): ?int;
    public function assignmentOrderId(): ?int;
    /** @return list<string> */
    public function revisionIds(): array;
}
```

The existing base getters continue to expose root ID, current revision ID,
current revision number and immutable composition identity/hash. The existing
current-evidence extension exposes current document date and PDF hash. The new
fields add only what is missing at the application seam: exact case/order
ownership and an enumerable closed revision membership set.

For a `FOUND` result, the application must require this complete extension and
validate all fields together. A base-only FOUND is malformed and maps to
retryable `FAILED/PERSISTENCE_FAILURE`; it must never be reclassified as a
business collision or absence. `NOT_FOUND|UNAVAILABLE` remain valid through the
base interface and must expose no metadata.

Changing the base interface is broader and less precise. It would force every
NOT_FOUND/UNAVAILABLE value, simple negative stub and unrelated repository
implementation to add case/order/list methods even though those states own no
lineage. The optional extension preserves compile-time compatibility while
making complete metadata mandatory exactly where it exists.

Existing test helpers that return FOUND must deliberately implement the complete
extension and provide literal case/order/revision membership/current evidence.
Negative-only helpers may retain the base interface. This is an expected exact
test-fixture disposition, not permission to accept incomplete production FOUND.

## Application validation

A complete FOUND lineage must satisfy:

- requested root equality for root lookup, or requested case/order equality for
  assignment lookup;
- positive bounded case/order/current revision number;
- valid root/current/all revision opaque IDs;
- nonempty unique `revisionIds`, with current ID present;
- `containsRevision(id)` exactly equivalent to membership in the returned list
  for every target queried by the command;
- valid immutable composition identity and lowercase SHA-256;
- real current document date and lowercase PDF SHA-256 from the same current
  revision metadata.

Malformed getters, Throwable, duplicate IDs, absent current membership or
case/order mismatch are persistence-integrity failure. Only a validated complete
lineage participates in semantic-collision, stale/current/target/no-change
precedence.

The public extension need not expose every historical revision column. Complete
sequence/previous ownership is a MariaDB adapter invariant verified by direct
repository tests and Gate 5 source inspection. Publishing full revision rows to
the application would duplicate evidence-reader/domain DTOs without being used
by the command.

## Exact MariaDB lineage checks

For either lookup, the adapter must use one consistent read-only snapshot and:

1. select exactly zero or one root; duplicate/corrupt matches are UNAVAILABLE,
   never resolved by `LIMIT 1`;
2. for root lookup require exact requested root; for assignment lookup require
   exact requested positive case/order and the root's stored ownership;
3. select all revisions for that root ordered by revision number and require a
   nonempty unique set;
4. require numbers exactly `1..N`, revision 1 with null previous, every later
   revision pointing to the immediately preceding revision ID, and no fork/gap;
5. require root `current_revision_id` equal revision N and current number N;
6. require every revision's root ID equal the selected root and every revision/
   request/event identity and scalar grammar to pass the existing contract;
7. derive current document date/PDF hash only from revision N;
8. return one immutable complete snapshot after successful commit/release of the
   read transaction; query/protocol/close failure is UNAVAILABLE.

This proves root/current/all-revision ownership and membership without adding a
write seam. The extension is passive and constructed only from validated reads.

## Accepted result backing across later corrections

A stored accepted request represents the immutable response produced by that
specific attempt. Its `currentRevisionId`, revision number, document date, hash,
size and uploaded time therefore remain tied to the revision created by that
request.

After a later correction advances the root, an earlier accepted request must
still replay its original historical revision. It is incorrect to require:

```text
request.currentRevisionId == root.currentRevisionId
```

for every accepted request.

Instead, accepted request validation must require:

- request row exact queried request ID and accepted tuple;
- referenced request revision exists and belongs to the request's root;
- revision `request_id` equals that request;
- request evidence fields equal that revision's ID/number/date/PDF hash/size/
  uploaded time;
- root exists with the same case/order and immutable composition identity/hash;
- the request revision is a member of the root's valid complete chain;
- one exact accepted event and audit back that request/root/revision.

The root's latest current revision is validated independently as chain revision
N. It may equal the request revision for the newest request or be a later member
for historical replay. Fingerprint lookup similarly validates that the queried
fingerprint belongs to the exact backing revision and then returns that
revision's immutable request result, not the root's latest evidence.

## Fresh recovery reuse

Ordinary terminal lookup, fingerprint lookup and the one-shot fresh recovery
reader should share the same accepted-result validator. Fresh FOUND is accepted
only after complete request→revision→root→event/audit backing succeeds on the
fresh connection. Malformed backing is UNAVAILABLE and maps to
`PERSISTENCE_OUTCOME_UNKNOWN`; it is never treated as NOT_FOUND or as a recovered
accepted result.

The fresh reader remains read-only and exposes no repository mutation methods.
Its connection identity/target/close protocol stays the separate mandatory
factory dependency already identified by the fresh-recovery feasibility review.

## Required bounded evidence

Gate 1/3 should cover:

- complete FOUND for root and assignment lookup;
- base-only FOUND, wrong case/order, duplicate/empty revision list, current not
  in list, contradictory `containsRevision`, invalid current date/hash and getter
  Throwable;
- direct MariaDB root duplicates, broken previous chain, revision gap/fork,
  foreign current revision and incomplete event/request backing;
- initial accepted request, latest correction request and historical initial
  request replay after correction;
- an explicit negative proving historical request revision need not equal root
  latest current revision;
- fingerprint FOUND backed by its exact historical revision;
- fresh FOUND/NOT_FOUND/malformed/unavailable with one read-only connection and
  close, no DDL/DML or repair.

All expected identities and rows must be literal synthetic values. Direct
adapter fixtures validate existing data but do not become an alternate write
seam.

## Exact reviewed hashes

```text
014af5a9b72ab93d7e03e9d3dbb1da208b22b28e5bf9c40bead0532ac60e74a8  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
1041c338567898b577b218c6b4bd73c2be872096fd994276e4c3680bdf95dcbd  app/AssignmentOrderOriginal/AssignmentOrderOriginalCommitProtocol.php
bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
72201ad71b18561cd40c4d697e9eeb110705e66c9467ca707017a88a4ccab7ff  docs/operations/original-command-data-integrity-contract-audit-2026-09-06.md
51fd437bd8808987516f27a0a3b51b2e7333d180170121249a28d9c3c0459d14  docs/operations/original-fresh-recovery-factory-feasibility-review-2026-09-06.md
```

This design adds read metadata only. It does not reorder authorization, expose
confidential data, add a mutation path or force historical request results to
track mutable lineage head state.
