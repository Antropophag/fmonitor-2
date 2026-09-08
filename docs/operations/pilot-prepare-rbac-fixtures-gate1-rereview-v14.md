# Independent Gate 1 rereview v14 — prepare upload-first amendment

Date: 2026-09-04
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v8`
Reviewed commit: `c7ce0a277c547424792352d0665604045c005d50`
Gate: fresh Gate 1 rereview after v13 corrective amendment
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- The OpenSpec verification delta now inherits the explicit validation split:
  an invalid source record produces server-side `503` before a successful
  representation, while post-delivery DOM/client rejection preserves the
  already delivered status, visible fallback, zero hidden IDs and zero
  mutation. This closes v13 finding 1.
- The client contract now operates on observable post-parse DOM. It restricts
  `template.content` to direct empty `span` records, exact attributes,
  whitespace-only intermediate text nodes, no element descendants, and decoded
  field/order/duplicate validation; UTF-8 and escaping remain server-only. This
  closes v13 finding 2.
- Record whitespace now has one enumerated set, U+0009..U+000D and U+0020, for
  both boundary rejection and internal collapse, with every other code point
  and normalization form unchanged.
- Section 12 now distinguishes the required exact local in-memory name/tab
  search from excluded workload/conflict/qualification/absence/remote search,
  server filtering and pagination. This closes the scope contradiction in v13
  finding 3.
- The earlier ID/tab mapping, empty busy field, record bounds/order/duplicate
  rejection, repository-owned asset and literal CSP, result-container grammar,
  accessible state/focus behavior, no-JS fallback, engineer selection,
  authorization-first reads and zero-mutation boundary remain intact. No prior
  closed finding regressed in the reviewed correction.

## Blocking finding

### 1. The query algorithm is still not independently executable

`PILOT-PREPARE-FORM-001` lines 22–25 applies whitespace normalization and
`toLocaleLowerCase('ru-RU')` to the query, then says to perform a substring
match against “normalized `data-name`”. It never says that the candidate name
is lowercased with the same operation. One implementation can therefore match
the lowercased query against the original mixed-case name, while another can
lowercase both; both satisfy the literal wording but return different results.

The same clause gates searching at “minimum 2 characters” without defining
whether that means Unicode code points or JavaScript UTF-16 code units, and
says to remove “non-digits” before requiring two ASCII digits without defining
whether only ASCII `0..9` survive. For example, a single supplementary-plane
character has one Unicode code point but two JavaScript code units, and Unicode
decimal digits outside ASCII may be retained or removed by plausible
implementations. Those choices change the minimum-query state and tab-number
matching.

Required correction: state the exact candidate-name transform (normally the
same whitespace transform and `toLocaleLowerCase('ru-RU')` as the query), define
the query-length unit, and replace “non-digits” with an exact retained/deleted
code-point set such as retaining only ASCII U+0030..U+0039. Gate 2 should pin
contrasts that distinguish these choices.

## Standards

No documented-standard breach or material Fowler smell was found in the
prose-only corrective diff. `git diff --check c0cca19..c7ce0a2` passes. The
remaining issue is a hard executable-contract ambiguity, not a formatting or
implementation-style concern.

## Scope and security assessment

The correction remains inside the read-only `GET|HEAD` picker slice. It does
not authorize upload parsing, persistence, composition commands or opening
work. Authorization-first reads, fail-closed source validation, client fallback,
text-only DOM construction, same-origin external JavaScript, literal CSP,
redacted failures and zero mutation remain preserved.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `c7ce0a2` is not
ready for exact-hash owner approval and does not authorize replacement RED,
Gate 3 or GREEN. Preserve all earlier approvals and reviews as historical
append-only evidence.

After making query normalization and matching independently exact, the complete
changed exact-hash batch requires another fresh independent Gate 1 rereview and
then explicit owner approval.

## Exact reviewed hashes

```text
a48a08fba9c5fe4939a48bf4d3396c6025e62cd8b67547bcdc6c84bc2d456894  specs/PILOT-PREPARE-FORM-001.md
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
c7ce0a277c547424792352d0665604045c005d50

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
c7ce0a277c547424792352d0665604045c005d50  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check c0cca19c856afbaac14d66fa68aa758429b4f6a6..c7ce0a277c547424792352d0665604045c005d50
PASS (exit 0, no output)

$ git diff --stat c0cca19c856afbaac14d66fa68aa758429b4f6a6..c7ce0a277c547424792352d0665604045c005d50
 .../pilot-prepare-rbac-fixtures/spec.md |  5 +++--
 specs/PILOT-PREPARE-FORM-001.md         | 23 +++++++++++++++-------
 2 files changed, 19 insertions(+), 9 deletions(-)
```
