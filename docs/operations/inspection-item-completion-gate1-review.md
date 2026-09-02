# INSPECTION-ITEM-COMPLETE-001 — independent Gate 1 review

Date: 2026-09-01  
Reviewer: `/root/item_gate1_review` (independently tasked agent; did not author the reviewed artifacts)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Reviewed baseline and exact artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`
  (`docs: archive inspection evidence schema change`).
- Landed canonical v8 executable spec,
  `specs/INSPECTION-EVIDENCE-SCHEMA-001.md`:
  SHA-256 `82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7`.
- Landed canonical v8 main OpenSpec,
  `openspec/specs/deployment/canonical-inspection-evidence-schema/spec.md`:
  SHA-256 `5708fbaf7f2b98bea23c80c81c193b76a9db66b06061d2a124646a838671d3e9`.
- Owner authorization decision,
  `docs/operations/inspection-item-completion-authorization-decision.md`:
  SHA-256 `1dd4d91901d1871c744c6d6a751dadfffb9428362943911699de2d55787c606a`.
- Executable spec, `specs/INSPECTION-ITEM-COMPLETE-001.md`:
  SHA-256 `5c94eb10cf884b4c094fe276638f753302eddc3c5fca1ec9341e93cce1752fc7`.
- Change README, `openspec/changes/migrate-inspection-item-completion/README.md`:
  SHA-256 `b483aee923918fa973fb33d1dfe4391dd3326af436a12a09768f8d45e2e3d53a`.
- Proposal, `openspec/changes/migrate-inspection-item-completion/proposal.md`:
  SHA-256 `885398411339b22b80fce4ef00a8ba055148cd4fab938433606bd4ccb7f1a653`.
- Design, `openspec/changes/migrate-inspection-item-completion/design.md`:
  SHA-256 `01043554f64d30baa3997330a566feaf8c0f59f71fbd5f0416c3cd2102e9d038`.
- Tasks, `openspec/changes/migrate-inspection-item-completion/tasks.md`:
  SHA-256 `34fa418546e13de09435c66a7d66136f9fc87227e8bc2aa54c866166dee9ef6a`.
- Delta spec,
  `openspec/changes/migrate-inspection-item-completion/specs/inspection-evidence/item-completion/spec.md`:
  SHA-256 `33d7ace500a627d8712f4a588c65f24d98e944cc4f9fa19752b12a901148cac1`.

I also read `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, `docs/fmonitor-2-pilot-spec.md`, and
`docs/fmonitor-2-pilot-data-model.md`. `openspec validate
migrate-inspection-item-completion --strict` reports the change structurally
valid; that validation does not resolve the behavioral findings below.

## Findings

### G1-01 — BLOCKER: replay precedence contradicts the idempotency contract

The executable spec promises that, after current authorization succeeds, an
existing operation id with the same normalized command returns the original
`DUPLICATE` result (`INSPECTION-ITEM-COMPLETE-001`, lines 98–113). However, its
observable validation order checks current case state, template association and
current installer assignment before replay detection (lines 50–62).

Those facts can legitimately change after acceptance. For example, the spec
explicitly permits a later crew change from installer 1042 to 2048. An exact
replay of the accepted command for 1042 would then be rejected as
`INSTALLER_NOT_ASSIGNED`, rather than return `DUPLICATE(1)`. Closing the case or
changing/removing the current template association creates the same conflict.
The delta spec also simultaneously says an exact replay SHALL return the
original result and that an unopen case/invalid installer/unknown template is
rejected, without fixing precedence.

Action required: define one deterministic rule and align both specs and the
worked scenarios. The coherent offline-idempotency form is: recheck current
actor status/capability first; validate enough immutable command syntax to bind
the id; inside the case lock, resolve same-id same-normalized-payload to the
original result (or different payload to conflict) before revalidating mutable
case/template/crew preconditions for a first-time operation. Add a worked replay
scenario after crew reassignment (and preferably after case-state change) so a
Gate 2 test can prove the intended precedence.

### G1-02 — BLOCKER: persisted acceptance facts are not observable at a confirmed public seam

The only confirmed public seam is
`InspectionRecording::completeItem(...) -> ItemCompletionResult`, and tests are
explicitly forbidden from using repository/DB side channels (lines 18–27).
Nevertheless examples and requirements demand observation of the stored actor,
assigned engineer at receipt, installer snapshots, immutable template identity,
timestamps, exact fact counts, byte-equivalent replay state and winner-only
concurrency state (lines 76–90 and 143–183). The delta spec refers to an
“observable projection”, but neither artifact names its public interface or
defines its result.

Gate 1 requires every acceptance statement to be observable at the confirmed
seam. Action required: either include the acceptance/audit evidence necessary
for these assertions in a precisely defined `ItemCompletionResult`, or confirm
and specify a public read/projection seam used alongside the command seam,
including the fields needed by the acceptance examples. Tests may then exercise
that public contract without private SQL.

### G1-03 — BLOCKER: OpenSpec examples violate the executable command format

The executable command requires a canonical lowercase UUID for
`clientOperationId` and rejects malformed UUIDs as `INVALID_COMMAND` (lines
35 and 124–126). The delta spec's first acceptance, replay and conflict examples
instead use `op-4512-28-1` (delta lines 10–12 and 45–51), which cannot reach the
stated accepted/duplicate/conflict outcomes.

Action required: replace the symbolic id in every normative OpenSpec scenario
with the exact canonical UUID used by the executable worked examples, or change
the executable input contract deliberately and consistently. Re-run strict
validation after reconciliation.

## Checks that pass subject to the blockers

- Authorization is coherent with the owner decision: exact capability grants
  intentional all-object scope; current engineer assignment is audit/routing
  context, not admission; reassignment alone does not reject; active status and
  exact capability are rechecked at every server receipt; `deviceTime` is not
  authority time.
- The concurrency outcome is deterministic at the stated public-result level:
  one `ACCEPTED(1)` and one `STALE_REVISION(1)`, with zero partial loser facts.
- Canonical v8 remains the sole owner of revisions, operations, installer
  evidence and photos. The slice adds no migration/version, requires fail-closed
  schema consumption, and does not depend on inspection-planning schema.
- The requested assigned-engineer-at-receipt audit can fit the existing v8
  operation `payload_json`; actor, device/server times, template identity and
  installer snapshots already have canonical v8 storage. No v8 DDL extension is
  specified or required by the reviewed plan.
- HTTP/UI/rapid-pilot ownership is correctly constrained to translation and one
  call to the application seam; business SQL belongs in the persistence adapter,
  and runtime DDL/fallback writes are prohibited.

Owner approval and Gate 2 MUST wait for a fresh independent review of the
reconciled exact artifact hashes.
