# QUALITY-GRAPH-GOVERNANCE-001 v0.5 — independent Gate 1 re-review

Date: 2026-09-02  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification, owner approval, OpenSpec artifacts, tests, or implementation  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.5  
Owner approval: `docs/operations/quality-graph-governance-v05-owner-approval-2026-09-02.md`  
Prior review: `docs/operations/quality-graph-governance-gate1-rereview-v4.md`  
Verdict: **CHANGES_REQUESTED**

## Prior v0.4 blockers

- **Receipt/artifact self-commit contradiction:** resolved. RED/GREEN commit fields have been removed from the receipt, all gate commits are derived from path-specific evidence blobs in Git history, and code review names only the already-known implementation commit.
- **Exhaustive changed-file coverage:** resolved in intent. The test interval now covers all `tests/**`, including helpers, fixtures, bootstrap files and nonstandard names. The implementation interval covers the complete repository diff with only two exact exclusions, rather than a source-directory allowlist. Both intervals include deletions.
- Strict chronology, spec-author metadata, immutable supersession, production input boundary, actual representative PR, owner authority and the constrained evidence envelope remain coherent.
- The OpenSpec delta/design/tasks are aligned with the v0.5 intent: Git-derived commits, complete name-status diffs, no self commits and exact implementation review.

## Remaining blocking schema contradictions

1. **Canonical metadata cannot represent the exact sets it is required to repeat.** The canonical RED/test-review/GREEN definitions declare `tests[{path,sha256}]`; GREEN/code-review declare `implementationFiles[{path,sha256}]`; unknown metadata fields are forbidden. The normative diff rule, however, requires each repeated entry to contain `path`, `status` and `sha256` (nullable for `D`). Adding `status` would violate the declared strict metadata schema, while omitting it would violate exact-set equality. Amend all four metadata shapes to `tests[{path,status,sha256}]` and both implementation shapes to `implementationFiles[{path,status,sha256}]`, with an exact nullable-hash rule by status.

2. **The global path-existence rule rejects every represented deletion.** It says paths must name current regular non-symlink files, but a `D` entry intentionally has no file at RED/GREEN/current checkout. Specify stage-aware validation: `A`/`M` paths must be regular non-symlink blobs at the right-hand stage commit and hash that blob; `D` paths must be regular non-symlink blobs at the left-hand commit, be absent at the right-hand commit, and have `sha256:null`. Receipt/evidence artifact paths themselves must still exist as current regular non-symlink files.

3. **Rename/type semantics of `git diff --name-status` are not deterministic under the declared three-value status enum.** Git may emit `R<score>` with two paths when rename detection is active, while the schema accepts only one path and `A|M|D`; type changes can also produce `T`. Define the exact command/options and parsing. A simple contract is `git diff --no-renames --name-status ...`, making renames an ordered `D` plus `A`, and fail closed on any status outside `A|M|D` rather than silently omitting it. State bytewise ordering over the normalized entries after this expansion and cover rename, deletion and unsupported-status fixtures.

## Assessment

The architecture and lineage model are now substantively sound; the remaining findings are narrow but acceptance-blocking because a valid deletion/rename fixture or even a metadata entry with `status` has no single expected verdict under v0.5. After correcting these schemas and mirroring the exact status/path rules in the OpenSpec artifacts, obtain owner confirmation of the revised contract and request another independent Gate 1 review. No v0.5 RED should be treated as Gate-1-approved.
