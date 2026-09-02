# Independent planning review — pilot assignment-order original

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_planning_review`  
Reviewed change: `replace-pilot-registration-with-original-upload`  
Verdict: **CHANGES_REQUIRED**

## Reviewed immutable inputs

```text
3672f8afbbd35bc0d88d928561ed1b014fc9c48711eba07bcc6af2b23866ecbb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
4f2e8ddfde941a9fee47ab2578f3d4a832e11b8cbea34b0b254febce286975a8  openspec/changes/replace-pilot-registration-with-original-upload/design.md
dbf6c07ff94f502f2fd8433bd92f2ee498ee2950315163da08777bf7aa900c98  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
71d0a30cbe80af7cda0279e1ebe50f1f9b579a8bc848d5c7d6114b0fe15d0cfd  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Primary owner evidence reviewed: `docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md`. Product/context and delivery contracts reviewed: `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`, `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, and `docs/installation-process-interface.md`.

## Findings

### 1. BLOCKING — superseded pilot truth is scheduled after RED/GREEN

The proposal and design correctly identify the current manual-number and `prepared → registered` opening contract as superseded. The design explicitly requires one coherent documentation amendment **before RED**. Task 4.3, however, schedules amendments to `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, `docs/installation-process-interface.md`, dependent active changes and tests only after application, persistence and HTTP GREEN tasks.

This violates Gate 1 and leaves a RED author with contradictory normative inputs. Move the complete active-contract inventory and coherent supersession ahead of executable-spec approval and RED. Historical review/evidence records must remain unchanged. The inventory must cover at least the three named documents, active E2E/RBAC/combined-PDF contracts, application specifications and tests that call `confirmOrderRegistration`/`confirmRegistration`, require `registered`, or render the manual-number action.

### 2. BLOCKING — five `NEEDS_GRILL` items are incorrectly delegated to the owner

No further owner interrogation is justified for these five items:

1. **Actor/capability mapping** is an engineering RBAC mapping constrained by the approved actors and the already approved rule that display labels never authorize. Discovery must map the existing technical role IDs/grants to a new explicit upload capability, fail closed, and preserve the technical code for «Руководитель ФКР». Only a genuinely new user-visible authorization exception would return to the owner.
2. **PDF validation policy** is a security/engineering acceptance policy. The owner already chose one actual PDF, no OCR/signature verification, no image bundle. Engineering must define deterministic treatment of encrypted/password-protected, truncated, structurally invalid and empty PDFs and record the parser/library boundary. Malware scanning remains a separately named non-goal unless infrastructure policy requires it.
3. **20 MB bytes** was explicitly delegated to engineering by the owner (“Ограничение размера установи сам”) and subsequently accepted as 20 MB. Choose and document an exact inclusive byte ceiling; do not ask the owner to distinguish decimal MB from MiB.
4. **Same bytes with changed semantic inputs** follows the approved model: byte identity alone is idempotent only when the complete semantic operation identity is the same. A changed date or composition cannot silently replay the old result; it must enter the applicable correction/sequential-order rule and require the already approved reason where it is a correction. Exact request/idempotency-key mechanics are engineering design.
5. **Applicable-version selection** must be designed deterministically from approved product rules: document date, append-only correction lineage, prospective sequential orders, and immutable opening/history snapshots. Upload time must not decide business applicability. Tie/conflict handling and database constraints are engineering decisions; only a newly proposed user-visible precedence rule would require owner input.

Replace `NEEDS_GRILL` and task 1.1 with explicit engineering decisions and verification obligations. The existing owner record already authorizes planning; exact-hash approval remains required after the artifacts are amended.

### 3. BLOCKING — correction and sequential-order semantics are not yet exact enough for Gate 1

The delta distinguishes correction from a sequential order, but does not give the public seam an unambiguous discriminator or exact outcomes for important collisions:

- same PDF and same date/composition with a different correction reason;
- same PDF with changed date;
- same PDF/date with changed composition;
- correction of a non-current/superseded original;
- two sequential orders with the same document date;
- a backdated sequential order whose date overlaps already effective history;
- which accepted original permits opening when document dates tie;
- whether a post-opening correction can change only file/date evidence, and how opening continues to reference its immutable original/version snapshot.

Specify command mode/target identity, expected result DTO/status/reason codes, exact applicability ordering and no-mutation outcomes. “Prospective” cannot be implemented or independently tested until ties and overlap are resolved without using upload time.

### 4. BLOCKING — authorization and opening actors are under-specified

Upload actors are product-traceable, but the artifacts do not state the exact permission string/grant source nor distinguish authorization for initial upload, correction and sequential order. They also say `openInstallation` remains separate without restating or citing the exact currently approved opening actor/capability. The executable plan must identify both public seams, their actor/capability checks and fail-closed observable results. Do not infer either from the displayed role name.

### 5. MAJOR — observable API and failure contract is incomplete

The intended seam is named only provisionally and its values/results are not closed. Before executable spec, define the command DTO fields and canonical identities, clock/timezone used for `server_today`, accepted/replayed/conflict/rejected result variants, stable business reason codes, file-download/read projection, and audit behavior for rejected attempts. The current blanket “without ... audit success event” does not answer whether security/business rejection audit is required and observable through the approved audit seam.

### 6. MAJOR — PDF safety and storage failure boundaries need testable decisions

“Actual PDF content” is not yet an executable oracle. Specify a deterministic parser/validation result rather than magic-byte inspection; reject encrypted/password-protected, truncated/unparseable, zero-page and page-less/empty-structure files unless a documented reason supports acceptance. State the exact inclusive byte limit and whether counting is over received bytes before any transformation. Define bounded streaming behavior, staging cleanup/quarantine ownership, download authorization, safe response filename/content headers, hash verification, and how transaction/storage failures yield no applicable fact or public orphan.

### 7. MAJOR — source-of-truth documents are internally contradictory

`PRODUCT.md` contains the new upload workflow, but still says actual start cannot precede an immutable order date established when the document is formed. `CONTEXT.md` still defines opening, prepared/registered orders and assignments in terms of registration number/status. The pilot spec and data model remain wholly number/`registered` based. These contradictions are acknowledged but not enumerated at requirement granularity. The pre-RED amendment must make the original’s confirmed document date authoritative, preserve generated-template date only as a proposal, and clearly mark legacy registration facts read-only historical compatibility rather than a pilot gate.

### 8. MAJOR — scope is too broad for one RED/GREEN slice

The task plan asks one Gate 2 cycle to cover initial upload, two entry paths, authorization, validation, dates, replay, correction, sequential versions, concurrency, storage/DB atomicity, opening and HTTP/UI supersession. This is not the “smallest test proving one acceptance statement” required by `docs/development-process.md`. Split delivery into ordered vertical executable slices, each independently approved/reviewed, while retaining one domain mutation owner. A safe sequence is: initial accepted upload + opening gate; rejection/PDF boundary; replay/concurrency; correction; sequential order; HTTP/UI/read/download supersession.

## What is sound

- The owner decisions are accurately captured for one PDF, optional template, two upload roles, explicit composition confirmation, distinct document/upload dates, separate opening, append-only correction, byte-identical replay intent and prospective sequential orders.
- Template and signed original are correctly modeled as separate evidence identities.
- The plan preserves old bytes/facts and rejects controller/`rapid-pilot` ownership.
- The storage staging approach recognizes cross-resource partial-success risk.
- `openspec validate replace-pilot-registration-with-original-upload --strict` passes structurally.

These strengths do not remove the behavioral ambiguities above.

## Required rereview evidence

Fresh review should receive amended exact hashes showing:

1. all five former grill items resolved as explicit engineering/security decisions or, only where unavoidable, one narrowly stated owner-visible choice;
2. active manual-number/registration contracts coherently superseded before executable spec/RED;
3. exact capability grants and both public seams;
4. exact PDF/byte/storage/download policy;
5. exact semantic replay, correction, applicability, tie and concurrency outcomes;
6. vertical Gate 1→5 slice ordering rather than one omnibus RED;
7. strict validation and `git diff --check` output.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0 at review time)
```

Structural validation is GREEN; planning verdict remains **CHANGES_REQUIRED**.
