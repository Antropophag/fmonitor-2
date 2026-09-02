# Independent Gate 1 executable-spec review — assignment-order original upload

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_spec_review`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`  
OpenSpec change: `replace-pilot-registration-with-original-upload`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or modify the reviewed specification, OpenSpec
artifacts, canonical product documents, tests, or production code. This
append-only review record is the only review output.

## Reviewed immutable inputs

```text
10b5f401395ee8c2be97c04fa0700a5c07ac31c4021d317e28d7d28223a42aee  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d6a5261cbbd7f12c2c8fd5b21f9d23d93040576d0060a0730900d2617901c566  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
8594110efe46a811476962709707549ca0aa87608467430017da60102585dc12  openspec/changes/replace-pilot-registration-with-original-upload/design.md
8bac30db8fc55a14c482216d42452e97f66df677209e46d75b89f0ad0b5ea9c2  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
277e42a2a2fc5e81f8e84c7db6291f5a8fa1d3777438e20279deea0c6cb28182  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
```

The prior planning approval at
`docs/operations/pilot-assignment-order-original-planning-rereview-v3.md` was
also reviewed. It explicitly did not approve the executable specification and
left task 1.2 as a prerequisite.

## Blocking findings

### AOOU-G1-01 — required pre-spec dependency disposition is still open

OpenSpec task 1.2 requires disposition of
`docs/installation-process-interface.md`, the behavior inventory, active
E2E/RBAC/PDF changes/specs/tests, and downstream registered-crew
characterization **before executable spec**. It remains unchecked. This is not
merely stale task bookkeeping: current active files still prescribe the
superseded manual-registration behavior. Examples include:

- `docs/installation-process-interface.md`: `confirmOrderRegistration`,
  `registered`, and `assignment_order.confirm_registration` remain the stated
  active application/opening contracts;
- `docs/operations/pilot-behavior-inventory.md`: registration and opening still
  use the same active terms and fixtures;
- `specs/PILOT-E2E-FLOW-001.md`: the route, form, capability, success state and
  canonical E2E sequence still require a manually entered registration number;
- `pilot-e2e-combined-pdf` still describes prepared/registered card behavior.

The executable spec therefore does not yet have one unambiguous normative
dependency set. Complete the inventory/disposition and mark each affected
artifact as amended target truth, explicit predecessor, or immutable legacy
characterization. Only then may task 1.2 be checked and the resulting exact
hash batch rereviewed.

### AOOU-G1-02 — exact replay requires both reading and not reading the stream

The accepted-operation fingerprint includes `pdfSha256`. Section 8 requires a
same-request lookup to distinguish the same fingerprint (`REPLAYED`) from a
different fingerprint (`IDEMPOTENCY_KEY_REUSED`). That distinction cannot be
made for a supplied byte stream without reading and hashing it. However Example
B and the RED contract require a lost-response same-request replay with stream
read count unchanged and no stream effect.

An implementation can satisfy only one side: trust `requestId` without reading
the stream and miss changed-PDF key reuse, or read the stream and violate the
zero-read replay oracle. Define an executable protocol, for example an explicit
caller-supplied expected content digest/size authenticated against the consumed
stream, or require stream consumption for fingerprint comparison and narrow
the no-effect assertion to no storage/domain mutation. The chosen public DTO
and precedence must be literal.

### AOOU-G1-03 — correction target identity cannot express `TARGET_NOT_CURRENT`

The DTO exposes only `correctionTargetOriginalId` plus `expectedRevision`.
Initial acceptance creates one `originalId`, and Example C returns that same
`originalId` for revision 2. Yet sections 8–9 separately require a target that
can become a non-current leaf and distinguish `TARGET_NOT_CURRENT` from
`STALE_REVISION`.

With a lineage-stable `originalId`, an old leaf is represented only by its
revision, so it is stale, not a distinct non-current target. If every revision
has its own opaque target ID, the Result/lineage model and Example C are wrong
or incomplete because no revision ID is returned. Define lineage ID versus
revision/evidence ID explicitly, state which one the command targets, and give
deterministic precedence for wrong lineage, superseded revision identity and
stale numeric revision.

### AOOU-G1-04 — rejection/failure matrix contradicts the OpenSpec delta

The executable spec maps malformed/unsafe PDF validation to
`REJECTED/NOT_PDF|INVALID_PDF|UNSAFE_PDF`, stream read failure to
`FAILED/STREAM_FAILURE`, and stage/finalize failure to
`FAILED/STORAGE_FAILURE`. The OpenSpec delta's “Storage failure до commit”
scenario groups “stream, validation, staging or private finalize” and mandates
`FAILED/STORAGE_FAILURE` for all of them. A RED author has no single oracle.

Amend the delta to preserve business rejection for completed invalid input,
`STREAM_FAILURE` for incomplete/unreadable input, and `STORAGE_FAILURE` for
staging/finalize infrastructure failure, or change the executable matrix. The
two approved artifacts must agree exactly.

### AOOU-G1-05 — `COMPOSITION_NOT_CONFIRMED` is unreachable under the DTO rules

The command DTO defines `compositionConfirmation` as literal `true`, and all
shape errors return `REJECTED/INVALID_COMMAND` before stream access. The result
matrix separately promises `REJECTED/COMPOSITION_NOT_CONFIRMED`, but no input
or precedence reaches it. Define the field as boolean and map literal `false`
to `COMPOSITION_NOT_CONFIRMED`, or remove that reason from the public matrix and
RED obligations.

### AOOU-G1-06 — the production parser is not pinned at Gate 1

The safety policy describes required semantic rejection classes, but delegates
the parser/library and exact version to future RED fixture-lock evidence. This
leaves the production dependency and its parsing/action-inspection capability
open after executable-spec review. Gate 2 must not select a parser and thereby
change the production acceptance surface after owner approval.

Name the production parser/library, exact pinned version/lock artifact, and
the adapter outcome contract in the Gate 1 batch. Include an independently
validated positive fixture and parser-specific adversarial fixtures. A parser
change after approval must return through Gate 1/2 as already stated.

## Major findings

### AOOU-G1-07 — audit failure semantics are not closed for no-domain-result paths

The spec requires a security attempt audit for unauthorized, rejected and
conflict outcomes, and says an audit/log failure “до DB commit” becomes
`FAILED/PERSISTENCE_FAILURE`. Rejections and conflicts do not otherwise have an
accepted DB transaction, so it is unclear whether their audit is transactional,
whether a failed attempt-audit replaces the intended rejection/conflict result,
and whether invalid-command failures before authenticated admission are
audited. State the exact audit owner, transaction boundary, allowed audit delta,
and returned result for audit persistence failure in every status class.

### AOOU-G1-08 — orphan reconciliation is required but has no public ownership seam

The contract makes bounded orphan reconciliation/reuse part of GREEN and RED
sensitivity, but does not define who invokes it, its application/maintenance
seam, exact batch/horizon inputs and result, or concurrency contract with a
same-request retry that may be reusing the same content-addressed blob. This
would force tests or implementation to invent a second state-changing seam.
Define it explicitly as a separately authorized maintenance seam or remove it
from this command slice and retain only safe private-orphan behavior here.

## Confirmed sound elements

- The owner direction is accurately represented: one PDF, optional template,
  separate document/upload dates, explicit opening, append-only correction and
  no manual registration number in target pilot behavior.
- The state-changing business seam is singular and process authorization is
  fail-closed on active user/role plus exact upload/correct capability; display
  name and adjacent capabilities do not authorize.
- Exact 20 MiB received-byte ceiling, UTC/Moscow clock split, private storage,
  no HTTP/read/download, no composition application and no opening mutation are
  appropriately scoped.
- The literal positive fixture independently decodes to exactly `327` bytes and
  SHA-256
  `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
  No production service was used to derive those values. No installed `qpdf`
  or `pdfinfo` executable was available in the review environment, reinforcing
  the need to pin and validate the actual production parser before approval.
- HTTP upload/read/download, sequential composition applicability and opening
  are correctly named as separate future slices rather than claimed GREEN.

## Task truth and decision

At review time `openspec list --json` reports this change as `1/14`; only task
1.1 is complete. Tasks 1.2–1.5 are correctly still open. In particular, no
owner exact-hash approval exists for the reviewed executable spec/OpenSpec
batch, and Gate 2 is not authorized.

After AOOU-G1-01 through AOOU-G1-08 are corrected coherently, obtain a fresh
independent Gate 1 rereview of new exact hashes. Only an explicit `APPROVED`
record may then be presented to the owner for exact-hash approval.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0 before this review record was added)

$ base64 -d <literal fixture> | wc -c
327

$ base64 -d <literal fixture> | sha256sum
4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
```

Structural validation and fixture literals are GREEN. Gate 1 verdict remains
**CHANGES_REQUIRED**.
