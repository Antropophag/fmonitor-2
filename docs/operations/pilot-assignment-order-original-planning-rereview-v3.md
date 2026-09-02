# Independent planning rereview v3 — pilot assignment-order original

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_planning_rereview_v3`  
Reviewed change: `replace-pilot-registration-with-original-upload`  
Verdict: **APPROVED**

## Reviewed immutable inputs

```text
d6a5261cbbd7f12c2c8fd5b21f9d23d93040576d0060a0730900d2617901c566  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
8594110efe46a811476962709707549ca0aa87608467430017da60102585dc12  openspec/changes/replace-pilot-registration-with-original-upload/design.md
8bac30db8fc55a14c482216d42452e97f66df677209e46d75b89f0ad0b5ea9c2  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
277e42a2a2fc5e81f8e84c7db6291f5a8fa1d3777438e20279deea0c6cb28182  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
```

Owner evidence reviewed: `docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md`. Process and product inputs reviewed: `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`, `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, and all three preceding independent planning reviews.

## Findings

No blocking or major planning finding remains in the reviewed hashes.

## Resolution of the v2 blockers

1. **Canonical pilot truth is coherently superseded.** `CONTEXT.md`, the pilot spec and the pilot data model now make the accepted signed PDF original the document basis for the separately executed opening command. They explicitly remove manual order-number entry, `confirmRegistration` and `registered` from the target pilot gate, while preserving historical registration facts as read-only compatibility. The implemented legacy opening gate is accurately named as a predecessor owned by `open-installation-from-assignment-order-original`, not presented as current GREEN behavior. The hashes recorded in completed task 1.1 exactly match the reviewed files.
2. **The command seam uses process-only authorization.** The normative initial and correction scenarios now require only exact process capabilities `assignment_order.original.upload` and `assignment_order.original.correct`, plus active user/role and an explicit capability row. HTTP/local RBAC is expressly outside this slice. The separately reserved future local read permission remains `assignment_order.original.read` and cannot be inferred from mutation capabilities or display names.

## Vertical and cross-document coherence

- The change remains a bounded command-only vertical slice: one public command with `INITIAL` and `CORRECTION`, immutable DTO/result contracts, deterministic PDF/date rules, semantic replay, append-only CAS lineage and private storage/commit failure semantics.
- Accepted upload/correction does not apply composition, open the case, change the actual start date or enable the checklist. HTTP upload/read/download, sequential composition applicability and the new opening gate remain three separately named future lifecycle slices.
- The owner-approved actors map to builtin technical role codes `fkr_operator` and `manager` through explicit process capability grants. The visible label «Руководитель ФКР» does not authorize and the technical code remains stable.
- Shape and authorization precede stream access. Replay precedence is exact: stored request identity, then accepted-operation fingerprint even for a now-superseded correction target, then current-target/revision/no-change validation.
- Storage finalizes only to a private content-addressed identity before the atomic DB commit. Definite and ambiguous persistence failures, response loss, private orphan reconciliation/reuse and retry results have distinct testable outcomes without a public orphan.
- The planning sequence obeys the delivery gates: remaining active-contract disposition and executable-spec approval precede RED; independent test review precedes production GREEN; verification and fresh code review follow GREEN.

Task 1.2 remains intentionally open and must finish before executable-spec approval. This is a scheduled pre-Gate disposition obligation, not an ambiguity in the reviewed contract and not grounds to withhold planning approval. This review does not approve executable specs, tests, production code, GREEN or Done; exact-hash owner approval remains required by tasks 1.3–1.5.

No reviewed OpenSpec artifact, canonical truth document, executable test or production file was edited by this reviewer. This append-only review record is the only review output.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0 at review time before this review record was added)
```

