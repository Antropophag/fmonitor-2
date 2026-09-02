# QUALITY-GRAPH-GOVERNANCE-001 v0.5 errata — independent Gate 1 re-review

Date: 2026-09-02  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification, errata, owner approval, OpenSpec artifacts, tests, or implementation  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.5 with schema errata  
Owner approval: `docs/operations/quality-graph-governance-v05-owner-approval-2026-09-02.md`  
Prior review: `docs/operations/quality-graph-governance-gate1-rereview-v5.md`  
Verdict: **APPROVED**

## Resolution of v0.5 findings

1. **Metadata status and nullable hash — resolved.** RED, test-review and GREEN test entries, and GREEN/code-review implementation entries, now have the strict shape `{path,status,sha256}`. `status` is exactly `A|M|D`; `sha256` is a lowercase content digest for `A`/`M` and JSON `null` only for `D`. The authoritative metadata can therefore repeat the same exact set as the receipt without violating `additionalProperties:false`.

2. **Deletion validation — resolved.** Artifact paths remain current regular non-symlink files. Changed-file entries are stage-aware: `A`/`M` require a regular non-symlink target and matching digest at the right-hand stage; `D` requires absence at that stage, `null` hash and a regular non-symlink predecessor. This removes the earlier contradiction that required a deleted path to exist in the current checkout.

3. **Rename and unsupported type status — resolved.** Both complete sets use exact `git diff --no-renames --name-status` ranges, so a rename is represented as `D` plus `A`. Any remaining status outside `A|M|D`, explicitly including `T`, fails closed as `unsafe_path`. Bytewise sorting makes metadata/receipt comparison deterministic.

## Full Gate 1 assessment

- The public seam has exact success/failure output and uses the checkout-derived head, with an additional `GITHUB_SHA` equality check in CI.
- The receipt and authoritative Markdown metadata are strict and content-addressed; identity, verdict, spec authorship, exact tests and exact implementation changes cannot be supplied only as unverified receipt assertions.
- All gate-record commits are Git-derived without self-containing SHA fields. Strict ancestry proves RED before independent test approval, implementation after approval, and independent code review after the exact implementation commit.
- Complete `tests/**` and remaining repository name-status diffs cover helpers, fixtures, configuration and deletions. Later evidence-envelope changes are narrowly enumerated and cannot alter governed implementation, tests, spec or graph without invalidating Gate 5.
- Missing/unsafe artifacts, hash or metadata drift, non-independent reviews, bad chronology, stale commits, broken supersession and stale graph provenance have observable fail-closed outcomes.
- The representative-PR matrix distinguishes actual old-harness parity from new governance coverage, requires identical PR heads and run evidence, and does not claim publisher parity before topology exists on the base branch.
- Quality Graph remains a thin orchestrator over repository-owned commands; approvals remain disabled; branch protection, merge, old-harness removal and parity waiver remain outside authority.
- The OpenSpec proposal, design, delta specification and tasks are coherent with the executable contract, including derived commits, exhaustive name-status coverage, immutable receipts, actual unmerged PR phase A and the explicit phase-B blocker.

## Gate decision

All ambiguities previously identified as affecting behavior are resolved, and the acceptance outcomes are observable at `make delivery-evidence-check` or the pinned Quality Graph publisher seam. Owner approval is recorded for v0.5. Gate 1 passes; the slice may proceed to Gate 2 RED. This approval does not approve tests or implementation and does not waive independent Gates 3 or 5.

Implementation tests should freeze path-specific first-introduction lookup and include `A`, `M`, `D`, rename-as-`D+A` and rejected `T` fixtures so the approved Git semantics cannot drift.
