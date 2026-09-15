# TASK-CONTEXT-MANIFEST-001 — compact deterministic delivery context

Issue: #157, bounded child T02 of #145. Source oracle: canonical repository documents, existing verification input/planner output and current harness package contract. Public seam: `python3 tools/delivery/harness.py prepare` and its generated role `package.json`.

## R1 — canonical, deterministic, reconstructible

Prepare SHALL emit a machine-readable manifest bound to exact change/issue identity, base, candidate source, role, instruction/policy source digests and section-index digest. Each required item SHALL name the canonical source, stable rule/section identifier (or `FULL_DOCUMENT`), reason, digest, load mode and exact reconstructible content reference. Extracted content SHALL be exact canonical bytes/characters, never a generated summary or normative copy. Same immutable semantic inputs SHALL produce identical semantic manifest content; fresh prepare SHALL re-read current canonical sources.

## R2 — deterministic applicability with fail-safe fallback

Applicability SHALL use only planned/changed paths, planner lane/risk, issue/OpenSpec binding, existing verification policy and repository-owned document roles. It SHALL cover:

- A: bounded presentation receives complete applicable general/UI/delivery rules while unrelated persistence/security history is not inline;
- B: persistence/current-state receives persistence/domain/security instructions and cannot use UI-only context;
- C: auth/security receives mandatory security/auth instructions;
- D: harness/verification receives delivery/verification governance without automatic product history inline;
- F: unknown boundary requires the conservative full safe context.

If a document cannot be safely sectioned, it remains required in full. No manifest selection changes FAST classification, verification coverage, Gates or planner output.

## R3 — freshness and safe section indexing

Every source/range SHALL be digest-bound. Changed canonical source, changed section-index version/digest, missing/non-unique heading or invalid range SHALL cause rebuild or fail-closed full-document fallback, never silent omission (E, K). Old manifest bytes are historical package evidence only and SHALL NOT be silently reused for a fresh package.

## R4 — real role-package integration

All root/executor/reviewer prepares SHALL reference the manifest and its exact materialized required-context artifact from the existing `package.json`; active binding/state SHALL expose enough navigation for the current package. Reviewer packages SHALL include applicable canonical refs/digests plus candidate/spec/evidence/review refs and reconstructible load-on-demand links (I). Existing evidence and admission requirements remain authoritative: manifest presence alone MUST NOT create approval or GREEN (L).

Fresh package materializes current required context (G). Re-reading the same immutable artifact within a single package needs no second expansion and repeated semantic inputs yield the same manifest identity (H).

## R5 — current versus history

Current actionable goal MAY be required. Explicit current evidence SHALL remain in the reviewer package. Historical delivery goals, reviews and evidence not explicitly bound to the current task SHALL remain append-only and appear only as load-on-demand references by default (J).

## R6 — deterministic measurement

The repository SHALL provide a reproducible before/after report for these completed inputs:

1. bounded presentation: `openspec/changes/unify-yii-main-navigation/verification-input.json`;
2. persistence/current-state: `openspec/changes/standalone-control-engineer-assignment/verification-input.json`;
3. harness/verification: `openspec/changes/canonical-verification-inventory/verification-input.json`.

For each, report mandatory materialized bytes, Unicode characters, full canonical documents, load-on-demand references, documents previously passed/read whole, and obvious historical/unrelated material. Bounded presentation SHALL show a meaningful mandatory-context reduction; sensitive cases SHALL retain all applicable instructions. Unsupported actual token telemetry SHALL be reported as `UNKNOWN`; only reduced mandatory context bytes and expected token-pressure reduction may be claimed.

## Non-goals

No product code, NLP/LLM selection, summaries, vector/RAG/search platform, second policy/planner, per-issue manual metadata, mass canonical-doc rewrite, FAST/coverage/Gate change, #107 completion, T03–T14, root rotation, supervisor, CI performance or rapid-pilot cleanup.

