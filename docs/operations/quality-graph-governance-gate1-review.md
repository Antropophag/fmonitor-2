# QUALITY-GRAPH-GOVERNANCE-001 — independent Gate 1 review

Date: 2026-09-02  
Reviewer: `codex:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the reviewed specification, OpenSpec artifacts, baseline audit, or upstream research  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.1  
Change: `integrate-quality-graph-governance`  
Verdict: **CHANGES_REQUESTED**

## Reviewed sources

- `docs/development-process.md`
- `specs/QUALITY-GRAPH-GOVERNANCE-001.md`
- `openspec/changes/integrate-quality-graph-governance/{proposal.md,design.md,tasks.md}`
- `openspec/changes/integrate-quality-graph-governance/specs/delivery/quality-graph-governance/spec.md`
- `docs/operations/quality-graph-governance-baseline-audit.md`
- `docs/quality-graph-primary-source-research-2026-09-02.md`

## Blocking findings

1. **The Gate 5/exact-HEAD lineage is not constructible as specified.** The receipt is a Git-tracked file and hashes the Git-tracked code-review record, while the code review must approve the exact `HEAD_SHA` supplied to the checker. Adding the review record and final receipt necessarily creates a commit after the implementation commit that the reviewer inspected; reviewing that later commit and then updating the receipt to name/hash its review creates another commit. The contract needs a non-circular identity, for example an exact reviewed implementation tree/commit plus a separately identified append-only evidence-envelope commit whose permitted delta is machine checked. Until that rule is explicit, neither a RED test nor a conforming receipt can independently derive the expected accepted head.

2. **Reviewer identity, artifact authorship, and verdict are self-asserted by the receipt rather than bound to the authoritative records.** The contract says Markdown review records remain authoritative, but receipt fields can claim a different reviewer, author, verdict, spec hash, or reviewed commit while still hashing an arbitrary Markdown file. No canonical machine-readable header/sidecar schema or exact cross-check is specified. Consequently a receipt author can manufacture apparent independence and `APPROVED`. Define canonical human and agent identity forms, required machine-readable fields for RED/GREEN/review records, their authorship source, and exact equality rules between those records and the receipt. Missing or conflicting metadata must fail closed.

3. **Gate ordering is asserted, not proven.** Literal sequence values `2,3,4,5` do not establish that RED preceded test approval or that GREEN followed it. The design/tasks promise timestamps/sequence and the OpenSpec delta says the checker rejects an invalid gate order, but the executable contract has neither timestamps nor a Git ancestry/containment rule beyond equality within RED/review and GREEN/review pairs. Specify the observable ordering evidence and rejected histories, including unrelated commits, RED at/after implementation, amended/replaced reviews, and downstream invalidation after spec/test changes.

4. **The representative-PR oracle is not exact enough for the promised migration decision.** The baseline establishes that no GitHub workflow currently exists, so “old CI/harness” means local repository commands, whereas several negative cases (graph drift, missing receipt/result, stale publisher provenance) have no old-harness counterpart. The matrix does not state the exact expected old result, graph node result, aggregate result, command/fixture, and parity classification per case. It also does not define what qualifies as the representative PR/ref, how identical heads are demonstrated, or whether Phase A requires an actual GitHub PR and run IDs rather than a local equivalent. Add a tabular, independently executable matrix and distinguish coverage added by governance from parity with the existing harness.

5. **Audit and correction history are underspecified at the public seam.** “Append-only by Git commits and content hashes” does not state which replacement/supersession fields are allowed, whether more than one receipt for a slice is always a duplicate, or how a rejected/stale receipt remains retained while a corrected receipt becomes current. The current uniqueness rule and “correction creates new ... receipt content” can require overwriting the sole receipt, which conflicts with the stated append-only history. Specify immutable receipt identity, supersession/current-selection rules, and deterministic behavior when historical and current receipts coexist.

6. **Input and discovery authorization boundaries need exact rejected outcomes.** `RECEIPT_DIR` is described as repository-relative, but the contract only explicitly constrains referenced artifact paths. Define rejection of absolute, escaping, symlinked, missing, unreadable, and non-directory receipt roots; define filename/discovery ordering and malformed/non-UTF-8/non-regular receipt handling. Also state that CI-provided `HEAD_SHA` is verified against the checked-out Git commit (or deliberately define it as a caller assertion); otherwise a caller can choose the SHA that makes a stale receipt pass.

## Owner approval assessment

The user's instruction `Внедряй` authorizes implementation of the already presented OpenSpec change and the in-scope delivery/CI mutations. It does **not** by itself prove approval of the exact executable contract in `QUALITY-GRAPH-GOVERNANCE-001` because that file and its new receipt semantics were created after the instruction and were not presented to the owner. Its current status line therefore overstates the evidence. After the blocking ambiguities above are resolved, record explicit owner confirmation of the revised executable outcomes before Gate 2, or cite an earlier owner-approved artifact that contains those exact outcomes. The instruction does authorize creating an unmerged representative PR; it does not authorize merge, branch-protection changes, or removal of the old harness.

## Non-blocking observations

- The chosen public seam is appropriately repository-owned and offline, and the graph remains a thin orchestrator over `make verify` and the lineage checker.
- The opt-in `delivery/evidence/*.json` boundary preserves historical evidence without bulk migration.
- The base-branch-topology bootstrap limitation is correctly acknowledged. Phase B cannot be declared complete on the initial unmerged graph-changing PR; this must remain an explicit incomplete migration state rather than being waived.
- Quality Graph approvals are explicitly disabled and the runner/publisher trust boundary is directionally consistent with the audited upstream v0.1.7 behavior.

## Conditions for re-review

Revise the executable spec and coherent OpenSpec artifacts to resolve findings 1–6, remove the unsupported approval claim, obtain the required owner confirmation, and request a fresh independent Gate 1 review. No Gate 2 test should be treated as approved against v0.1.
