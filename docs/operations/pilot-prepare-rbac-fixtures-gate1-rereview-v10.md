# Independent Gate 1 rereview v10 — prepare upload-first amendment

Date: 2026-09-04  
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v4`  
Reviewed commit: `16a94608a07eb4e7bc7aad422139f2a2a5f12455`  
Gate: fresh Gate 1 rereview after v9 corrective amendment  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- `data-id` is now the canonical unpadded decimal `installerTabId` and future
  `installerTabIds[]` value; `data-tab` is its six-digit display form. The fixed
  `1042`/`2088` oracle is therefore no longer a second unidentified identity.
- `data-selected` has an exact initial value and `data-busy` at least has a
  lexical shape; ID/tab disagreement and invalid dates are explicitly rejected
  before a partial picker can be exposed.
- The initial no-JS/load/parser-failure DOM is fail-closed: trigger and popover
  remain hidden, a visible exact fallback remains, hidden IDs remain absent and
  initialization exposes the interaction only after validating the complete
  dataset.
- Exact status strings now cover minimum-query, ordinary result count,
  truncation, zero results and selected count. Removal of a focused chip returns
  focus to the picker opener.
- `PILOT-UI-SHELL-001` now explicitly permits only the exact same-origin picker
  asset as a prepare-v0.2 successor exception in both its architecture and
  out-of-scope clauses. Inline, remote and unrelated behavior JavaScript remain
  forbidden.
- The engineer radio group, eligible legacy prefill and separate unchecked
  confirmation remain intact. The OpenSpec package remains a read-only
  `GET|HEAD` RBAC fixture slice; upload parsing, CSRF, submit, persistence,
  composition application and opening are still outside it.

These corrections close the second-identity and cross-spec absolute-JS-ban
parts of v9. They do not yet provide one exact constructible parser, asset/CSP
and accessibility contract.

## Blocking findings

### 1. The picker asset still has no exact admission/configuration contract

The new text says that `/pilot/assets/picker.js` “inherits asset admission” and
has an “exact JavaScript media type/length/cache”, but it names neither the
contract inherited nor the literal media/cache headers. No environment key,
descriptor/path owner, basename rule, configured/unconfigured outcome, open and
revalidation protocol, or cleanup behavior is specified for this new file.
`PILOT-HTTP-AUTH-001` defines those matters only for `shlz.css`, while
`PILOT-UI-SHELL-001` defines them only for configured `pilot.css`.

The CSP names also disagree with the active CSP contract. This amendment says
`SCRIPT_CSP`, while `PILOT-ROUTE-CSP-001` defines byte-exact
`SCRIPT_HTML_CSP`; `SCRIPT_CSP` is not defined anywhere. Moreover that CSP spec
is itself still marked `DRAFT / Gate 1`, so merely referring to its symbolic
policy cannot silently promote it into an approved predecessor.

Gate 2 and Gate 4 would still have to invent how the asset is configured and
opened, exact success headers, whether absence is `404` or `503`, and which
byte-exact CSP is normative.

Required correction: identify an approved exact predecessor or state the full
picker asset contract here: configuration key/source ownership, descriptor and
TOCTOU/cleanup rules, configured/unconfigured behavior, literal Content-Type
and Cache-Control, GET/HEAD length parity, method/path precedence and redacted
failure behavior. Use the exact normative CSP name/value and explicitly account
for the Gate status of the CSP predecessor.

### 2. The record parser still lacks the requested semantic and lexical mapping

`data-busy` is now lexically empty or `YYYY-MM-DD`, but the contract still does
not state which approved source fact produces it or what the date means. The
inherited form contract explicitly says workload and overlapping assignments
are not filtering facts because no approved production fact/policy exists,
while the upload-first picker introduces a field named `busy`. An implementer
must therefore invent whether it is planned occupancy, an assignment end,
informational legacy text, or always empty.

The exact ID/tab relation is also only described as “consistent”: the contract
does not state the padding algorithm or reject `installerTabId > 999999`, even
though the inherited domain admits any positive integer and `data-tab` must be
exactly six digits. Finally `data-name` and `data-position` retain no explicit
normalized/nonempty/bounded lexical rule at this serialization boundary. This
does not meet v9's request to define the source and meaning of every field and
the exact lexical bounds of untrusted strings.

Required correction: name the approved source/meaning of `data-busy` (or remove
it), give the exact decimal-to-six-digit relation and upper bound/failure, and
state the normalized, nonempty and bounded UTF-8 rules for untrusted name and
position values. Define canonical record order/deduplication directly for the
picker representation rather than leaving authors to combine old checkbox prose
with the successor table.

### 3. Accessible names and popover/result focus remain under-specified

The v9 request asked for exact search and picker accessible names and exact
popover semantics. The amendment still says only that search “has label”; it
does not fix that label's text or association. It names an
`aria-controls=installer-picker` target but does not state the target element,
whether native `popover` is used, its accessible role/name, or the exact result
container relationship.

The status strings are fixed, but their transition mapping is not complete: it
does not say which initial/live text is present on open, which string replaces
which after query shortening, or where focus goes when filtering removes the
currently focused result. “Tab in native document order” is insufficient when
the result collection is dynamically replaced. Independent implementations can
therefore strand focus or announce different observable states while satisfying
the prose.

Required correction: fix the search label text/association, picker container
element and popover/role/name relationships, result-container semantics and the
exact state-to-announcement transitions. Define focus after result refresh when
the focused result disappears, in addition to the already fixed chip-removal
case.

## Exhaustive successor and scope assessment

The replacement table plus the amended UI-shell successor notices now give one
answer for card label, prepare heading/breadcrumb/intro, installer picker,
engineer selection/confirmation, installer empty state, return action and the
narrow JavaScript exception. The inherited old checkbox/browser assertions are
explicitly presentation-superseded. The remaining blockers are not requests to
expand the slice: they are required to make that selected representation
testable and safe while preserving its read-only boundary.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `16a9460` is not
ready for exact-hash owner approval and does not authorize a replacement RED,
Gate 3 or GREEN. Preserve every earlier approval and review as historical
append-only evidence.

After the exact asset/CSP predecessor, complete record semantics and complete
accessibility/focus oracle are corrected, the whole changed exact-hash batch
requires another fresh independent Gate 1 rereview and then explicit owner
approval.

## Exact reviewed hashes

```text
6b4a7dea6f95d3258a1f1a6ceef59b12d3019f229244135452600554517a20f7  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
1b8035dcdc5469704424d0c91d1589db52e35a4b139c634c6eb509410eb6bb06  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
a1b1a95caf04b2f6793561fac58ee7420606eabddbf3523f50435afeaf934aba  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

## Verification

```text
$ git rev-parse HEAD
16a94608a07eb4e7bc7aad422139f2a2a5f12455

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
16a94608a07eb4e7bc7aad422139f2a2a5f12455  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 308a015..16a9460
PASS (exit 0, no output)

$ git diff --stat 308a015..16a9460
3 files changed, 186 insertions(+), 13 deletions(-)
```
