# CLASSIFICATION-PROVENANCE-SCHEMA-001 — fresh independent Gate 3 rereview v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/classification_test_rereview_v2`  
Verdict: **CHANGES_REQUIRED**

## Fixed review input

- owner-approved executable spec SHA-256:
  `a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed`
  (`specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md`);
- corrected verifier SHA-256:
  `36a5550f1f7c73d6db1ead7e2c479dc35e27ed6fbde13dd1d13610e389d50ef6`
  (`tests/InstallationProcess/classification_provenance_schema_001_test.php`);
- TCP source sentinel SHA-256:
  `cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099`
  (`tests/Support/classification_source_sentinel.php`);
- superseding RED evidence SHA-256:
  `56f7a85afc9113a892c9ab330db500bd77501adc76ec57f5390d9964b44b89e2`
  (`docs/operations/classification-provenance-schema-red-evidence.md`);
- owner decision SHA-256:
  `485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1`
  (`docs/operations/morning-owner-approval-decision-2026-09-02.md`);
- prior Gate 3 review SHA-256:
  `d395ef3259eab6a2058843c49e3d450877737e0406924e63b7ec7042014d2c65`
  (`reviews/tests/CLASSIFICATION-PROVENANCE-SCHEMA-001.md`).

The approved hash still matches the owner decision and the verifier's test-owned
hash guard. The correction materially addresses the prior missing coverage: it
adds real invocations of all three batch CLIs with independent TCP sentinels,
the required native `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast, semantic
index rows and representative drift mutants, 25/26/invalid prefix boundaries,
a populated isolated race fixture, bounded CLI/race waits, and database/temp
cleanup. Three remaining gaps prevent Gate 3 approval.

## Gate-blocking findings

1. **The exact manifest oracle does not prove that `id` is the primary key.**
   `cpsAssertIndexes()` reduces every index member to
   `NON_UNIQUE/SEQ_IN_INDEX/COLUMN_NAME/SUB_PART/COLLATION/INDEX_TYPE/IGNORED`
   and deliberately discards the index/constraint identity. Consequently a
   table with an ordinary `UNIQUE(id)` and no `PRIMARY KEY` produces the same
   expected row `0|1|id|NULL|A|BTREE|NO`. `cpsAssertManifest()` filters
   `TABLE_CONSTRAINTS` to only FK/CHECK, and the drift matrix has no
   primary-versus-unique mutant. A GREEN preflight which accepts that
   non-equivalent schema would pass this verifier, contrary to approved §3's
   exact `PRIMARY KEY (id)` requirement. Presentation names may be ignored, but
   primary-key semantics may not.

2. **The two-runner race still lacks unconditional process cleanup.** At line
   64 both runners are started before `cpsFinishRunner($r1)` is called. If the
   first bounded wait throws (timeout, pipe/read error, or assertion path),
   `$r2` is never passed to a wait/terminate helper and no surrounding `finally`
   owns either child. Database cleanup does not terminate that live process.
   This is the same fail-safe requirement from prior finding 6: every started
   auxiliary process must be registered immediately and attempt-all terminated/
   reaped in `finally`, including when its sibling fails. `cpsRun()` is also an
   unbounded `stream_get_contents()`/`proc_close()` public-runner helper, so a
   hung clean/prefix/conflict/repeat invocation can still hang autonomous
   verification instead of producing explicit `SETUP_FAILURE`.

3. **Missing/drift scenarios do not make zero output DML or zero ready
   publication observable.** `cpsRuntimeCliBoundaries()` snapshots only the
   provenance table, one `${prefix}ambient_decoy`, and names/counters of tables
   matching that prefix. It does not install/snapshot the command-specific
   output tables/rows for native, history, or active, and it does not snapshot
   the manifest bytes (or another ready-publication artifact) after writing it.
   Thus the TCP sentinel proves source connection ordering, and the current
   failure is sensitive, but a future path that correctly avoids source access
   while mutating an unobserved output/ready surface before returning the exact
   error would pass. Approved §5.2 explicitly requires zero output/provenance
   DML and zero ready publication for each of the three real commands; those
   assertions must be backed by concrete before/after sentinels, not inferred
   from the exit transcript.

## Prior blocker disposition

- required native PILOT_ONLY contrast: **corrected** through a real eligible
  source object, after-insert conflict injection, exact CLI transcript, one-case
  assertion and unchanged mismatched proof;
- three real missing/drift public CLIs and source sentinels: **corrected for
  source-access ordering and exact/redacted transcript**, but output/ready
  mutation observability remains incomplete as finding 3;
- index/default/extra/engine/collation/CHECK drift sensitivity: **substantially
  corrected**, but primary-key identity remains incomplete as finding 1;
- exact 25-byte success plus 26-byte/non-ASCII/invalid-ASCII rejection:
  **corrected**;
- populated isolated race and predecessor/counter/decoy preservation:
  **corrected**;
- bounded waits and unconditional cleanup: **partially corrected**; sibling
  race-process cleanup and ordinary migration-runner timeout remain open as
  finding 2.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/classification_provenance_schema_001_test.php
No syntax errors detected

$ php -l tests/Support/classification_source_sentinel.php
No syntax errors detected

$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: clean public runner reaches v11 canonical owner
Expected: exit 0 / schemaVersion 11 / appliedVersions [1..11]
Actual:   exit 0 / schemaVersion 10 / appliedVersions [1..10]

$ CPS_SCENARIO=runtime-boundaries php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: batch-import-native-candidates.php missing exact public failure
Expected stderr: empty
Actual stderr: mysqli greeting warning from the forbidden TCP source connection

$ CPS_SCENARIO=runtime-ddl php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: classification provenance runtime owner contains no DDL
Expected: false
Actual: true

$ openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid
```

The primary and real-boundary failures are deterministic behavior REDs after a
reachable MariaDB/setup path, not environment failures. The runtime-boundary
failure also confirms that the TCP sentinel accepts the forbidden source
connection. These valid REDs do not compensate for acceptance statements that
the verifier cannot yet observe.

## Required correction before another fresh review

- distinguish `PRIMARY KEY(id)` from a merely unique index and add a sensitive
  primary-key drift mutant;
- give every migration/race child a bounded collector and immediate
  attempt-all `finally` ownership, including a sibling left running when the
  first collector fails;
- install and byte/schematic snapshot concrete output and ready-publication
  sentinels for each real missing/drift CLI, then prove them unchanged.

OpenSpec task `2.2` remains unchecked because the verdict is not APPROVED. No
test or production file was edited by this reviewer.
