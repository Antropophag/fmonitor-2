# Quality Graph governance baseline audit

Date: 2026-09-02
Auditor: Codex `/root`
Repository head at audit: `$(git rev-parse HEAD)` is intentionally not embedded because this working tree contains uncommitted planning artifacts; exact commit lineage starts at Gate 2.

## Repository-owned execution seams

| Seam | Owner | Stable observable result |
|---|---|---|
| `make architecture-check` | `tools/architecture/check` and policy files | zero/nonzero exit and architecture findings |
| `make verify` | root `Makefile` | `VERIFY_STAGE <name> PASS|FAIL`, terminal `VERIFY_OK` or `FULL_VERIFICATION_FAILURE` |
| `make fresh-test-verify` | root `Makefile` | full verification plus mandatory teardown |
| focused suites | `tools/verification/run.sh` | unit, db, characterization, e2e and lint exit status/output |

`make verify` currently executes test DB reset, canonical migration, architecture, lint, unit, DB, characterization, E2E and diff checks. It keeps independent stages running after a regression and distinguishes `SETUP_FAILURE` from regression failures. Quality Graph must call these seams rather than recreate their logic.

## SSD/TDD evidence inventory

| Gate | Existing owner/location | Current machine-verifiable fields | Gap |
|---|---|---|---|
| Gate 1 | `specs/*.md`, OpenSpec artifacts | stable spec ID/status by convention | no canonical content hash binding downstream artifacts |
| Gate 2 | tests plus `docs/operations/*red-evidence*.md` | commands/output generally recorded | no common schema for tested commit, author and spec hash |
| Gate 3 | `reviews/tests/*.md` | template has reviewer, author, commit, verdict | free-form Markdown; independence and hashes are not enforced |
| Gate 4 | `docs/operations/*green*.md` and commands | commands/results generally recorded | no exact common binding to approved test/spec |
| Gate 5 | `reviews/code/*.md` | template has reviewer, author, commit, verdict | exact head, independence and artifact hashes are not enforced |

The backward-compatible migration boundary is opt-in: only immutable receipt chains under `delivery/evidence/*/*.json` are governed initially. Historical records remain valid historical evidence until explicitly onboarded; no bulk rewrite is required.

## CI baseline

No `.github/workflows` files existed at audit time. The repository has a deterministic local harness but no GitHub-hosted PR runner, trusted publisher or required Quality Graph check. Branch protection is external state and is outside this slice.

## Quality Graph source baseline

- Official repository: `https://github.com/alchemmist/quality-graph`
- Audited release/tag: `v0.1.7`
- Dereferenced release commit and runtime action pin: `caf5366a04ca01b230f1df5585d0fbd9693d7bef`
- Package set: `quality-graph-cli==0.1.7`, `quality-graph-github==0.1.7`
- Local source verification: detached checkout at the commit above; `git rev-parse HEAD` returned the same value.
- Primary-source details and permalinks: `docs/quality-graph-primary-source-research-2026-09-02.md`.

The checked release documentation contains stale `0.1.2` snippets. FMonitor therefore treats the exact package versions plus the exact runtime SHA above as one indivisible release set.

## Migration baseline and parity boundary

The old mechanism is the repository-owned Make harness. It is not removed or weakened. Phase A compares old harness and Quality Graph runner/governance outcomes on identical representative heads. Phase B can only validate the trusted publisher after graph topology is present on the base branch; the initial unmerged bootstrap PR cannot prove that condition. Required-check cutover and deletion are separate future changes.

## Reproduction commands

```text
git status --short
find .github -maxdepth 3 -type f -print
sed -n '1,260p' Makefile
sed -n '1,260p' docs/development-process.md
rg --files specs reviews/tests reviews/code docs/operations tools
git -C /home/antropophag/code/fmonitor-2-quality-graph-audit rev-parse HEAD
```
