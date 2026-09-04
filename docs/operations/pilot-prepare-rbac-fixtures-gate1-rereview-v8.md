# Independent Gate 1 rereview v8 — prepare upload-first amendment

Date: 2026-09-04  
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v2`  
Reviewed commit: `ff61af80427b4cd9d3b0f23ea9fd7de6f480b812`  
Gate: fresh Gate 1 rereview after v7 corrective amendment  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- The controlling fixture contract now inherits
  `PILOT-PREPARE-FORM-001 v0.2`, rather than the obsolete v0.1 contract.
- The v0.2 replacement table and the UI-shell successor notice now identify the
  card launch, prepare page, installer representation, prepare example and
  browser-oracle areas which the upload-first representation replaces. Those
  notices leave one intended card label (`Загрузить распоряжение`), heading
  (`Загрузить распоряжение`), current breadcrumb (`Распоряжение`), installer
  representation (picker), installer empty reason (`Нет допустимых
  монтажников.`), and return action (`Отмена`) after applying the stated
  successor rules.
- Engineer semantics now agree with `PRODUCT.md`, the pilot specification and
  the exact owner decision: eligible engineers remain one radio group; a valid
  legacy value is only a prefill; and the separate confirmation remains
  unchecked. The GET does not reinterpret the card engineer as an authoritative
  composition fact.
- The OpenSpec proposal/design/delta keep this slice read-only and leave file
  input, multipart parsing, CSRF, upload, persistence, composition application
  and opening to separately gated command/HTTP slices.

These corrections close findings 1–3 from v7. They do not close the picker
constructibility/security finding below.

## Blocking findings

### 1. The picker still has no exact constructible data/asset contract

Section 0 says normalized installer records are placed in
`<template data-picker-data>` and an external same-origin `picker.js` builds the
interaction. It does not define the script URL/admission/failure contract or the
template's exact record schema and encoding. “Escaped normalized records” does
not determine whether the inert bytes are text nodes, JSON, nested elements or
attributes, nor how the script parses them. As a result, two implementations
can satisfy the prose while disagreeing on observable DOM and security
boundaries, and Gate 2 cannot independently fix hostile-name/provenance
expectations without inventing a serialization.

This is security-relevant: the `textContent` construction rule protects the
rendered result only after parsing. The contract must also fix how untrusted
catalog text crosses the server-to-template boundary, forbid HTML
interpretation during parsing, and specify deterministic behavior for malformed
or over-limit inert data.

Required correction: define an exact bounded inert record representation,
escaping/parse algorithm, deterministic canonical order/identity fields, exact
same-origin asset URL and asset admission/failure behavior. Keep all DB-derived
text text-only through both parsing and result construction.

### 2. Native controls and keyboard/focus behavior remain under-specified

The picker trigger, result buttons and remove buttons are inside the stated GET
form, but none has an exact `type="button"`; HTML therefore permits accidental
GET submission for default buttons. “Popover управляется native
button/keyboard” does not identify whether the contract uses the Popover API or
an application-owned region, and it fixes no expanded/controls relationship,
focus entry/return, Escape behavior, arrow/Tab behavior, result empty text, or
selected-count/summary announcement. `aria-pressed` on result buttons alone
does not make the complete picker keyboard/focus contract observable.

The no-JS fail-closed sentence is useful, but the executable contract still
cannot distinguish a constructible accessible picker from one that leaves
focus behind hidden content, submits the GET form, or exposes no usable status
when search has zero results.

Required correction: require `type="button"` for every non-submit picker
button; fix exact accessible names and state relationships; define open/close,
focus entry/return, Escape and Tab/keyboard behavior; define live result/count,
zero-result and selected-summary behavior; and state the no-JS DOM outcome in
observable terms.

### 3. The exhaustive successor list still conflicts with an unlisted JS ban

The v0.2 replacement table supersedes selected assertions in sections 1, 4, 5,
7, 8, 9 and 10, but it does not supersede section 12. Section 12 still places
`custom CSS/JS` and a behavior asset outside the slice, while section 0 now
requires an external behavior script. Likewise the old section 8 prose says the
page “не добавляет CSS/JS”; that particular section is listed as replaced, but
the later out-of-scope ban is not. The UI-shell also still says “no inline
style/script”; that can coexist with an external asset, but the form spec's
absolute custom-JS ban cannot.

Required correction: explicitly replace the section 12 JS exclusion only for
the exact bounded picker asset, retaining the ban on inline/third-party/general
behavior JS and keeping upload-command behavior out of this read slice. The
final text must leave exactly one normative answer about whether this Gate 1
slice owns `picker.js`.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `ff61af8` must not
receive owner hash approval and does not authorize a replacement RED, Gate 3 or
GREEN. Preserve all earlier approvals and reviews as historical records.

After correcting the exact picker serialization/asset boundary, accessible
control behavior and the remaining JS ownership contradiction, the entire
changed exact-hash batch requires another fresh independent Gate 1 rereview and
then explicit owner approval.

## Exact reviewed hashes

```text
a3c2331ab22d7b4bd341f34535d38370c84b96dec3c3cad2ca9fad0246d4ca8f  specs/PILOT-PREPARE-FORM-001.md
fca388d01589c6495e9ab0d63cf548d80d4c386cedd7eded8ed66dd39549f3d7  specs/PILOT-UI-SHELL-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
270841d2ec713ec0eded3d162b284085d0dfe5cbc899dda4d09708cc52cb125f  openspec/changes/pilot-prepare-rbac-fixtures/design.md
1b8035dcdc5469704424d0c91d1589db52e35a4b139c634c6eb509410eb6bb06  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
85397a877bb34d05cf1954fd869b5aeaed306f39d847870b7f17fe417c84886a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

## Verification

```text
$ git rev-parse HEAD
ff61af80427b4cd9d3b0f23ea9fd7de6f480b812

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
ff61af80427b4cd9d3b0f23ea9fd7de6f480b812  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 034ce0c..ff61af8
PASS (exit 0, no output)
```
