# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 5 final review

- Reviewer: `/root/completion_final_review` (independent; did not author the specification, tests, or production implementation)
- Base: `bced877aec8a8802e97037749ca4251d3098df1a`
- Reviewed HEAD: `7d54a7064860c7d34c84358bddf21a99d55b49e2`
- Candidate source: `4edaf10665aac487e352ebffe52f60e69d73bfc2fde99e27be8d4beaa29a442b`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T185328Z-b8a5a75686/package.json`
- Contract: `specs/YII2-COMPLETION-FORM-RECOVERY-001.md`
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **High — several recognized `409` rejections are not rendered inside the
   submitted form and do not preserve that form's values for no-JavaScript users.**
   Contract A2 requires each named domain rejection to appear in the submitted
   form, while A1 requires the submitted form values to remain correctable. The
   OpenSpec delta states the same requirement without an exception for a newly
   unavailable command. `app/YiiRuntime/Views/completion.php:32-38` decides whether
   the submitted form is currently visible from refreshed domain state, and
   `app/YiiRuntime/Views/completion.php:55` falls back to a card-level alert when it
   is not. Consequently:

   - `CHECKLIST_INCOMPLETE` for `record_pto` renders no `record_pto` form because
     `$at85` is false;
   - `PTO_REQUIRED` for `record_declaration` renders the current `record_pto` form,
     not the submitted declaration form;
   - `CASE_NOT_WORKING` renders no submitted form because the card is no longer at
     the completion stage;
   - a `FACT_ALREADY_RECORDED` race for `record_pto` renders current document state,
     not the submitted PTO form.

   In those responses the retained values carried by
   `CompletionController::cardError()` are not emitted at all. The user therefore
   cannot inspect or correct the submitted data in the returned `409` HTML without
   JavaScript, contrary to the bounded feature's public contract. The Gate 3 record
   explicitly accepted omitting unavailable forms, but that interpretation is not
   present in the normative contract and cannot override it.

   Correct by rendering a bounded rejected-state version of the submitted form for
   every named recognized conflict (without making a now-forbidden action
   executable), or amend and independently reapprove the normative behavior before
   implementation. Add table-driven assertions that every named `409` contains the
   exact submitted form, its retained fields only there, and its form-local focusable
   error; the current test checks only status/message/no-fact for most of these
   branches. Because this changes the approved acceptance oracle, recompute the plan
   and return through Gate 2/Gate 3 as required by the CRITICAL lane.

## Review assessment

The rest of the inspected implementation is bounded to completion presentation.
It preserves the existing application write seam (`MariaDbInstallationCompletion`
`record`/`correct`), performs no controller or JavaScript DML, keeps roots immutable
and corrections append-only, preserves access/session and malformed-transport
fail-closed responses, and does not add retry or idempotency behavior. The browser
path guards one form in flight, leaves the original DOM intact for network/non-HTML
unknown outcomes, unlocks without automatic retry, consumes only `409/422` HTML,
and navigates only after a followed successful redirect. The full diff has no
candidate production overlap with #238's `FeedbackApplication`, feedback view, or
configuration paths after the asset refactor.

All immutable Gate 3 records were inspected, including the approved v9/v10 deltas.
The prepared package binds the reviewed HEAD and candidate source. Its exact-source
focused records are GREEN for completion HTTP, completion browser, verification
governance, runtime storage, and architecture guard. The delivery record's adjacent
documentary HTTP/browser, object-details HTTP/browser, and current-stage records were
also inspected; they are GREEN supporting evidence from their recorded earlier
source, not substitutes for candidate-source CI. `git diff --check` is clean. No
local full suite was run. Required exact-source GitHub CI, publication, merge, and
deployment remain `UNKNOWN` and are not treated as approval or GREEN.

The finding above is the complete Gate 5 findings list for the reviewed source.
