# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 1/Gate 3 spec delta v11

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Exact specification correction: `6e7bc5e542f6f5af9006bdfc98ea4f2eab4653a1`
- Prompting Gate 5 finding: `9fb8f4dd3dfbd3a09885a61360e71ebc4644bedf`
- Canonical contract: `specs/YII2-COMPLETION-FORM-RECOVERY-001.md` (`git hash-object`: `82d1510e84faf1bfa2bad4ad52d3d98042bed194`)
- OpenSpec delta: `openspec/changes/recover-yii-completion-form-errors/specs/runtime/completion-form-recovery/spec.md` (`git hash-object`: `5377d31a7783ac5d5c2644c37c9c5476b0f226f7`)
- Verdict: **CHANGES_REQUESTED**

## Contract coherence

The correction resolves the Gate 5 ambiguity without weakening the owner's original
requirement. Recognized validation/domain failures still retain their original
non-success status, remain visible and accessible on the same object card, create no
facts, and cannot be portrayed as success. Submitted values remain in the exact form
when the authoritative refreshed state still permits that command.

When refreshed state removes the command—case no longer working, threshold or PTO
prerequisite absent, or document already recorded—the contract now explicitly
requires a section-level documentary-closure alert and prohibits recreating an
unavailable form merely to echo input. Capability/activity loss and expired session
remain separate fail-closed access/session results with no protected data. This is
consistent with the governing requirement that retained browser input must neither
expose unavailable data nor enable a forbidden command.

The canonical Russian contract and the OpenSpec SHALL wording express the same
conditional-retention rule. A1 remains the field-validation contract for available
forms; A3 still governs browser handling of returned `422/409` HTML, focusing the
available field or error target after card replacement. Neither requires fabricating
a command absent from the refreshed card.

## Test traceability finding

Most of the approved matrix distinguishes the two branches:

- Available-form failures cover all four form shapes, retained values, accessible
  field/form errors, focus, disclosure, sibling isolation, and corrected retry.
- `FACT_NOT_FOUND` keeps the correction command available and has browser-level
  retained-value/general-focus coverage.
- `CHECKLIST_INCOMPLETE`, `PTO_REQUIRED`, and `CASE_NOT_WORKING` cover original `409`,
  visible explanation, same-card HTML where applicable, and no facts without requiring
  an unavailable form.
- Lost capability and expired session remain non-field access outcomes, disclose no
  protected document data, and create no facts; malformed/CSRF/unknown/404 paths stay
  outside retained-card recovery.

However, the current `FACT_ALREADY_RECORDED` test contradicts the corrected rule.
After a declaration already exists, `yii2_completion_form_recovery_001_test.php`
submits `record_declaration` again and explicitly requires the unavailable
`record_declaration` form, retained `Д-CONFLICT` value, and a form-local general-focus
target. The new canonical text explicitly lists “document already recorded” as a
refreshed state that makes the command unavailable and says the form must not be
recreated solely to retain input. Thus the normative contract and executable Gate 3
oracle prescribe opposite behavior for the same named conflict.

## Required change before reapproval

Update the `FACT_ALREADY_RECORDED` acceptance case to require its original `409`,
same-card documentary-section alert, no facts/no success, and absence of the now
unavailable record form and submitted marker. Preserve `FACT_NOT_FOUND` as the
available-command form-retention/browser-focus case. Capture the corresponding
source-bound intended RED/GREEN disposition as required by the active lifecycle.

Also reconcile `openspec/changes/recover-yii-completion-form-errors/design.md`, which
still says without qualification that “domain conflicts map to the submitted form”
and that the completion partial accepts state keyed by the submitted action. State
there the same three-way rule as the normative delta: available commands retain a
form-local error; unavailable state conflicts render a completion-section alert
without recreating a form; access/session loss remains a separate fail-closed result.

The exact spec commit itself changes only the two specification files and passes
`git diff --check`, but Gate 1/Gate 3 cannot approve while its executable oracle is
in conflict.

Production code, the Gate 5 rereview, and exact-source CI remain unapproved/UNKNOWN.
