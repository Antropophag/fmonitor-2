# Independent Gate 1 rereview v11 — prepare upload-first amendment

Date: 2026-09-04
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v5`
Reviewed commit: `40f8a8d59f979d51789b222467b7cde5b4bca00f`
Gate: fresh Gate 1 rereview after v10 corrective amendment
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- The picker asset now has one repository-owned source path,
  `app/PilotHttp/picker.js`, no environment override, exact route/method
  precedence, literal media/cache headers, GET/HEAD behavior and a fail-closed
  error outcome. The text uses the active CSP contract's normative name
  `SCRIPT_HTML_CSP` rather than the nonexistent `SCRIPT_CSP`.
- The opener, dialog, search and results now have fixed accessible names; the
  opener/dialog relationship, initial expanded state, focus entry, Escape
  return and post-rerender focus are stated. The no-JS/parser-failure fallback
  and zero-hidden-ID outcome remain intact.
- `data-id` now has a lexical integer range and `data-tab` names left-zero
  padding. `data-busy` now has an intended inclusive-date meaning rather than
  only a lexical shape.
- The engineer radio group, eligible legacy prefill and separate unchecked
  confirmation remain aligned with product truth. The successor table and
  UI-shell exception still leave one intended upload-first representation, and
  the OpenSpec package remains a read-only GET/HEAD slice.

These changes improve constructibility, but they do not close all exact
blockers required by v10.

## Blocking findings

### 1. ID/tab semantics remain internally contradictory

The amendment admits `data-id` values through `9223372036854775807`, while
requiring `data-tab` to be exactly six digits, left-zero-padded and numerically
equal to that ID. Values from `1000000` upward satisfy the new ID grammar but
cannot have the required six-digit representation. The inherited eligibility
contract admits any positive integer and does not reject that range earlier.

Therefore a renderer/parser author still has to invent whether such a delivered
row is rejected, omitted, truncated or rendered with more than six digits. V10
explicitly required the upper-bound/failure decision.

Required correction: cap the canonical picker ID at `999999` with an exact
fail-closed outcome, or owner-approve a different display representation. State
the decimal-to-display transformation without an impossible admitted range.

### 2. `data-busy` introduces an unapproved and unavailable source fact

The new clause says `data-busy` comes from an “already-read current/future
assignment projection”. No such projection is named in the inherited sources,
route read order, OpenSpec design or data contract for this slice. In fact the
still-normative eligibility section states that workload and overlapping
assignments are not filtering facts because there is no approved production
fact/policy for them. Calling the value informational does not define its
authoritative table/projection, snapshot date or integrity/failure behavior.

This is both an engineer-truth ambiguity and a product decision the
implementation cannot make. It also expands the stated read dependencies of a
read-only RBAC fixture without an approved predecessor.

Required correction: remove `data-busy` until a separately approved projection
exists, or reference an exact approved source contract and define snapshot,
mapping and failure semantics. Do not infer workload truth from unspecified
legacy data.

### 3. String, ordering and deduplication rules are still not complete at the picker boundary

V10 required normalized, nonempty and bounded UTF-8 rules for `data-name` and
`data-position`. V11 adds no such rules. “Escaped normalized records” is not an
exact normalization algorithm or byte/code-point ceiling. The picker paragraph
also does not directly state canonical record order and deduplication; readers
must infer that the old checkbox candidate ordering survives a presentation
replacement while combining it with the new 500-record parser rule.

Required correction: specify the normalization, nonempty requirement and exact
bound for both untrusted strings, with a fail-closed over-bound outcome; state
the picker record ordering and duplicate-ID rejection directly in the v0.2
representation.

### 4. Asset immutability/CSP authority and accessible state transitions remain incomplete

The asset is now repository-owned, but “changed asset” has no reference digest,
build manifest or startup/request snapshot against which change is detected.
No descriptor/open/revalidation/TOCTOU and cleanup rule is given, so
“byte-identical repeat” and `changed → 503` do not identify an implementable
admission mechanism. The successful HTML also inherits `SCRIPT_HTML_CSP` only
by name from `PILOT-ROUTE-CSP-001`, whose exact reviewed hash is still explicitly
`DRAFT / Gate 1`; v11 does not account for that status or inline the byte-exact
policy as this slice's own approved assertion.

Accessibility names are improved, but the live transition oracle remains
partial: the contract does not state the initial/open announcement, which exact
string replaces it for query shortening, nor the exact result-container
element/role and relation to the search control. “New pressed state is
announced live count” also does not identify which live region contains which
exact selected-state text after toggling.

Required correction: define one implementable bundled-asset validation and
revalidation model (including resource ownership/cleanup), make the byte-exact
CSP authoritative without relying on an unapproved predecessor, and provide
the exact initial/query/result/selection state-to-live-region transitions and
result-container semantics.

## Coherence and scope assessment

The upload-first label, page heading/breadcrumb, picker representation,
engineer selection/confirmation, installer empty state and neutral return
action remain coherent after applying the successor table. File input,
multipart, CSRF, submit, persistence, composition application and opening are
still outside this read-only slice. The findings above require exactness at the
already-selected representation boundary; they do not authorize expanding the
slice or making a new product decision.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `40f8a8d` is not
ready for exact-hash owner approval and does not authorize replacement RED,
Gate 3 or GREEN. Preserve all earlier approvals and reviews as historical
append-only records.

After correcting the ID/display domain, approved source facts, bounded string
serialization, bundled asset/CSP authority and exact accessible transitions,
the complete changed exact-hash batch requires another fresh independent Gate
1 rereview and then explicit owner approval.

## Exact reviewed hashes

```text
c0bcd7a6039e99733b73dc06b39927f34af161a838a6f4ae0e4fd688d951d737  specs/PILOT-PREPARE-FORM-001.md
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
40f8a8d59f979d51789b222467b7cde5b4bca00f

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
40f8a8d59f979d51789b222467b7cde5b4bca00f  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 3f3bd85..40f8a8d
PASS (exit 0, no output)

$ git diff --check 16a9460..40f8a8d
FAIL: append-only v10 review commit `3f3bd85` contains four pre-existing Markdown
hard-break trailing-space lines; the v11 corrective spec commit itself is clean.

$ git diff --stat 16a9460..40f8a8d
2 files changed, 195 insertions(+), 9 deletions(-)
```
