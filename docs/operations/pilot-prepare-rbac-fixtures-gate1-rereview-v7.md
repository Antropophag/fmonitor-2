# Independent Gate 1 rereview v7 — prepare upload-first amendment

Date: 2026-09-04  
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1`  
Reviewed commit: `19588b975095bc7741a96ef04f4805f795e88b67`  
Gate: Gate 1 rereview after upload-first presentation amendment  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Preserved and satisfactory boundaries

- The OpenSpec package still limits this slice to the read-only exact
  `GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare` admission
  path. It does not silently add file upload, multipart parsing, CSRF handling,
  an application command, original persistence, composition application or
  opening.
- Local `assignment_order.prepare` and the separately owned process capability
  remain distinct fail-closed gates. Method, authorization, redaction,
  deterministic HEAD and zero-mutation guarantees are not weakened by the
  amendment.
- The proposed screen direction removes the obsolete manual registration-number
  model and is directionally consistent with the owner-approved signed-original
  workflow: an optional template, a signed PDF original, a separate document
  date/upload time and a separate opening action.
- PDF bytes, date confirmation, composition confirmation and mutation remain
  assigned to the separately gated original-upload HTTP/command work. No
  implementation-derived PDF or upload oracle has been introduced here.

Strict OpenSpec validation and whitespace/diff validation pass, but they do not
resolve the normative contradictions below.

## Blocking findings

### 1. The controlling RBAC executable spec still inherits v0.1

`specs/PILOT-PREPARE-RBAC-FIXTURES-001.md` section 1 still says that all
form/eligibility/UI clauses are inherited from
`PILOT-PREPARE-FORM-001 v0.1`. The amendment changes the form to v0.2 and the
OpenSpec proposal/delta now require v0.2, but the controlling fixture contract
was not amended. A RED author therefore has two incompatible normative sources:
the unchanged executable fixture spec requires v0.1 while its OpenSpec package
requires upload-first v0.2.

Required correction: amend the controlling executable fixture spec to cite the
exact v0.2 contract and reset its Gate 1 status/hash. Keep previous v3 approval,
RED and Gate 3 records historical; they cannot approve tests for the new
representation.

### 2. The additive override does not unambiguously supersede every conflict

`PILOT-PREPARE-FORM-001 v0.2` says it replaces only conflicting presentation
assertions in sections 4–10. Its section 1 acceptance tracer remains normative
and still requires independent installer checkboxes, an engineer radio-group
and an unconfirmed legacy prefill. Sections 4–10 also retain the old exact link,
headings, controls, empty states and executable example; readers must classify
each old clause as “presentation” before deciding whether it applies.

`PILOT-UI-SHELL-001` marks only section 7 as presentation-superseded. Conflicting
requirements remain outside it: section 6 requires card link text
`Сформировать распоряжение`; section 8 independently fixes the same link,
checkbox/radio candidates and confirmation checkbox; its browser oracle also
requires native checkbox/radio geometry. The new successor reference and
heading sentence do not explicitly supersede those clauses.

Required correction: produce coherent normative text, preferably by editing the
affected sections/examples in place or by an explicit exhaustive clause/ID
replacement table covering both specs. The approved hash must leave exactly one
expected card label, heading/breadcrumb, installer representation, engineer
representation, empty state, return action and browser oracle.

### 3. Engineer ownership is a product-semantic conflict, not presentation only

The owner-approved original-PDF truth says that before upload the user selects
one engineer and at upload confirms that the PDF composition matches the
selected composition. The amendment instead makes the engineer read-only “from
the object card” and labels it `справочно`, while removing both selection and
confirmation controls. Existing product truth treats legacy
`responsstroicontrol` only as a prefill requiring confirmation; it is not a
confirmed composition fact.

The amendment does not identify a separately approved public seam that turns
the card engineer into the selected immutable composition, nor define what an
upload command should confirm when the displayed value is only informational.
Gate 4 would have to invent whether the card value is authoritative, a legacy
hint, or an earlier confirmed selection.

Required correction: obtain an explicit owner decision for this changed actor
task, or retain selectable/confirmable engineer semantics until a separately
approved selection seam exists. Record which persisted/read model supplies the
engineer, its eligibility/staleness behavior and how it becomes the exact
composition later confirmed by upload.

### 4. The picker contract is neither constructible nor accessibility-complete

The new text places normalized installer records in an inert `<template>` and
shows a `Выбрать монтажников` button, while inherited clauses prohibit JS and
require keyboard-operable native controls. A `<template>` is not exposed as
interactive/accessibility content, and the amendment specifies no dialog/list
semantics, accessible name/state, focus movement/return, keyboard operation,
selection count/summary, no-JS behavior, or escaped data schema. It also does
not say whether the button is `type="button"`; inside the stated GET form an
unspecified `<button>` defaults to submit, conflicting with “no submit”.

Required correction: define an observable read-only interaction contract (and
the owned JS/CSP boundary if JS is now intended), including exact inert data
shape/escaping, button type, accessible picker semantics, focus/keyboard
behavior, selected-state exposure and empty-state behavior. If that interaction
belongs to the future upload composition, this GET contract must not claim that
the current button performs selection.

## Security assessment

Authorization-first reads, no upload/body parsing, no mutation and output
escaping remain required. However, the unspecified serialized picker-data
schema is a security boundary: “escaped normalized records” alone does not fix
whether bytes are HTML text, JSON, attributes or later DOM input. The revised
contract must define encoding/parsing and prohibit HTML interpretation so names,
positions and provenance cannot become markup or executable content. Existing
hostile-text and CSP expectations should be carried forward to the new picker
representation rather than left attached only to removed radio/checkbox DOM.

## Gate decision

Gate 1 is not ready for owner hash approval. Task 1.6 remains open. No new RED,
Gate 3 reuse, GREEN or implementation is authorized from commit `19588b9`.
After the controlling executable fixture spec and both UI/form contracts are
made coherent, and the engineer actor-task decision is explicitly owned, the
entire changed exact-hash batch requires a fresh independent Gate 1 rereview and
then explicit owner approval.

## Exact reviewed hashes

```text
3d693f06e21780fb0dfc42c217e61f512f97796e221c817ace4be100eab2622a  specs/PILOT-PREPARE-FORM-001.md
68bd7d66d305eaa5fa5ce901d65df736dfb06f69a9b06db7f8c041641007006c  specs/PILOT-UI-SHELL-001.md
2736c142c2c4535b6541b08764ef5cfea034434291657935b718945b67b55818  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
77ac116ebfe70f809f9f7092d5db945cf61b98f164cb40120d7c6e66866c21c0  openspec/changes/pilot-prepare-rbac-fixtures/design.md
1b8035dcdc5469704424d0c91d1589db52e35a4b139c634c6eb509410eb6bb06  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
85397a877bb34d05cf1954fd869b5aeaed306f39d847870b7f17fe417c84886a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

## Verification

```text
$ git rev-parse HEAD
19588b975095bc7741a96ef04f4805f795e88b67

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 19588b9^ 19588b9
PASS (exit 0, no output)

$ git diff --stat 19588b9^ 19588b9
6 files changed, 47 insertions(+), 5 deletions(-)
```
