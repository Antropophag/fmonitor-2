# Test review: OBJECT-DETAIL-NO-DDL-RATCHET-001 v0.1

- Review date: `2026-09-05`
- Reviewer: separately tasked agent `/root/object_detail_ratchet_review`; did
  not author the specification, test, RED evidence, scanner, baseline, or
  importer
- Reviewed repository HEAD: `5c711f2d26c4618b0f91c2512f91db51999dd311`
- Verdict: **APPROVED**

## Coverage and traceability

The test directly exercises the approved public seam by launching a copied
`tools/architecture/check.py --json` as a child process. It does not import,
mock, or replace the scanner's private collection/comparison functions. Each
case copies the actual scanner and baseline into a fresh owned temporary
repository and supplies exactly one explicit PHP source fixture.

The three cases trace to the v0.1 acceptance contract:

- the two fixed historical CREATE statements at the permitted
  `app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php`
  path must return exit 0, `ok:true`, and no errors;
- the sole public read-only compatibility call at the importer path must return
  the same clean result;
- the same two historical CREATE statements at the exact importer path must
  return exit 1, `ok:false`, and contain both independently fixed
  `ddl_ownership` findings for fingerprints `0869fae855bd5c76` and
  `5e45e35f56e1f931`.

The expected fingerprints are literals fixed in the approved specification and
correspond to the two historical importer lines. The test does not calculate
them from scanner output or read the runtime importer to derive its oracle.
Requiring each exact finding prevents an unrelated scanner error from satisfying
the rejection case. The two clean controls also show that the existing owner
rule remains enabled and that a runtime readiness check does not acquire schema
ownership.

The test intentionally verifies the behavioral ratchet through the
`ddl_ownership` findings. The specification's exact removal of the matching two
`rapid_pilot_boundary` entries is additionally an implementation-diff
constraint for Gate 5; those parallel entries do not change the public denial
proved here because either removed DDL line must independently be rejected by
the canonical DDL-owner rule.

## Sensitivity and RED

Independent execution reproduced the recorded intended RED:

```text
test_01_canonical_owner_is_accepted ... ok
test_02_readonly_runtime_precondition_is_accepted ... ok
test_03_old_runtime_ddl_cannot_return ... FAIL
AssertionError: 1 != 0 : RED: historical importer CREATE must no longer be grandfathered
Ran 3 tests
FAILED (failures=1)
exit 1
```

The current baseline still contains exactly one copy of each of the two
`ddl_ownership` entries and exactly one copy of each corresponding
`rapid_pilot_boundary` entry. Consequently the copied public CLI returns exit 0
for the historical runtime fixture, while the approved contract requires exit
1. Both controls pass in the same environment, so this is missing-ratchet RED
rather than broken scanner, PHP tokenizer, JSON, path layout, or interpreter
setup.

## Isolation, cleanup, and bounds

`TemporaryDirectory` owns the complete fixture repository and removes it on
normal return or exception. The test writes no original repository file,
connects to no database or source system, and never executes the SQL text. The
child runs with the owned root as its working directory, captures stdout and
stderr, and has a 30-second timeout. Unexpected stderr, non-contract exit
status, malformed JSON, or missing result shapes fail as setup diagnostics
rather than qualifying as the intended RED.

The test introduces no production behavior and authorizes no early baseline
reduction. The four baseline entries may be removed only with or after both
actual importer CREATE statements are removed under their separately approved
Gate 3. Importer serial behavior, its regression suite, baseline implementation,
Gate 5, full verification, and parent completion remain outside this approval.

## Verification

```text
python3 -m py_compile tools/architecture/tests/test_object_detail_no_ddl.py
PASS

git diff --check
PASS
```

## Exact reviewed SHA-256

```text
079944d3797bdd0016d76b1ab2572c9ef48436a06d932b9c9b455c56cd1aa908  specs/OBJECT-DETAIL-NO-DDL-RATCHET-001.md
0469a590f0c119e493a03ca3bb65703804861a6ca4cbc1c9ed16e3ddcc32f62d  tools/architecture/tests/test_object_detail_no_ddl.py
164af100cba76cab4d6d80ff2954337b1447df4e0f060d12d6ebb8700fff2378  docs/operations/object-detail-no-ddl-ratchet-red-2026-09-05.md
64c1bfbed11df12ab6078e64ea5db71a970b5696403840375db945e528de86b8  tools/architecture/check.py
55704e27d4c6f58152996444e7744bc5c6c42234ffbede92ed3ad6024cd00f1e  tools/architecture/baseline.json
```

## Required changes

None.
