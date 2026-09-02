# QUALITY-GRAPH-GOVERNANCE-001 v0.5

```delivery-metadata
{"schemaVersion":1,"kind":"spec","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root"}
```

Status: APPROVED for Gate 2 on 2026-09-02; see `docs/operations/quality-graph-governance-gate1-rereview-v6.md`.

## Простыми словами

На каждом PR CI запускает существующие проверки FMonitor и доказывает, что спецификация, RED, независимые reviews, GREEN и проверенный commit принадлежат одной неизменной цепочке. Quality Graph связывает результаты, но не заменяет Make-команды или решения reviewers.

Slice не меняет продукт или branch protection, не удаляет старый harness и не мержит PR.

## Actor and public seam

- Actor: PR author, separately tasked test reviewer, separately tasked code reviewer and trusted CI publisher.
- Public seam: `make delivery-evidence-check` from repository root.
- Source oracle: `docs/development-process.md`, `specs/HARNESS-FULL-AGGREGATION-001.md`, repository review templates and pinned Quality Graph v0.1.7 primary sources.

The seam MUST resolve the checkout with `git rev-parse HEAD`; under GitHub Actions it MUST additionally require equality with `GITHUB_SHA`. It accepts no production head or receipt-root override. It MUST print exactly one terminal `DELIVERY_EVIDENCE_OK receipts=<n> head=<sha>` and exit zero only when every current receipt is valid. Otherwise it MUST print at least one `DELIVERY_EVIDENCE_FAILURE category=<stable-category> receipt=<path> detail=<bounded-detail>`, MUST NOT print success, and MUST exit nonzero.

## Canonical evidence metadata

Every governed Markdown artifact starts with a fenced `delivery-metadata` JSON object before narrative. Unknown JSON fields are forbidden. Canonical identities are non-empty ASCII `human:<github-login>` or `agent:<canonical-task-path>`.

- Specification: `schemaVersion`, `kind:"spec"`, `sliceId`, `author`; it contains no self hash or commit.
- RED: `schemaVersion`, `kind:"red"`, `sliceId`, `author`, `specPath`, `specSha256`, `baseCommit`, ordered `tests[{path,status,sha256}]`, `command`, `observedFailure`, `recordedAt`.
- Test review: `schemaVersion`, `kind:"test-review"`, `sliceId`, `reviewer`, `verdict`, `specSha256`, ordered `tests[{path,status,sha256}]`, `redCommit`, `recordedAt`.
- GREEN: `schemaVersion`, `kind:"green"`, `sliceId`, `author`, `specSha256`, ordered `tests[{path,status,sha256}]`, `testReviewRecordPath`, ordered `implementationFiles[{path,status,sha256}]`, `commands`, `recordedAt`.
- Code review: `schemaVersion`, `kind:"code-review"`, `sliceId`, `reviewer`, `verdict`, `specSha256`, ordered `tests[{path,status,sha256}]`, `implementationCommit`, ordered `implementationFiles[{path,status,sha256}]`, `recordedAt`.

The checker MUST parse these authoritative blocks and require exact equality with receipt values. A receipt cannot supply identity, verdict, hash or commit absent from artifact metadata. `recordedAt` is audit information; chronology is proven through Git history. Historical Markdown without an opt-in receipt remains outside enforcement.

## Receipt v1 contract

Each immutable `delivery/evidence/<slice-id>/<receipt-id>.json` is strict UTF-8 JSON. `receiptId` matches `[a-z0-9][a-z0-9-]{0,62}` and `supersedes` is `null` or an older receipt ID in the same slice directory. Required shape:

```json
{
  "schemaVersion": 1,
  "sliceId": "QUALITY-GRAPH-GOVERNANCE-001",
  "change": "integrate-quality-graph-governance",
  "receiptId": "qg-governance-v1",
  "supersedes": null,
  "baseCommit": "<pre-slice commit>",
  "authors": {"spec": "agent:/root", "test": "agent:/root", "implementation": "agent:/root"},
  "artifacts": {
    "spec": {"path": "specs/QUALITY-GRAPH-GOVERNANCE-001.md", "sha256": "<64 lowercase hex>"},
    "tests": [{"path": "tests/Verification/quality_graph_governance_001_test.php", "status": "A", "sha256": "<64 lowercase hex>"}],
    "red": {"path": "docs/operations/quality-graph-governance-red-evidence.md", "sha256": "<64 lowercase hex>"},
    "testReview": {"path": "reviews/tests/QUALITY-GRAPH-GOVERNANCE-001.md", "sha256": "<64 lowercase hex>", "reviewer": "agent:<task-path>", "verdict": "APPROVED", "specSha256": "<spec hash>"},
    "green": {"path": "docs/operations/quality-graph-governance-green-evidence.md", "sha256": "<64 lowercase hex>"},
    "codeReview": {"path": "reviews/code/QUALITY-GRAPH-GOVERNANCE-001.md", "sha256": "<64 lowercase hex>", "reviewer": "agent:<task-path>", "verdict": "APPROVED", "specSha256": "<spec hash>", "reviewedCommit": "<implementation commit>"}
  }
}
```

`additionalProperties` is forbidden at every level. Paths MUST be normalized repository-relative and remain inside the repository after canonical resolution. For `A`/`M`, the target MUST be a regular non-symlink file and lowercase SHA-256 MUST match stage-commit content. For `D`, `sha256` MUST be JSON `null`, the path MUST be absent at the stage commit, and its parent-commit target MUST have been a regular non-symlink file. Referenced evidence files themselves are always current regular non-symlink files.

Review metadata MUST agree exactly with the receipt and verdict MUST be `APPROVED`. Test/code reviewers MUST differ from test/implementation authors. Both review spec hashes MUST equal the current spec hash.

The checker derives RED, test-review, GREEN/implementation and code-review commits as the unique first Git commit containing each exact evidence blob (`git log --diff-filter=A` plus blob verification); neither artifacts nor receipt contain RED/GREEN self-commit fields. Git MUST prove strict ancestry `base < RED < test-review < GREEN/implementation < code-review ≤ current HEAD`; unrelated, reversed or equal gate commits fail. Code review MUST name the derived exact GREEN/implementation commit.

The receipt's ordered test set MUST equal the complete bytewise-sorted `git diff --no-renames --name-status base..RED -- tests/` result. Every entry records `path`, exact `status` (`A`, `M` or `D`) and SHA-256 at RED (`null` only for `D`). RED, both reviews and GREEN repeat the exact set. Thus helpers, fixtures, bootstrap files and nonstandard test names are included.

Between test review and GREEN, executable spec and `tests/**` MUST be unchanged. The ordered implementation set MUST equal the complete bytewise-sorted `git diff --no-renames --name-status test-review..GREEN` result after excluding only the exact GREEN evidence path and this change's OpenSpec `tasks.md`; entries use the same `path/status/sha256-or-null` contract. Renames are represented as `D` plus `A`. Any remaining status other than `A`, `M` or `D`, including type change `T`, fails `unsafe_path`. GREEN and code review repeat the set. There are no directory/pattern exclusions for source, configuration or deletion.

Evidence-envelope commits after the reviewed implementation commit MAY change only the code-review record, receipt chain, OpenSpec task status and parity/final-verification evidence. Any governed implementation path, test or executable spec change after it is `commit_mismatch` and requires new GREEN and Gate 5 review. This avoids all self-reference.

One unique receipt leaf per slice is current. `supersedes` MUST form one acyclic chain of immutable files and point to a receipt committed earlier. Historical receipts remain. Multiple leaves, missing targets, cycles, reused IDs or in-place historical modification fail.

Stable categories: `invalid_input`, `missing_receipt`, `invalid_schema`, `duplicate_slice`, `invalid_history`, `unsafe_path`, `missing_artifact`, `hash_mismatch`, `metadata_mismatch`, `stale_spec`, `non_independent_review`, `gate_order`, `commit_mismatch`.

## Required behavior

1. Checker MUST be deterministic and offline.
2. It discovers fixed `delivery/evidence/*/*.json` in bytewise path order and aggregates failures. Missing/unreadable roots, malformed/non-UTF-8 JSON, non-regular files and symlinks fail closed. A test-only executable MAY accept an explicit canonical temporary repository root.
3. `make verify` retains current stages and semantics. Graph nodes call repository commands rather than duplicate them.
4. Graph declaration/generated drift, missing nodes/results/reports, nonzero commands and provenance mismatch are blocking.
5. Result provenance binds node ID, repository, PR, head SHA, workflow run ID, run attempt and graph digest.
6. Quality Graph approvals are disabled; only repository-owned APPROVED records satisfy Gates 3 and 5.
7. PR runner has read-only permissions and no persisted checkout credential. Trusted publisher uses base-branch topology, does not checkout/execute PR code, and has only required artifact/check permissions.
8. CLI/provider packages are exactly `0.1.7`; runtime is exactly `alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef`. Floating or mixed pins fail architecture validation.

## Acceptance examples

- Valid chain: Git-derived complete path sets, matching metadata/hashes, distinct reviewers and strict gate ancestry exit `0` with one terminal success.
- Missing/empty receipt root: `missing_receipt`.
- Mutated artifact: `hash_mismatch`; updated spec with stale review hashes: `stale_spec`.
- Receipt, test set or implementation set differing from authoritative metadata: `metadata_mismatch`.
- Reviewer equal to relevant author: `non_independent_review`.
- Unrelated, reversed or non-strict required ancestry: `gate_order`.
- Code review names another implementation commit, or source/test/spec/graph changes after review: `commit_mismatch`.
- Absolute/escaping path, symlink or non-regular artifact: `unsafe_path`.
- Duplicate slice identity: `duplicate_slice`; broken/multiple-leaf supersession: `invalid_history`.
- Result v0 with another node/repository/PR/head/run/attempt/digest: publisher rejection.

## Representative PR parity matrix

Phase A MUST use an actual open GitHub PR against `main`; old harness and graph runner records carry the same PR head SHA. Local fixtures supplement but do not replace run URLs/IDs.

| Case and fixture | Old `make verify` | Governance/node | Aggregate | Classification |
|---|---|---|---|---|
| valid evidence and commands | PASS | PASS | PASS | parity |
| existing repository stage forced to exit 1 | FAIL, same stage | verify node FAIL | FAIL | parity |
| declaration differs from generated output | N/A | validation FAIL | FAIL | added governance coverage |
| current receipt removed | N/A | `missing_receipt` | FAIL | added governance coverage |
| governed spec byte mutated | N/A | `hash_mismatch`/`stale_spec` | FAIL | added governance coverage |
| Result replayed with different head/run/attempt/digest | N/A | publisher rejects | FAIL | added publisher coverage |
| expected node artifact omitted | N/A | missing result | FAIL | added publisher coverage |

Parity rows require the same command/stage/aggregate outcome on identical head. Added-coverage rows pass only on the exact fail-closed outcome and are not old-harness parity. Phase B requires publisher execution against topology already present on base. Until both phases complete, old CI/harness remains mandatory and required checks are unchanged.

## Authorization, audit, history and concurrency

GitHub/repository policy owns review authority; checker proves recorded lineage and distinct canonical identities, not cryptographic real-world identity. Immutable receipts and explicit supersession preserve history. Re-running one checkout is idempotent. Another head, run attempt or graph digest cannot satisfy the current run. Corrections add a receipt; spec/test/implementation changes invalidate downstream gates.
