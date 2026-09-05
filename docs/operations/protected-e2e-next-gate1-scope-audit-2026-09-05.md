# Protected E2E next Gate 1 scope audit

- Recorded: `2026-09-05`
- Auditor: `/root/protected_e2e_gate1_scope_audit`
- Inspection HEAD: `a73dfe16178d7e62ec9f5074fe768c2fae637942`
- Disposition: **READ-ONLY RECOMMENDATION; NO OWNER APPROVAL CLAIMED**
- Execution: no test, shared-DB command, external-data access or push was run.

This append-only audit identifies the smallest coherent owner decision that can
remove the current protected list/admission blocker without making the legacy
manual-registration journey the target pilot golden. It does not authorize a
protected test, production, specification or review edit.

## Current exact conflict

Actor 18 is admitted with `200` and the configured production renderer emits
object `4512` as the one canonical link in a semantic `ul/li`. The protected
test nevertheless requires that link under `tbody/tr` at its current line 153,
then requires six table headings and repeats the table-scoped link assertion at
current lines 156–157. `PILOT-UI-SHELL-001` section 5 selects a semantic list
and forbids native `table`/`.shlz-table-wrap`; `PILOT-OBJECT-LIST-001` supplies
the approved membership, facts and canonical order. Therefore changing the
renderer to satisfy the protected XPath is outside present authority.

`PILOT-E2E-FLOW-001 v0.5` is itself headed `BLOCKED TARGET E2E`; its queue
expansion and manual registration/`registered` path cannot override the
approved list/UI-shell contract. Product truth now requires accepted signed
PDF original evidence as the opening basis and excludes manual registration as
a pilot gate.

## Smallest coherent owner package

The owner may approve an isolated **admission-fixture contract amendment**
separately from a new target end-to-end journey. The package should contain one
exact-hash amendment to `PILOT-E2E-RBAC-FIXTURES-001`, with these explicit
decisions and no broader ones:

1. Its seam is only both actor-18 `GET /pilot/objects` observations in the
   protected fixture: initial admission before actor-19/authority proofs and
   the unchanged-state repeat before the card request.
2. Both observations inherit `PILOT-OBJECT-LIST-001` membership/order/facts and
   the configured `PILOT-UI-SHELL-001` semantic-list/no-table representation.
   For fixture object `4512`, the observable requirement is exactly one
   canonical `/pilot/objects/4512` link in exactly one semantic `li`; neither a
   `tbody/tr` ancestor nor six headings is required or permitted.
3. Authority is limited to replacing the two table-scoped link assertions and
   deleting/replacing the six-heading assertion with semantic-list assertions.
   Actor matrix, restricted-principal sentinels, snapshots, zero mutation,
   cleanup and RBAC-before-handler ordering remain byte-for-byte obligations.
4. The amendment explicitly says that reaching the card after these
   observations is a downstream boundary, not acceptance of the remaining
   `PILOT-E2E-FLOW-001 v0.5` assertions. It grants no authority for production
   presentation changes, combined-PDF behavior, prepare/register/open behavior,
   manual registration, or a target golden journey.
5. Because current production already conforms, the packet must make an
   explicit owner/process decision about gate treatment. A corrected test is
   expected to be GREEN immediately and cannot honestly demonstrate Gate 2 RED
   for missing production behavior. The mandatory process currently requires
   demonstrated RED and says test changes restart at Gate 2. Owner approval of
   representation alone does not waive that rule. The package must therefore
   either (a) approve a narrowly documented expectation-correction path with
   fresh independent test review and no production Gate 4 change, or (b) first
   approve an amendment to the delivery process that defines such a path.

The approval request should name the amended specification bytes and the exact
protected-test bytes it is intended to constrain. Approval of prose that merely
says “fix the E2E” is insufficient because it could be misread as approval of
the stale monolithic journey.

## What remains after isolated admission alignment

The isolated amendment removes only the list/table blocker. Without executing
the protected test, this audit does not claim which later assertion will fail
first. The following downstream authorities and failures nevertheless remain:

- The current protected file still encodes the legacy prepare → generated
  artifacts → manual registration number → `registered` → opening journey.
  Passing those assertions cannot serve as target-golden evidence under
  `PRODUCT.md`, `CONTEXT.md` and the current pilot specification.
- If only the first line-153 XPath were changed, the six-column assertion at
  current line 156 and repeated table XPath at line 157 would remain immediate
  deterministic blockers. The isolated amendment must treat all three as one
  list-representation correction.
- Historical evidence identifies the combined-PDF/appendix artifact boundary
  as a separate downstream failure. `PILOT-E2E-COMBINED-PDF-001` remains
  `DRAFT / Gate 1`; this admission amendment cannot authorize its test or
  implementation.
- A replacement target journey depends on coherent approved and delivered
  original-first slices. At inspection, `ASSIGNMENT-ORDER-ORIGINAL-HTTP-001`
  is `DRAFT / GATE 1 NOT APPROVED`; the composition-selection contract still
  names Gate 1 open items; and approved `OPEN-INSTALLATION-001` is explicitly a
  legacy `registered` predecessor awaiting
  `open-installation-from-assignment-order-original`. Those dependencies must
  be resolved before a new protected target golden can be approved.
- The eventual target E2E must cover composition selection, optional template,
  direct or post-template signed-original upload, immutable original evidence,
  and separate opening from the applicable original. It must not retain manual
  number entry or `registered` as a pilot gate. Exact routes, roles, outcomes
  and renderer details must come from their own approved contracts; this audit
  does not invent them.

Thus isolated admission approval is coherent and useful, but it only restores
the RBAC/list fixture boundary. It neither completes protected E2E nor makes the
old golden a valid target.

## Exact inspected bytes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
c97bcb3df97362a19efc9dda6ab6ac2a8224fa0d0a3a42721d7eca8b511e3cfd  specs/PILOT-E2E-FLOW-001.md
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
2b30da75a41dc894d43d85a53e8f06979775787f447eaf1b3f82b9675fbe84b9  specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md
bb7fa50459ea0c4a8e1983d5c9193be4d026d11e25f97e224fb2b8ccbd871a3f  specs/ASSIGNMENT-ORDER-ORIGINAL-HTTP-001.md
9450aae9f3f0a13a7066e10f9c28363d5dc02e408e3e9fa1a641fda7a1c8edbb  specs/OPEN-INSTALLATION-001.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
9c16b1b72841d6de54c0ea55f1c58961dfa45fe313fee6aa6476500d4ab041ae  docs/operations/protected-pilot-e2e-actor18-zero-link-diagnosis-correction-2026-09-05.md
```

## Correction — Gate 2 methodology remains unresolved

- Recorded: `2026-09-05`
- Disposition: **SUPERSEDES ITEM 5 AND THE RELATED PROCESS-PATH CONCLUSION ABOVE**

Item 5 above overstates the result of this bounded audit. This audit did not
establish that owner approval must waive the mandatory delivery process or that
`docs/development-process.md` must be amended. The owner package must not ask
the owner to lower review quality or waive a gate on the strength of this
record.

The correct scoped finding is:

1. Gate 1 must first approve the exact isolated admission-fixture correctness
   contract and the limited protected-verifier boundary described in items
   1–4 above.
2. How to demonstrate the intended RED and advance the test-only correction
   through the existing gates is a subsequent methodology question. It was not
   assessed or resolved by this read-only audit.
3. Existing supporting-fixture lineage demonstrates that a fixture/verifier
   correctness contract can use an intended fixture RED, an unapplied exact
   patch reviewed independently at Gate 3, and test-only GREEN without a
   production change or waived gate. Relevant examples are
   `PILOT-SESSION-EMPTY-ENV-FIXTURE-001` and
   `CANONICAL-V12-CONSUMER-FIXTURES-001` at the inspected hashes below.
4. This case cannot blindly inherit that method: those slices correct fixture
   inputs/prerequisites while preserving expectations, whereas the protected
   E2E conflict requires changing stale representation expectations. A future
   public-verifier correctness contract and independently demonstrated intended
   RED may be viable after owner Gate 1, but that route requires its own exact
   specification and independent assessment.
5. Until that assessment, the scope recommendation remains limited to owner
   approval of the semantic-list admission contract. It makes no process
   exception, supplies no Gate 2 evidence, authorizes no protected-test patch,
   and claims no approval.

Additional exact inspected bytes for this correction:

```text
d7c9a4acd71aaadd25c21bed1bc836b34ee8e61c64c6318877e44301711760cf  specs/PILOT-SESSION-EMPTY-ENV-FIXTURE-001.md
2f716c5cc308b4897c49a1e687a1b77c283fdac8fba624bd5c3e763990a9e997  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
```
