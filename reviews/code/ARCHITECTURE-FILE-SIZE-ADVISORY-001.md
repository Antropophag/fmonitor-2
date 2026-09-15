# Code review: ARCHITECTURE-FILE-SIZE-ADVISORY-001

- Reviewer: Codex independent reviewer `/root/issue116_gate5` (gpt-5.6-sol / low); reviewer authored neither tests nor implementation
- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T191207Z-302a165e8c/snapshot`, patch SHA-256 `8cab6773f39e84bd370fc9312a6064c2b007275b911d56b1a3e1dec165633666`
- Candidate/executable source: `fdd25f45b3f01f61d3fe32399b8a3e24023aa8114d7de4fe99a448528e2068de` / `a2685e99ef0dd3e76079e33386a37265e36d2a41a5b4bc26baadcde40edc8df7`
- Specification: `specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md`
- Required prior review: Gate 3 final correction is `APPROVED`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **MEDIUM — the candidate unnecessarily breaks established human-readable error tokens** (`tools/architecture/check.py:399-403`, `tests/Verification/architecture_file_size_advisory_001_test.py:97-105`). Before this slice, blocking findings were printed verbatim, for example `- sql_ownership: ...`; the candidate uppercases the rule prefix to `- SQL_OWNERSHIP: ...`, and the new mixed-output test locks that incidental change in. Human output is part of the public checker seam. The contract requires additive advisory visibility while meaningful rules retain their blocking behavior, but does not require changing existing error text. Existing log matchers or callers can therefore break for a change unrelated to file-size classification. Preserve the former rendering with `print(f"- {error}")`, and have the test assert the established lowercase `sql_ownership:` independently from advisory visibility.

## Standards

The finding above is the only standards/integration-compatibility issue. The complete patch is otherwise bounded and introduces no documented-standard violation, security issue, or applicable Fowler smell. Production application code, architecture detectors, baseline debt, and unrelated `sync-erp-equipment-facts` WIP are absent from the reviewed snapshot.

## Specification

Apart from the finding, the candidate implements the bounded contract. The executable fixture covers the ten owner-required cases: the 149→150 comment/blank crossing, existing hotspot growth, new and moved large files, mixed SQL, both DDL/runtime-migration forms, forbidden dependency, separate JSON errors/advisories with errors-driven exit, human advisories on PASS and FAIL, and a size update alongside a live unrelated SQL violation. `classify()` leaves all pre-existing meaningful detector comparisons intact; `compare()` remains a blocking-errors compatibility seam. JSON adds `advisories` while retaining `ok`, `errors`, and `rules`. The size update changes only `hotspots` in the parsed baseline and preserves every non-size section.

## Verification evidence

- `python3 tests/Verification/architecture_file_size_advisory_001_test.py` — `GREEN`, 10 tests; retained record `1789499459814766000-a406fd2bddd740a689c962779e34fefb.json`, exact candidate/executable source as named above.
- `python3 tests/Verification/change_verification_001_test.py` — `GREEN`, 18 tests; retained record `1789499493808790000-5bc4e980fd854b5eb25d53bc22d7667a.json`, same exact source.
- Full local `make test` / `make verify` was not run, per owner policy. Exact-source CI is `UNKNOWN`/pending and is not treated as GREEN.

## Required correction

Restore the pre-existing lowercase human error rendering and adjust the new assertion accordingly. Because this changes implementation and tests after the reviewed snapshot, refresh the plan/evidence as required and obtain the applicable independent rereviews before publication.

## Corrected-candidate final rereview — 2026-09-15

- Reviewer: Codex independent reviewer `/root/issue116_gate5` (gpt-5.6-sol / low); reviewer authored neither tests nor implementation
- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T191900Z-08c83fc41d/snapshot`, patch SHA-256 `192ea7c0a5bf2c739da7d58f80da29363ca0b1ed67b6f5c3eb301543be8adc20`
- Candidate/executable source: `7f54d9f0fd8d8770aae0a6b2c1d8679242c3701694b13e2c7cc4d3d65deb70f7` / `faa2dd98294a28fd486b1260cca15b8607de8c2d7f319b772256650aebfd7079`
- Prior finding disposition: resolved
- Post-change Gate 3: `APPROVED`
- Verdict: `APPROVED`

### Complete findings

None.

### Standards

The corrected checker again prints blocking errors verbatim, preserving established lowercase rule identifiers, while the mixed-result fixture independently requires `sql_ownership:` and visible advisory output. No documented-standard, integration-boundary, maintainability, security, or applicable code-smell finding remains. The snapshot remains bounded and excludes production application changes and unrelated `sync-erp-equipment-facts` WIP.

### Specification

The complete contract is implemented and regression-sensitive across the ten required cases. Size observations are separate advisories for threshold crossing, growth, new files, and move/rename; SQL, DDL/runtime migration, and forbidden dependency remain errors even when an advisory is also present. Human output exposes advisories on PASS and FAIL. JSON keeps `ok`, `errors`, and `rules` and additively exposes `advisories`; only errors determine exit. The size-only update replaces `hotspots` while preserving every non-size baseline section despite a live unrelated SQL violation. Existing meaningful collection/comparison paths are unchanged, and `compare()` retains its blocking-errors-only compatibility behavior.

### Verification evidence

- `python3 tests/Verification/architecture_file_size_advisory_001_test.py` — `GREEN`, 10 tests; retained record `1789499896493844000-c664ce28a03e4adb9e28822cd04ea448.json` on the exact candidate/executable source above.
- `python3 tests/Verification/change_verification_001_test.py` — `GREEN`, 18 tests; retained record `1789499906251441000-fb8ce3cb85734114ba039b3b9737ae92.json` on the same exact source.
- The post-change Gate 3 record also identifies the unchanged architecture fixture as `GREEN`, 59 tests, on the corrected executable lineage, preserving the existing meaningful negative cases.
- Full local `make test` / `make verify` was not run, per owner policy. Exact-source CI remains `UNKNOWN`/pending and is not treated as GREEN; it remains required after publication of the committed candidate.

### Required changes

None.
