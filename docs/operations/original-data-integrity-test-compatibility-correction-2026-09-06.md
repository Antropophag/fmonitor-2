# DATA-INTEGRITY existing-test compatibility correction

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Status: correction to the read-only inventory; no code, test or specification change.

The original inventory remains immutable at:

```text
8a8346a52bfc93e256b844bfec16d86f27ba19e3954efa94482f89cda472d444  docs/operations/original-data-integrity-existing-test-compatibility-inventory-2026-09-06.md
```

## Correction

The original inventory overextended the real persistence-adapter identity rule
to pure application fixtures.

Approved `ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001` section 4 explicitly
states that the application content object is opaque and verification fixtures
may use a valid synthetic identity; production FileStorage alone guarantees the
exact content-sha256 identity and bytes. `private-content-0001` satisfies the
shared opaque grammar: printable ASCII, 1..80 bytes, no slash or backslash.

`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001` section 9 requires exact
`privateContentIdentity` digest binding when the real MariaDB repository validates
an `AssignmentOrderOriginalAcceptedCommit` before mutation. That is an adapter
contract. It does not add a new application-level rule that every custom
`AssignmentOrderOriginalPrivateContent` must name itself
`content-sha256-<digest>`.

Therefore these reviewed pure fixtures may retain `private-content-0001`:

- `tests/Support/AssignmentOrderOriginalInitialFixture.php` and its upload,
  dynamic-port, shape, safe-log-isolation, validation and gate5-domain consumers;
- `tests/Support/AssignmentOrderOriginalLifecycleFixture.php` and the exact
  FINALIZE_DONE traces in
  `tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php`;
- literal prior/winner commits held only by pure fake repositories in
  `AssignmentOrderOriginalLifecycleFixture.php` and
  `AssignmentOrderOriginalShapeFixture.php`.

No helper or expected evidence patch should change those identities merely to
satisfy DATA-INTEGRITY. Doing so would create unnecessary churn in independently
reviewed pure lifecycle/upload tests and would erase a useful proof that the
application treats content identity as opaque.

## Fixtures that do require digest-bound identity

Only a test invoking the real `AssignmentOrderOriginalMariaDbRepository` with an
accepted commit must supply:

```text
content-sha256-4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
```

for the canonical 327-byte PDF example. This includes:

- future direct public-repository AcceptedCommit validation tests;
- real MariaDB command/worker tests whose production FileStorage supplies the
  commit identity;
- real rehydration/backing fixtures that seed revision rows expected to validate.

Existing worker/race/lease and MariaDB evidence fixtures already use the
content-sha256 form. They need no compatibility oracle patch for this rule.
Future malformed DTO cases should deliberately vary this field only at the real
repository seam and require `ROLLED_BACK` with zero SQL/mutation.

## Findings that remain valid

The original inventory remains accurate for these independent gaps:

1. FOUND lineage helpers in DynamicPorts, LogIsolation, Lifecycle and Gate5Domain
   need the complete case/order/revisionIds extension. This is required by the
   application FOUND-lineage protocol and is not a content-identity issue.
2. `Gate5DomainComposition` uses noncanonical hashes for otherwise positive
   FOUND snapshots. The exact order-81/order-82 one-installer hashes remain
   `7c824b...aaa0` and `9e5c95...b96e`; correcting them changes a positive oracle
   and needs fresh RED/Gate 3.
3. Lifecycle unknown recovery must move from a second ordinary repository lookup
   to the explicit fresh-reader provider, with exact open/read/close traces and
   separate review.
4. The trailing `freshTerminalReaders` dependency remains optional for ordinary
   non-recovery tests; no blanket constructor edit is required.
5. `AssignmentOrderOriginalInitialReferenceLookup` still has the closed-shape
   mismatch NOT_FOUND/false and should become FOUND/false or a distinct
   NOT_FOUND/null fixture according to the test's intended branch.
6. Production factory/worker same-target fresh-provider wiring remains production
   code work, not a fixture patch.

Intentional invalid snapshots and lookup combinations remain unchanged. Denial
audit cardinality remains deferred.

## Revised patch order

After exact DATA-INTEGRITY Gate 1 approval:

1. complete-lineage positive helper shapes;
2. Gate5Domain canonical composition hash oracle correction with fresh RED/Gate 3;
3. lifecycle fresh-provider protocol fixture/trace correction with fresh RED/Gate 3;
4. closed reference helper correction where its owning test exercises that seam;
5. real MariaDB repository/reader/factory/worker tests and implementation.

Do **not** schedule a blanket `private-content-0001` replacement in pure tests.
Digest binding is verified at the actual MariaDB repository and real FileStorage
composition boundary.

## Exact authority hashes

```text
0f116462166cf7db03c13a7f126357464c3a5eb05da12c65224243ea6bd14af7  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
```
