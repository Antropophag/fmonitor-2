# Independent Gate 1 rereview v15 — prepare upload-first amendment

Date: 2026-09-04
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v9`
Reviewed commit: `80a399b391cbf2b4e52079dfae95dcbaf5cb65ac`
Gate: final fresh Gate 1 rereview after v14 query-algorithm correction
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## v14 finding closure

The sole v14 blocker is closed in `PILOT-PREPARE-FORM-001`:

- query normalization replaces runs of exactly U+0009..U+000D/U+0020 with one
  U+0020, removes those boundary characters and applies
  `toLocaleLowerCase('ru-RU')`;
- candidate `data-name` now explicitly passes through that same
  whitespace/lowercase normalizer before substring matching;
- the two-character threshold is measured as Unicode code points by the exact
  JavaScript expression `Array.from(query).length`;
- the tab branch deletes every code point except ASCII `[0-9]`, and enables
  substring matching against the six-digit `data-tab` only after at least two
  retained digits.

These clauses distinguish the alternatives identified by v14: an original
mixed-case candidate cannot be compared without the specified lowercase
transform, a supplementary-plane character counts as one rather than two, and
non-ASCII decimal digits cannot enter the tab query. The algorithm is now
independently executable.

## Regression review of prior closures

The correction changes only the query paragraph. The complete exact-hash batch
was reread against the v7-v14 finding chain. No closed finding regressed:

- the governing RBAC spec explicitly selects the upload-first v0.2
  representation while preserving both independent admission gates and exact
  authorization/read precedence;
- engineer selection remains one eligible radio group with separately unchecked
  confirmation; picker identity remains installer `tabId` versus DOM `id`;
- the record grammar, direct-child parser, empty busy value, duplicate/order/
  bounds rejection and server/client failure split remain exact;
- the repository-owned same-origin asset, literal CSP, DOM-only rendering,
  no-JS fallback and successor disposition remain coherent;
- the enumerated whitespace set, accessible state/focus/keyboard behavior,
  result-container grammar and excluded search semantics remain unchanged;
- GET/HEAD remain read-only and do not authorize upload parsing, persistence,
  composition commands or opening work.

## Standards and scope

No documented-standard breach or material Fowler smell was found in the
prose-only correction. `git diff --check` passes. The change is limited to
removing the exact ambiguity requested by v14 and introduces no implementation
or test work.

## Gate decision

The complete exact-hash package is **READY_FOR_OWNER_APPROVAL**. Task 1.6 stays
open until the owner explicitly approves these hashes. This technical verdict
does not itself authorize replacement RED, Gate 3 or GREEN. Any subsequent
change to an artifact in the approved batch requires a fresh exact-hash Gate 1
review.

## Exact reviewed hashes

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
1b8035dcdc5469704424d0c91d1589db52e35a4b139c634c6eb509410eb6bb06  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
0c87ed39e3454e87339e606b3c1d4202538cd0d46534a590e69739cf8d19087a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

## Verification

```text
$ git rev-parse HEAD
80a399b391cbf2b4e52079dfae95dcbaf5cb65ac

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
80a399b391cbf2b4e52079dfae95dcbaf5cb65ac  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check c0cca19c856afbaac14d66fa68aa758429b4f6a6..80a399b391cbf2b4e52079dfae95dcbaf5cb65ac
PASS (exit 0, no output)

$ git diff --stat c0cca19c856afbaac14d66fa68aa758429b4f6a6..80a399b391cbf2b4e52079dfae95dcbaf5cb65ac
 ...lot-prepare-rbac-fixtures-gate1-rereview-v14.md | 124 +++++++++++++++++++++
 .../pilot-prepare-rbac-fixtures/spec.md            |   5 +-
 specs/PILOT-PREPARE-FORM-001.md                    |  26 +++--
 3 files changed, 146 insertions(+), 9 deletions(-)
```
