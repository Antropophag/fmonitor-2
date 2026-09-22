# Independent Gate 3 test review — YII2-OBJECT-CARD-STAGE-ACTIONS-001

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate3_object_card`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, or evidence
- Exact candidate source: `9d540c66c4394bf5461df5adc3ea7bc0a336bb14eac073640b9b2ea6d51d27dc`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T000912Z-4056f7f776/package.json`
- Verdict: **CHANGES_REQUESTED**

The prepared reviewer package, required context, normative contract, OpenSpec
artifacts, selected test sources, verification plan, and supplied RED evidence
were reviewed. This review changes only this review record.

## Findings

### G3-1 — High: the stage/action matrix is not exercised through the public seam

`tests/Yii2/yii2_object_card_stage_actions_001_test.php:76-80` reads the PHP view
as text and checks for seven literals plus the textual order of two expressions.
It never creates and renders the persisted `Монтажные работы`, `Требуется
изменение`, documentary-without-PTO, documentary-with-PTO-without-declaration,
or completed states. Those assertions can pass when a branch is dead, selects the
wrong action, emits the wrong href/form, exposes more than one primary block, or
ignores the actor's capability. Consequently acceptance E-G and the central
priority rule in section 4 are not protected against implementation defects.

Replace the source-text oracle with public `GET|HEAD /pilot/objects/{id}` cases
backed by the relevant persisted facts. For every stage, assert the exact heading,
command/href or form, one `.fm2-next-action`, the forbidden competing commands,
and the corresponding capability-denied informative state. In particular, prove
that `Требуется изменение` suppresses ordinary checklist continuation and that
completed work exposes no continuation or completion mutation as its primary
action.

### G3-2 — High: the no-selection and capability-denied branches are absent

The test creates a selection at line 14 before its first card request and never
renders a card without either a selection or an applied order. It therefore does
not test acceptance H: exact `Требуется распоряжение` presentation, authorized
`Выбрать состав`, restricted absence of the selection command/URL, and the
single informative action block. The only denied action exercised is upload
(lines 38-48); denied opening, checklist read, PTO record, declaration record,
and selection are not exercised. This leaves the contract's rule that every
unavailable command degrades to readable state unverified.

Add the no-selection authorized and restricted public-card cases, and add a
restricted-role counterpart for every command-bearing row of the matrix.

### G3-3 — Medium: the opened-with-pending case does not prove a coherent response

Lines 68-74 establish that both installer names and the pending upload URL occur,
but do not assert the current stage/readiness label, the retained checklist
action, exactly one primary block, absence of a false pending-original document,
or absence of read-side writes. A page that correctly lists both crews while
allowing the pending order to replace the applied order as the primary process
basis can pass. That is the principal regression described by acceptance D and
section 5's same-response consistency requirement.

Extend this case to assert the applied work status and checklist basis, separate
pending-only upload affordance, document truth, forbidden opening/incorrect
primary actions, exactly one action block, and an unchanged facts snapshot across
the read.

### G3-4 — Medium: supplied RED evidence establishes only the first assertion path

The sole evidence record is `INTENDED_RED` because the first pending-card copy is
missing. Since the test stops there, it does not demonstrate fixture reachability
for the accepted-original, opening, later-pending, or any proposed stage-matrix
paths. There is no separate `FIXTURE_REACHABLE` evidence. A monolithic early RED
therefore leaves most of the acceptance oracle unexecuted before implementation.

After adding the missing executable cases, supply bounded exact-source RED and
independent fixture-reachability evidence (or split the cases so each intended
RED is observed) for all material branches. The evidence must remain bound to the
candidate source and isolated task-owned database profile.

## Reviewed digests

```text
c5c9bf0e91b63b149d27d1f67c6745e8821fd5355248980e99c19599087ed4b7  specs/YII2-OBJECT-CARD-STAGE-ACTIONS-001.md
8c02d258e8d0aeb2369aca82f78013b02ede8522d5302a52fb1e2d99c77e9ff8  tests/Yii2/yii2_object_card_stage_actions_001_test.php
ab7aff7d704162d46d11140336b4e1f9ac31fb61766eab1546f2fe9ba5fbf6de  openspec/changes/align-object-card-stage-actions/proposal.md
ac0619bdc53be837e8252806b4c09f54b251fb90927b8a96cb5d884ee1efdf9e  openspec/changes/align-object-card-stage-actions/design.md
9d684c01daac6aea07b62e5aa002dcb591bf6c57e21cd4f96f8d8d4e59d0a124  openspec/changes/align-object-card-stage-actions/specs/ui/object-card-stage-actions/spec.md
bb42b69d0d5aa0c325b2223d68bf2f418594de8de76a92efb938b5fb681f8b03  openspec/changes/align-object-card-stage-actions/tasks.md
bab0720141da11b466d171a0ceda8f30a4f759a23a659bf2c286aa7053dcb960  openspec/changes/align-object-card-stage-actions/verification-input.json
e569425560838b01630e26d1c419c37b19490a7bdd20dd025610a4ff5072577a  prepared package.json
a9514cdd5ed35e3538005e9c53cec44a7b9dcbe610979ee52e261442934cafd7  required-context.json
aa12ce5948c59a29818221b869fb944faada9de308d7ce8d011e73e89cfa0c7a  task-context-manifest.json
fc849ed9d340029602bb54e42255000aef0f60881b95a95fac6170320b8d2b17  verification-plan.json
```

`reviews/tests/YII2-OBJECT-CARD-STAGE-ACTIONS-001.md` is review metadata and is
not self-hashed.

## Verdict

**CHANGES_REQUESTED.** Gate 4 is not authorized for exact source
`9d540c66c4394bf5461df5adc3ea7bc0a336bb14eac073640b9b2ea6d51d27dc`.
Address G3-1 through G3-4, refresh exact-source evidence and the reviewer package,
and obtain a fresh independent Gate 3 review before production implementation.

---

# Re-review 1 — executable matrix correction, exact source `70ea65d2`

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate3_object_card`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, corrections, or evidence
- Exact candidate source: `70ea65d256956053f773bc214b21245cbf651f844c3f398c7ac0c6ed4e42a0bc`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T001624Z-c35ee36709/package.json`
- Verdict: **CHANGES_REQUESTED**

The corrected package, all required context, both acceptance tests, updated
acceptance mapping, verification plan, and both exact-source RED records were
reviewed. The prior findings are dispositioned below; this re-review changes only
the append-only review record.

## Prior-finding disposition and complete current findings

### G3-1 — Partially fixed; High blocker remains

`tests/Yii2/yii2_object_card_stage_matrix_001_test.php` replaces the former
source-text oracle with real persisted facts and public card requests for working,
change-needed, missing PTO, missing declaration, and completed stages. That closes
the principal static-oracle defect for authorized actors.

However, the required restricted counterpart is still absent for the working
checklist branch. The test proves denied PTO and declaration at lines 27-34, but
never renders `Монтажные работы` as an actor without checklist read and therefore
does not prove the informative state or absence of the checklist command/URL.
The accepted-original/opening branch likewise has no restricted actor case in
either acceptance test. Complete the executable matrix with those public-card
denial cases and assert exactly one informative `.fm2-next-action` plus absence of
the forbidden command and URL.

### G3-2 — Partially fixed; High blocker remains

The authorized and restricted no-selection scenarios at
`tests/Yii2/yii2_object_card_stage_actions_001_test.php:14-21` now protect the
selection command branch. Upload denial and documentary-command denial are also
covered.

The correction does not add the previously requested denied opening and denied
checklist-read scenarios. As a result, the contract's rule that every unavailable
command-bearing matrix row degrades to a readable state remains incomplete.
Add those two capability-denied cases, including exact absence of their command
URLs/forms. The restricted no-selection response should also assert exactly one
informative primary block, matching acceptance H rather than only checking its
copy and missing URL.

### G3-3 — Unresolved; Medium blocker

The opened-with-later-pending block remains materially unchanged at
`tests/Yii2/yii2_object_card_stage_actions_001_test.php:76-82`. It still checks
the two crews and upload targets only. It does not assert `Монтажные работы`, the
retained checklist primary action, exactly one action block, absence of a false
pending-original document/opening action, or an unchanged facts snapshot around
the GET. The core applied-versus-pending coherence regression can therefore still
pass with an incorrect primary process basis.

Add all of those observable assertions to the public response and compare facts
before and after the read.

### G3-4 — Unresolved; Medium blocker

Splitting the suite into two tests improves failure localization, but both supplied
records are still ordinary `INTENDED_RED` records with no reachability boundary or
probe kind. The first test fails on pending waiting copy, before accepted-original,
opening, and opened-with-pending assertions execute. The matrix test fails at the
change-needed branch, before missing-PTO, missing-declaration, restricted
documentary, completed, and completed-HEAD assertions execute. Thus most corrected
oracle paths still have no pre-implementation execution evidence.

Provide exact-source `FIXTURE_REACHABLE` probes that deliberately pass through all
material setup paths without depending on missing presentation, or split/order the
tests and capture bounded intended RED evidence so every material branch is shown
reachable. Evidence metadata must identify the reachability boundary/probe and
remain bound to the isolated integration profile.

## Acceptance coverage summary

- A-B: executable pending/read-only/upload-right coverage exists, but the sole RED
  stops at the first pending copy assertion.
- C: authorized accepted-original/opening setup exists; restricted opening remains
  absent and this path is not reached by evidence.
- D: both crews and pending order identity are asserted; action/document/read-only
  coherence remains absent.
- E: authorized working and change-needed cases exist; restricted checklist is
  absent and evidence stops at change-needed.
- F-G: authorized and documentary-restricted cases are written, but no supplied
  evidence reaches them.
- H: authorized/restricted no-selection exists; restricted single-block cardinality
  is not asserted.
- I: planner-selected adjacent regression obligations remain declared; Gate 4 has
  not begun and this review does not treat them as GREEN.

## Re-reviewed digests

```text
c5c9bf0e91b63b149d27d1f67c6745e8821fd5355248980e99c19599087ed4b7  specs/YII2-OBJECT-CARD-STAGE-ACTIONS-001.md
96259cd61df9744a9b7e78ca3ab18aee02e7f701288801741c8e4e6dc171100c  tests/Yii2/yii2_object_card_stage_actions_001_test.php
3c40e24c2cb8ca3b9317dfe835226fa6c735bfc59251370face5179edf08c9aa  tests/Yii2/yii2_object_card_stage_matrix_001_test.php
ab7aff7d704162d46d11140336b4e1f9ac31fb61766eab1546f2fe9ba5fbf6de  openspec/changes/align-object-card-stage-actions/proposal.md
ac0619bdc53be837e8252806b4c09f54b251fb90927b8a96cb5d884ee1efdf9e  openspec/changes/align-object-card-stage-actions/design.md
9d684c01daac6aea07b62e5aa002dcb591bf6c57e21cd4f96f8d8d4e59d0a124  openspec/changes/align-object-card-stage-actions/specs/ui/object-card-stage-actions/spec.md
bb42b69d0d5aa0c325b2223d68bf2f418594de8de76a92efb938b5fb681f8b03  openspec/changes/align-object-card-stage-actions/tasks.md
867bc4be50d63319da947e0c566462c07c56bab0bc69b9b8c65a5a9df05bdecc  openspec/changes/align-object-card-stage-actions/verification-input.json
9e1922f3702d72d893ac9045b734db0b2a73b0c2518965934a1abe08104f8d7a  tools/verification/suites.tsv
4ab973b88bfd099a26a09823ef8f98a60a3f2e3753ad02e9f36675a12a10baaf  prepared package.json
a9514cdd5ed35e3538005e9c53cec44a7b9dcbe610979ee52e261442934cafd7  required-context.json
139d0d06d8c84371eda5d2c8a92343f6fbdf1e6c418bc4ca3e5e93bd7db61643  task-context-manifest.json
bec6796938a790ed1ce9227d31b3a24ae44276e5024b8b647b4a60f45e6a9ad7  verification-plan.json
```

## Re-review verdict

**CHANGES_REQUESTED.** Gate 4 remains unauthorized for exact source
`70ea65d256956053f773bc214b21245cbf651f844c3f398c7ac0c6ed4e42a0bc`.
Close the remaining portions of G3-1 through G3-4, refresh exact-source evidence
and the reviewer package, and obtain another independent Gate 3 re-review.

---

# Re-review 2 — aggregate branch execution, exact source `d7219111`

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate3_object_card`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, corrections, or evidence
- Exact candidate source: `d7219111ef03b8363bd2d5897df55036e468ffaec18811c26d373655a727ee39`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T002130Z-08bd308409/package.json`
- Verdict: **CHANGES_REQUESTED**

The third prepared package, full required context, both corrected tests, acceptance
mapping, verification plan, and exact-source aggregate RED evidence were reviewed.
This section is the complete findings list for this source and changes only the
append-only review record.

## Prior-finding disposition

- **G3-1 substantially fixed.** The public HTTP matrix now exercises working,
  restricted working, change-needed, missing PTO, restricted PTO, missing
  declaration, restricted declaration, and completed states with single-block and
  competing-action assertions. The accepted-original branch now includes a
  restricted-opening case.
- **G3-2 substantially fixed.** Authorized and restricted selection, upload,
  opening, checklist, PTO, and declaration states are all represented.
- **G3-3 partially fixed.** Applied/pending crews, working status, checklist action,
  one primary block, applied original visibility, upload identity, and no reopening
  are now asserted. The no-write oracle remains invalid, as described below.
- **G3-4 fixed.** Both tests collect assertion failures and throw only after all
  setup and response paths execute. The exact-source evidence records explicitly
  report reaching every material branch in each test, so later paths are no longer
  hidden behind the first intended RED.

## Current findings

### G3R2-1 — High: opened-with-pending no-write assertion snapshots too late

In `tests/Yii2/yii2_object_card_stage_actions_001_test.php:84-85`, the public GET
is executed first and `$duringBefore = $fixture->facts()` is captured afterward.
Line 97 then compares that post-request snapshot to another post-request snapshot.
Any event, fact, task, or file written by the GET itself is already present in both
values and the assertion passes. This does not prove acceptance D/section 5's
read-only requirement for the exact applied-plus-pending state, and it fails to
close the explicit G3-3 correction request.

Capture the facts snapshot immediately before line 84's GET, then compare the
post-response facts to that pre-request value. Refresh the exact-source aggregate
RED evidence after this correction.

### G3R2-2 — Low: two restricted exact-negative assertions remain incomplete

The restricted no-selection case at lines 21-25 checks state and command/URL
absence but does not assert the acceptance-H requirement of exactly one informative
`.fm2-next-action`. The restricted checklist case in
`tests/Yii2/yii2_object_card_stage_matrix_001_test.php:24-28` excludes only the
visible text `Перейти к чек-листу`; it does not exclude the exact checklist href,
so a relabelled inaccessible link can pass. Add the missing single-block assertion
and exact `/pilot/objects/4512/checklist` URL negative assertion. These are small
changes but are part of the explicit restricted-state contract and prior requested
disposition.

## Acceptance coverage summary

- A-C and E-H now have executable public-seam state/capability coverage and are
  reached by aggregate RED evidence, subject to G3R2-2's two exact negatives.
- D has the required presentation-coherence checks, but its no-write protection is
  ineffective because of G3R2-1.
- I remains represented by the planner-selected adjacent focused obligations; no
  Gate 4 GREEN is inferred at this stage.

## Re-reviewed digests

```text
c5c9bf0e91b63b149d27d1f67c6745e8821fd5355248980e99c19599087ed4b7  specs/YII2-OBJECT-CARD-STAGE-ACTIONS-001.md
61467ebb24fa0cec0956be27afced05b1df042610950b5b6394fcca12c13014f  tests/Yii2/yii2_object_card_stage_actions_001_test.php
a8c68da3315086d68c7a5f54b3f19001192f7c6a7241280dd63c5887adea40df  tests/Yii2/yii2_object_card_stage_matrix_001_test.php
ab7aff7d704162d46d11140336b4e1f9ac31fb61766eab1546f2fe9ba5fbf6de  openspec/changes/align-object-card-stage-actions/proposal.md
ac0619bdc53be837e8252806b4c09f54b251fb90927b8a96cb5d884ee1efdf9e  openspec/changes/align-object-card-stage-actions/design.md
9d684c01daac6aea07b62e5aa002dcb591bf6c57e21cd4f96f8d8d4e59d0a124  openspec/changes/align-object-card-stage-actions/specs/ui/object-card-stage-actions/spec.md
bb42b69d0d5aa0c325b2223d68bf2f418594de8de76a92efb938b5fb681f8b03  openspec/changes/align-object-card-stage-actions/tasks.md
867bc4be50d63319da947e0c566462c07c56bab0bc69b9b8c65a5a9df05bdecc  openspec/changes/align-object-card-stage-actions/verification-input.json
9e1922f3702d72d893ac9045b734db0b2a73b0c2518965934a1abe08104f8d7a  tools/verification/suites.tsv
7234533ba8aebbf5f27c754cd3f1f90b22d9a78c7722e5064804016c81517bb6  prepared package.json
a9514cdd5ed35e3538005e9c53cec44a7b9dcbe610979ee52e261442934cafd7  required-context.json
813fa32730f8cb51012f17beaa0a8b3350fb144d22fa2146d314d825c2cb4634  task-context-manifest.json
ee0e5e9d058c16c708e7c377ced56d60f548e83713598bcbf6302b045f6ee5c1  verification-plan.json
```

## Re-review verdict

**CHANGES_REQUESTED.** Gate 4 remains unauthorized for exact source
`d7219111ef03b8363bd2d5897df55036e468ffaec18811c26d373655a727ee39`.
Correct G3R2-1 and G3R2-2, refresh the exact-source package/evidence, and obtain a
fresh independent Gate 3 re-review before production implementation.

---

# Re-review 3 — final test correction, exact source `613d6153`

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate3_object_card`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, corrections, or evidence
- Exact candidate source: `613d615318af027868fb46190805ef2afe36a1aea8c6675afaf92f6c8b2f064a`
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T002634Z-64f0177080/package.json`
- Verdict: **APPROVED**

The final corrected package, full required context, both acceptance tests,
acceptance mapping, verification plan, and fresh exact-source aggregate RED
records were reviewed. This section is the complete findings list for this source
and changes only the append-only review record.

## Findings and prior-finding disposition

No blocking or non-blocking findings remain.

- **G3R2-1 fixed.** In
  `tests/Yii2/yii2_object_card_stage_actions_001_test.php:85-98`, the complete
  facts snapshot is now captured before the opened-with-pending public GET and
  compared after the response. A read-side fact/event/task/file write can no
  longer hide in both operands.
- **G3R2-2 fixed.** The restricted no-selection response now asserts exactly one
  `.fm2-next-action` at line 26. The restricted working response now excludes both
  the checklist label and exact `/pilot/objects/4512/checklist` URL at
  `tests/Yii2/yii2_object_card_stage_matrix_001_test.php:24-28`.
- **G3-1 through G3-3 closed.** The two tests jointly exercise the public card for
  no selection, pending composition, accepted original, opening, opened work with
  later pending composition, change-needed, missing PTO, missing declaration, and
  completion. Authorized and restricted variants protect selection, upload,
  opening, checklist, PTO, and declaration commands. Assertions cover one primary
  block, exact state/action copy, command fields and URLs, competing-action
  absence, applied-versus-pending crew/document coherence, HEAD behavior, and
  read-only facts.
- **G3-4 closed.** Both tests aggregate assertion failures until their final
  `TestFailure`, allowing every material setup and response path to execute on the
  pre-implementation source. The fresh evidence records are bound to source
  `613d6153...`, executable source `cb07c08f...`, and the isolated integration
  environment, and explicitly confirm that all listed branches were reached.
- Acceptance I's adjacent object-card presentation, object-details/history,
  documentary, construction-control, object-card, and object-queue regressions
  remain explicit planner-selected Gate 4 obligations. This Gate 3 approval does
  not report them GREEN before implementation.

## Approved digests

```text
c5c9bf0e91b63b149d27d1f67c6745e8821fd5355248980e99c19599087ed4b7  specs/YII2-OBJECT-CARD-STAGE-ACTIONS-001.md
e67b98e0a0ec9b701443c1364d7b497b1eb0d4a3781ca77fff457c7feddbe43c  tests/Yii2/yii2_object_card_stage_actions_001_test.php
b24ec293fbdd70bd7a562a6a303e55ad2f10e7055db6a874957ba121f4f2b272  tests/Yii2/yii2_object_card_stage_matrix_001_test.php
ab7aff7d704162d46d11140336b4e1f9ac31fb61766eab1546f2fe9ba5fbf6de  openspec/changes/align-object-card-stage-actions/proposal.md
ac0619bdc53be837e8252806b4c09f54b251fb90927b8a96cb5d884ee1efdf9e  openspec/changes/align-object-card-stage-actions/design.md
9d684c01daac6aea07b62e5aa002dcb591bf6c57e21cd4f96f8d8d4e59d0a124  openspec/changes/align-object-card-stage-actions/specs/ui/object-card-stage-actions/spec.md
bb42b69d0d5aa0c325b2223d68bf2f418594de8de76a92efb938b5fb681f8b03  openspec/changes/align-object-card-stage-actions/tasks.md
867bc4be50d63319da947e0c566462c07c56bab0bc69b9b8c65a5a9df05bdecc  openspec/changes/align-object-card-stage-actions/verification-input.json
9e1922f3702d72d893ac9045b734db0b2a73b0c2518965934a1abe08104f8d7a  tools/verification/suites.tsv
4b3b44d812e9b278a2f6796204cea81cd7949d2b2aae4c785e2a65c871136b95  prepared package.json
a9514cdd5ed35e3538005e9c53cec44a7b9dcbe610979ee52e261442934cafd7  required-context.json
88069ef13f489fb1d3d18e453c1d363b5b9e7b8fb75ecb7489e85614c42f1629  task-context-manifest.json
d490c46b3d72fea3d882363f4e61a2445602f5edc768d249162f5ca928763037  verification-plan.json
```

## Re-review verdict

**APPROVED.** Gate 4 implementation is authorized for exact source
`613d615318af027868fb46190805ef2afe36a1aea8c6675afaf92f6c8b2f064a`.
This approval is limited to the reviewed specification/tests/evidence and does not
substitute for planner-selected focused GREEN checks, exact-source CI, or the
independent final review required after implementation.
