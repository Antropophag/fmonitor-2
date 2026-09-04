# Independent Gate 1 rereview v13 — prepare upload-first amendment

Date: 2026-09-04  
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v7`  
Reviewed commit: `967d79a86f83fa640756c2a8101e13b952cc2aac`  
Gate: fresh Gate 1 rereview after v12 corrective amendment  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- Server validation now occurs before successful HTML and produces the redacted
  `503`; independent client rejection explicitly cannot change an already sent
  status and instead preserves the visible fallback with zero hidden IDs. This
  closes v12 finding 1 in the form spec itself.
- Results now have an exact `div role=group`, programmatic name, polite live
  semantics, direct native-button ownership and an exact zero-result paragraph.
  This closes v12 finding 2.
- The earlier ID bound and numeric tab mapping, always-empty `data-busy`, record
  ordering and duplicate rejection, repository-owned asset, literal CSP,
  accessible state/focus behavior, no-JS fail-closed state, engineer selection
  and read-only scope remain present.

The exact-hash batch nevertheless remains internally inconsistent and does not
yet provide a deterministic, independently constructible server/client parser
contract.

## Blocking findings

### 1. The related OpenSpec contradicts the new server/client split

`PILOT-PREPARE-FORM-001` lines 40–45 now correctly separates server rejection
before successful HTML from client rejection after a successful response.
However, the unchanged verification delta lines 61–65 still inherits the
record/parser contract as one whole and requires every malformed picker input
to fail closed before a successful representation.

Consequently the complete reviewed batch still assigns incompatible observable
outcomes to malformed client-visible picker input. Gate 2 can require no
successful response while the amended form spec requires an already successful
response followed by fallback.

Required correction: update the OpenSpec delta to inherit the explicit split:
source-record invalidity rejects server-side before successful representation,
while DOM/client parser rejection after successful delivery preserves fallback,
zero hidden IDs and zero mutation without changing HTTP status.

### 2. The client parser grammar is neither independently possible nor exact

Lines 30–45 say the client independently rechecks “the same contract”. That
contract includes source/wire properties such as valid UTF-8 and HTML escaping
which JavaScript cannot independently reconstruct after browser decoding and
HTML parsing. The amendment also removes the previous explicit rule that the
parser reads only the six dataset fields from direct template descendants.
Although each valid record is described as a direct empty `span`, client
handling of nested or extra descendants is no longer stated.

Required correction: define a separate exact post-parse DOM grammar which the
client can observe, including descendant ownership and rejection of unknown,
nested or malformed nodes/fields. Keep source validation, escaping and malformed
UTF-8 in the server grammar rather than claiming the browser can revalidate
unobservable response bytes.

### 3. Whitespace and query normalization remain nondeterministic

Lines 35–37 require byte equality to backticked `trim`, but do not define the
boundary character set. The following Unicode `White_Space` phrase governs
collapse runs without unambiguously defining `trim`, and it pins neither a
Unicode version nor an enumerated code-point set. PHP and JavaScript trim rules
can therefore disagree for the same valid UTF-8. “Normalization form does not
change” settles NFC/NFD conversion, not whitespace grammar.

The picker also says that a query is “normalized” (line 22) without specifying
case handling/folding, substring versus prefix matching or the searched fields,
despite status copy mentioning name and tab number. In addition, the required
local search/filter behavior in lines 19–23 and 74–80 conflicts with §12 line
331, which still excludes search and filtering; the replacement table does not
supersede §12.

Required correction: enumerate the exact boundary/collapse code points (or pin
one exact Unicode property version and explicitly apply it to both operations),
define the complete query normalization and match algorithm, and distinguish
the required local picker search from the workload/absence/remote search that
is intended to remain out of scope.

## Standards

No additional documented-standard breach or material Fowler smell was found in
this prose-only one-file amendment. `git diff --check` passes. The contradictions
and under-specification above are hard Gate 1 contract findings rather than
formatting or implementation-style concerns.

## Scope and security assessment

The amendment itself remains inside the read-only `GET|HEAD` slice and does not
authorize upload parsing, persistence, composition commands or opening work.
Authorization-first reads, zero mutation, text-only DOM construction, external
same-origin JavaScript, CSP literals and redacted failures remain preserved.
The required corrections narrow the selected picker boundary; they do not
expand the feature.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `967d79a` is not
ready for exact-hash owner approval and does not authorize replacement RED,
Gate 3 or GREEN. Preserve all earlier approvals and reviews as historical
append-only evidence.

After reconciling the OpenSpec failure outcome, defining an observable client
DOM grammar, and fixing exact whitespace/query behavior and scope, the complete
changed exact-hash batch requires another fresh independent Gate 1 rereview and
then explicit owner approval.

## Exact reviewed hashes

```text
04644a710fafaca0f97e3ad38b724d48af165de8b812731da63cb91d312dc005  specs/PILOT-PREPARE-FORM-001.md
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
967d79a86f83fa640756c2a8101e13b952cc2aac

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
967d79a86f83fa640756c2a8101e13b952cc2aac  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 3ff48e1590c50f6659e46537af9aade4d08b53a2..967d79a86f83fa640756c2a8101e13b952cc2aac
PASS (exit 0, no output)

$ git diff --stat 3ff48e1590c50f6659e46537af9aade4d08b53a2..967d79a86f83fa640756c2a8101e13b952cc2aac
 specs/PILOT-PREPARE-FORM-001.md | 18 ++++++++++++------
 1 file changed, 12 insertions(+), 6 deletions(-)
```
