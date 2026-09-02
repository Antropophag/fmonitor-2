# CLASSIFICATION-PROVENANCE-SCHEMA-001 — fresh independent Gate 3 rereview v3

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/classification_test_rereview_v3`  
Test author: separately tasked Gate 2 agent `/root/classification_red`  
Reviewed commit: dirty autonomous-delivery worktree; approval is pinned to the exact hashes below  
Verdict: **APPROVED**

## Fixed review input

- owner-approved executable specification SHA-256:
  `a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed`
  (`specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md`);
- reviewed verifier SHA-256:
  `6413248fdd5f74379a91e09d315bd08219b3e46ecefd4dd91fd75bf68e8593f9`
  (`tests/InstallationProcess/classification_provenance_schema_001_test.php`);
- TCP source sentinel SHA-256:
  `cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099`
  (`tests/Support/classification_source_sentinel.php`);
- superseding RED evidence SHA-256:
  `0ace73183398f8ba5bfc2be67b4f59df5f6d186d700830751fcee9421497723e`
  (`docs/operations/classification-provenance-schema-red-evidence.md`);
- owner decision SHA-256:
  `485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1`
  (`docs/operations/morning-owner-approval-decision-2026-09-02.md`);
- prior `CHANGES_REQUIRED` reviews SHA-256:
  `d395ef3259eab6a2058843c49e3d450877737e0406924e63b7ec7042014d2c65`
  and
  `6d5fefcecadc06eca0af808c3f18386e95b706b2572d5276734d742b9871b273`.

The specification hash is exactly the owner-approved hash and the verifier
checks that hash itself. Expectations are test-owned and exercise the public
migration runner, all three public batch CLIs, and the public provenance
reconcile seam. They are not derived from a planned v11 implementation.

## Prior blocker disposition

1. **Primary-key identity mutant — corrected.** The semantic index projection
   retains the literal `PRIMARY` identity while ignoring only secondary
   presentation names. The dedicated mutant drops the primary key and adds a
   merely unique `id` index; the expected conflict and full before/after state
   equality make a PK-as-UNIQUE regression observable.

2. **Bounded and fail-safe process lifecycle — corrected.** Ordinary migration
   and batch runners use nonblocking output collection with an eight-second
   deadline, forced termination and reap. Both race children are registered
   before collection; each successfully collected child is cleared, while an
   enclosing attempt-all `finally` terminates, closes and reaps every remaining
   sibling if either collection or an assertion throws. Sentinel processes are
   likewise owned by `finally`, and every auxiliary database plus manifest/temp
   artifact is covered by outer cleanup.

3. **Concrete zero-output and ready-publication snapshots — corrected.** Each
   missing/drift invocation installs command-specific sentinels for the actual
   output families: installation cases; history snapshots/quarantine; and
   active baselines/case provenance/template associations. Before/after values
   include exact table metadata, ordered binary rows, counters, provenance
   schema, ambient decoy, complete prefixed table catalogue and the manifest
   SHA-256 publication sentinel. Equality is therefore observable for all
   three real commands and both missing/drift modes; it is not inferred from
   transcript alone.

All earlier v1 blockers also remain corrected: real independently sentineled
CLI invocations, exact/redacted outcomes, mandatory
`PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast, complete semantic manifest and
representative drift matrix, exact 25/26/invalid/non-ASCII prefix boundaries,
and a separate populated v1-v10 race fixture with predecessor/counter/decoy
preservation.

## Traceability, sensitivity and isolation

- Clean, exact repeat, populated repeat, conflict/zero-repair, prefix/default,
  DDL-denied replay/conflict, missing/drift runtime ordering, bounded race and
  native non-atomic contrast map directly to specification sections 2-7.
- The PK mutant, index/column/default/engine/collation/CHECK mutants, live TCP
  sentinels, incompatible concrete output tables, trigger-injected provenance
  conflict and runtime-DDL source ratchet would catch plausible incorrect GREEN
  implementations rather than merely restating their output.
- Fixed business facts and exact transcripts are deterministic. Random values
  only allocate validated private database/process/file namespaces. Cleanup is
  exact and does not discover production targets from post-failure output.
- No test touches production or legacy databases; all SQL runs in disposable
  test databases. Diagnostics assertions prohibit source credentials, prefixes
  and sentinel literals.

## Reproduced RED evidence

```text
$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: clean public runner reaches v11 canonical owner
Expected: exit 0 / schemaVersion 11 / appliedVersions [1..11]
Actual:   exit 0 / schemaVersion 10 / appliedVersions [1..10]

$ CPS_SCENARIO=runtime-boundaries php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: batch-import-native-candidates.php missing exact public failure
Expected stderr: empty
Actual stderr: mysqli greeting warning from forbidden sentinel connection

$ CPS_SCENARIO=runtime-ddl php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: classification provenance runtime owner contains no DDL
Expected: false
Actual: true

$ openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid
```

MariaDB is reachable and the primary run successfully applies canonical v1-v10
before the terminal-version assertion. The runtime-boundary sentinel reaches
readiness and records the forbidden connection. These are qualifying behavior
REDs, not setup failures.

## Required changes

None.

Gate 3 is **APPROVED** for exactly the hashes above. OpenSpec task `2.2` may be
checked. Gate 4 may implement only the approved behavior without changing the
specification, verifier, sentinel or expectations; any such change requires a
new independent Gate 3 review.
