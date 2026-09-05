# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 command v2

- Date: `2026-09-05`
- Reviewer: `Codex agent /root/original_command_gate5_v2` (fresh independent Gate 5; did not author the specification, executable tests, corrective production code, or prior review)
- Implementation authors: separately tasked corrective agents; this reviewer changed no production/test/spec artifact
- Exact reviewed SHA: `12fee6f0389cf1e4cd82d174b15d579689efd29f`
- Prior rejected review: `fa97cfd` of implementation `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Corrective production commits: `87d3bd33063cd063b2202c92c3f5d54ad83db849`, `6be7fa6423cfa46564d03696233623b0f466c31d`, `12fee6f0389cf1e4cd82d174b15d579689efd29f`
- Corrective Gate 3 records: `7b56be9eb88a6d8509edb9b8f4d91efc17e8c170`, `8231b8abbe2306dde00ba27eaa6a9b095f71edaa`, `c00f8ae18b09cd9e23f4790886a8bec0cfd06aab`, `18fafa418cb5962a4ba7d0b819f6be40a1498eaa`
- Verdict: `CHANGES_REQUESTED`

## Exact artifact identities

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
6ace1c1e3029bf549cd2e0fd7127610e670285e48d5aed0a02ffdb679123ab5d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
207f304502191cb522581900c6a086080932d9b77ba23e80afa7e19286e3cf3f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7a6f693c0fda3cfa19c5d693373f07460549f27ab834ce8b411c677bb60e13bf  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
39d3c88b82c9f022e3d1a6b1946ee98e5914af8e1c4e6d572ac372352b66a57a  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
8a7e9a58199afbebaf2d2e82ca758bbeb4f9d44909ff5fbc953a6c6432b6e634  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
```

## Re-evaluation of the seven prior blockers

1. **Physical immutable bytes/restart: corrected for the exercised normal path.** The storage now writes a private stage file, fsyncs, verifies size/digest, atomically renames one `0600` content file, and the restart probe proves exact bytes remain readable after process exit.
2. **Bounded structural parser: still blocking.** The parser checks that every classic-xref offset happens to begin with *an* indirect object, but does not prove that the xref subsection entry for object N points to object N/generation G. A well-formed in-range but identity-wrong xref is accepted as `passive_pdf`; exact reproduction follows below. Xref streams are likewise recognized by dictionary shape but their `/W` entries are not decoded and reconciled with the latest-object graph. This does not implement the approved broken-offset/conflicting-identity fail-closed algorithm.
3. **Assignment-scoped INITIAL: corrected.** Production repository implements `findLineageForAssignmentOrder(caseId, orderId)` and the service uses it where available; the MariaDB corrective test proves a second assignment order is not globally blocked.
4. **Exact failures/audit/DB conflict: corrected for reviewed branches.** Unavailable lookups fail closed, authorization infrastructure failure returns `FAILED/PERSISTENCE_FAILURE`, attempt commit must be `COMMITTED`, and MariaDB duplicate-key is separated from other SQL failures.
5. **All-row composition/pre-stream drift: corrected.** Production reader validates the full ordered member set before inclusion and correction lineage/composition checks precede stage/stream work; corrective tests are sensitive to the prior regressions.
6. **Real lifecycle barriers: corrected.** `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` is emitted by the application only after real finalize returns its held lease; the negative test rejects fabricated/pre-application READY.
7. **Production factory/filesystem/safe-log/orphan safety: still blocking.** Root, symlink, owner/mode and verifier-orphan checks improved, and actual production construction exists. However `AssignmentOrderOriginalProductionConfig` exposes only private root/prefix and `ProductionAssignmentOrderOriginalFactory` binds `AssignmentOrderOriginalDiscardSafeLog`. Thus every real cleanup/release diagnostic required by the approved safe-log contract is silently discarded. The separately tested `AssignmentOrderOriginalFileSafeLog` is used by verification workers, not by the production factory. A green factory smoke test does not prove the production binding it claims.

## Blocking reproduction 1: identity-wrong classic xref is accepted

Starting with the independent positive classic fixture, replace only its first
in-use xref offset (`0000000009`, object 1) with the in-range offset of object 2
(`0000000058`). The resulting xref asserts that object 1 lives at an object-2
header. It must fail closed as structurally invalid; reviewed production accepts
it:

```text
fixture in-use offsets: 0000000009, 0000000058, 0000000115
mutated object-1 xref offset: 0000000058
FMonitorPassivePdfInspector result: passive_pdf
```

Cause: `FMonitorPassivePdfInspector.php:15` iterates only captured offsets and
tests `^\d+\s+\d+\s+obj`; it never compares the expected subsection object and
generation to the header at that offset. A corrective executable test must be
approved through fresh RED/Gate 3 before parser production changes.

## Blocking reproduction 2: production safe logging is disconnected

```text
AssignmentOrderOriginalProductionConfig(privateStorageRoot, tablePrefix)
ProductionAssignmentOrderOriginalFactory dependencies.safeLog = AssignmentOrderOriginalDiscardSafeLog
AssignmentOrderOriginalDiscardSafeLog::record(...) = no-op
```

Consequently production cleanup failures at `finish()`, `releaseLease()` and
the approved cleanup/release-failure paths have no durable safe diagnostic. The
factory must require and validate an existing owned `0600` safe-log identity and
bind the real observer (or another already approved production construction
that provides the same exact behavior). Because changing the public production
config/test expectation is an executable behavior correction, it needs fresh
RED and independent Gate 3.

## Independent verification on exact SHA

Every assignment-order-original InstallationProcess suite was run directly,
including capability migration, database setup, evidence reader, both Gate-5
domain/MariaDB corrections, lease race, maintenance, PDF parser, private restart,
production boundary, schema v2, upload, remaining contract, validation,
post-finalize negative, worker protocol, and worker transport. All 16 exited 0
with their expected `*_OK` literal.

Additional commands:

```text
$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make unit-test
exit 0

$ make lint
exit 0

$ git diff --check 6c4fb5b..12fee6f
exit 0

$ find app/AssignmentOrderOriginal -name '*.php' ... php -l
all files: No syntax errors detected
```

The focused grep found schema DDL only under `app/InstallationProcess/*SchemaMigration.php`; the reviewed command/storage/repository files contain no runtime
`CREATE|ALTER|DROP|TRUNCATE TABLE` or `information_schema` access.

The complete GREEN matrix is insufficient because neither approved test mutates
an xref to a different valid object offset, and the production-boundary test
tests the real logger class separately without asserting the factory binds it.

## Required corrective delivery

1. Add a smallest public-inspector executable RED for xref object/generation
   mismatch (and reconcile xref-stream entries, not only dictionary presence),
   obtain fresh independent Gate 3, then implement the bounded structural fix.
2. Add a production-factory RED proving cleanup/release diagnostics reach the
   configured existing-owned `0600` safe log and invalid/missing paths fail
   before DB/storage use; obtain fresh independent Gate 3, then bind the real
   safe logger without a verifier/runtime selector.
3. Re-run the entire focused inventory and obtain another fresh independent
   Gate 5 on the corrected exact SHA.

Tasks 6.1/6.2 remain incomplete. This review does not approve deployment of the
command slice.
