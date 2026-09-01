# Pilot generation metadata planning rereview

- Review date: `2026-09-02`
- Reviewer task: fresh independent rereview of all four updated planning
  artifacts for `separate-pilot-generation-metadata`
- Reviewed artifacts: `proposal.md`, delta specification, `design.md`, and
  `tasks.md`
- Independence: the reviewer did not author or edit the reviewed OpenSpec
  artifacts, operations status, runtime code, or tests.

## Authoritative evidence checked

- `docs/operations/test-user-release-contour-decision.md` and its decision
  review;
- `docs/operations/pilot-generation-metadata-evidence.md` and the independent
  evidence rereviews ending in `READY_FOR_OPENSPEC`;
- the previous
  `docs/operations/pilot-generation-metadata-planning-review.md` with four
  blocking findings;
- `PRODUCT.md`, `CONTEXT.md`, the pilot specification/data model, and
  `docs/development-process.md`;
- current OpenSpec status and all concrete artifact paths reported for
  `separate-pilot-generation-metadata`.

## Rereview findings

No blocking planning defect remains.

1. **Release contour and compatibility boundary are now explicit.** Every
   artifact treats Compose `make up` as the sole TEST-USER release seam and the
   future working deployment direction. The standalone CLI is only a synthetic
   fixture harness, never a readiness oracle or equal contour. Default
   host/image separation is represented accurately, while every supported
   co-location requires an explicit discriminator and zero cross-contour
   mutation across state roots, manifests/artifacts, and DB prefixes/tables.

2. **The creation and recovery state machine is coherent.** On empty owned
   volumes `make up` selects prepare, separately gated prerequisites, readiness
   proof, finalize, and publication. Existing ready state is validation-only;
   incomplete or ambiguous state fails closed. Recovery repeats the full
   readiness verifier rather than inferring readiness from a matching sentinel
   and owner marker. Proposal, specification, design, and tasks describe the
   same lifecycle.

3. **Consumer identity is feasible in the real Compose topology.** The package
   compares logical database name and live server identity after each consumer
   connects through its own endpoint; it does not require the pilot proxy and
   worker service endpoint to be textually equal. Listener-port reconciliation
   is scoped to HTTP startup. State-changing consumers retain validation before
   writes and an in-boundary identity recheck.

4. **Owner decision is not widened into production operations.** The common
   Compose direction does not claim approval for production credentials,
   backup, scaling, availability, or destructive operations. The sentinel
   remains setup-only and excluded from the production migration catalogue;
   no product/domain persistence redesign is introduced.

5. **Gate and dependency boundaries remain intact.** GRILL-004 still controls
   fixture contents, personal-data exclusion, and the exact reset policy.
   DDL-free canonical bootstrap predecessors must land before Gate 1. The
   package explicitly withholds executable-spec approval, RED, implementation,
   and reset authority. Task 1.1 correctly remains unchecked because recording
   the contour choice is only one part of the composite task: supported
   topology/runbook proof, predecessor confirmation, GRILL-004 reset scope, and
   strict completion evidence still remain.

## Closure of the previous planning review

- The two live generation contours are delimited and the authoritative contour
  is selected.
- Prepare/prerequisites/readiness/finalize/recovery now form one durable plan.
- Logical DB identity is separated from adapter-specific transport endpoints.
- Fresh-volume `make up` has an explicit successful clean-create path.

The remaining executable details called out previously—stable errors and
transcripts, lock/timeout contract, filesystem durability support, and exact
cleanup evidence—are correctly retained for Gate 1 and do not make the current
planning package contradictory.

## Verification

- `openspec validate separate-pilot-generation-metadata --strict`: PASS.
- `git diff --check`: PASS.
- `make architecture-check`: PASS (`6 rules`).

## Verdict

**READY_WHEN_PREDECESSORS_AND_GRILL004_LAND**

Planning is coherent and the release-contour decision is incorporated without
inventing production operations. No Gate 1, RED, implementation, destructive
reset, or fixture-content work is authorized by this verdict.
