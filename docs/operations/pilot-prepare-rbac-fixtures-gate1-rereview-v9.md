# Independent Gate 1 rereview v9 — prepare upload-first amendment

Date: 2026-09-04
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v3`
Reviewed commit: `308a01571776809e5304f955ccb793cc7d4b9d8a`
Gate: fresh Gate 1 rereview after v8 corrective amendment
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- The amendment now names one six-attribute inert element shape, rejects
  missing/unknown fields and malformed input, caps the record set at 500 and
  requires HTML attribute escaping followed by DOM/dataset parsing and
  text-only result construction.
- The picker script URL is fixed to same-origin
  `/pilot/assets/picker.js`; the response is required to be deferred, to use a
  JavaScript media type, and not to enable script on error/redirect/asset
  responses.
- Non-submit picker buttons now require `type=button`; the opener exposes
  `aria-controls`/`aria-expanded`, focus enters search and returns on Escape,
  Tab is not trapped, result selection has `aria-pressed`, removal has an
  accessible name, and live/summary regions are required.
- The engineer radio group, legacy prefill and separate unchecked confirmation
  remain unchanged. File input, multipart, CSRF, submit, upload persistence and
  command execution remain outside this read-only slice.

These are material improvements, but they do not yet make the representation
constructible from one coherent normative contract.

## Blocking findings

### 1. The alleged exact six-field record has two unidentified identities

The new schema requires both `data-id` (canonical decimal) and `data-tab`
(exactly six digits), while the inherited domain contract exposes one installer
identity, `installer_tab_id`, and the future command field is named
`installerTabIds[]`. Neither the amendment nor its example says what `data-id`
denotes, which field supplies it, or whether the hidden command value is
`data-id` or `data-tab`. The fixed example continues to require records ordered
as `1042, 2088`, which are not six-digit tab values. `data-busy` is likewise
named but has no allowed lexical grammar or semantic mapping; only
`data-selected=0` receives a value rule.

Consequently independent RED and GREEN authors must invent an additional
person identity, padding policy, hidden-value mapping and busy flag grammar.
This is not an exact parser/security boundary and can silently address a
different person at the future command seam.

Required correction: define the source and meaning of every one of the six
fields, exact lexical bounds for both flags and all untrusted strings, the
canonical ordering/deduplication key, and the exact field copied into
`installerTabIds[]`. Reconcile the six-digit tab requirement with the fixed
`1042/2088` oracle (or change the oracle through an owner-approved product
contract).

### 2. The accessibility and failure oracle is still not exact enough

The amendment does not fix the search input's accessible label text, the
picker container's exact element/`popover` semantics and accessible name, the
zero-result text, or the exact count/result/selection announcements. It says
that count and result metadata are live but does not define which state changes
produce which observable text. It also does not state focus behavior after
removing the focused selected person or when search results are refreshed.

The no-JS clause proves only that hidden IDs are absent. The enabled opener and
search/picker DOM outcome when the asset is blocked, missing or malformed is
not fixed, so a conforming response may expose a dead `Выбрать монтажников`
control with no explanation. This does prevent hidden command input, but does
not meet v8's required observable usable/fail-closed UI outcome.

Required correction: provide exact accessible names/relationships and exact
zero-result/count/selection status strings; define result refresh and removal
focus behavior; and define the observable no-JS/load-error UI (including
whether the opener is absent/disabled and the visible failure explanation).

### 3. JavaScript and asset ownership remain contradictory across successors

The form's section 12 now makes a narrow exception for
`/pilot/assets/picker.js`, closing the local contradiction identified in v8.
However `PILOT-UI-SHELL-001` remains an active successor and still states in
section 3 that JavaScript is not connected in this slice and in section 10 that
search/filter/pagination and behavior JavaScript are out of scope. Its
successor notice replaces prepare presentation clauses in sections 6–8, not
these absolute section 3/10 statements.

The asset admission contract is also incomplete: “approved script CSP”,
“immutable bytes” and an exact media type do not identify the exact CSP
directive/value, source/configuration/descriptor contract, GET/HEAD/cache
headers, malformed method/path precedence, or configured/unconfigured failure
behavior for `/pilot/assets/picker.js`. Gate 2 and Gate 4 would have to invent
both security assertions and production configuration.

Required correction: explicitly supersede the conflicting UI-shell JS clauses
for this one asset and define its exact public asset/admission contract,
including CSP value, descriptor/source ownership, method/path/HEAD behavior,
media/cache headers and fail-closed configuration errors. Retain the ban on
inline, third-party and general behavior JavaScript.

## Coherence and scope assessment

The owner-approved upload-first direction and engineer product truth remain
preserved, and the OpenSpec package remains read-only. The successor table still
leaves one intended card label, page heading/breadcrumb, installer picker,
engineer group, installer empty reason and neutral return action after applying
its listed replacements. The blockers above concern constructibility,
accessibility/security and cross-spec ownership; resolving them does not
authorize adding upload or mutation behavior to this slice.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `308a015` is not
ready for exact-hash owner approval and does not authorize replacement RED,
Gate 3 or GREEN. Preserve all earlier approvals/reviews as historical records.

After the schema/identity mapping, observable accessibility/no-JS behavior and
UI-shell/asset contract are corrected, the entire changed exact-hash batch
requires another fresh independent Gate 1 rereview and then explicit owner
approval.

## Exact reviewed hashes

```text
dbf9915e4355854a6fde9b24d5132d1a6b2bfcd36202a9cea473409708d37a51  specs/PILOT-PREPARE-FORM-001.md
fca388d01589c6495e9ab0d63cf548d80d4c386cedd7eded8ed66dd39549f3d7  specs/PILOT-UI-SHELL-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
1b8035dcdc5469704424d0c91d1589db52e35a4b139c634c6eb509410eb6bb06  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
a1b1a95caf04b2f6793561fac58ee7420606eabddbf3523f50435afeaf934aba  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

## Verification

```text
$ git rev-parse HEAD
308a01571776809e5304f955ccb793cc7d4b9d8a

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
308a01571776809e5304f955ccb793cc7d4b9d8a  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 308a015^ 308a015
PASS (exit 0, no output)

$ git diff --stat 308a015^ 308a015
3 files changed, 29 insertions(+), 3 deletions(-)
```
