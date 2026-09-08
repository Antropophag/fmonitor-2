# Independent Gate 1 rereview v12 — prepare upload-first amendment

Date: 2026-09-04  
Reviewer: fresh independently tasked agent `/root/prepare_upload_screen_gate1_v6`  
Reviewed commit: `ebf0ab1bba8087b48cae4589079ca30f195b5ade`  
Gate: fresh Gate 1 rereview after v11 corrective amendment  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation. This append-only review record is the
reviewer's only change.

## Corrections verified

- Picker IDs are now capped at `1..999999`, and the six-digit display value is
  explicitly required to be the zero-padded numeric equivalent.
- `data-busy` no longer invents an assignment/workload projection: it is always
  empty and a nonempty value is rejected.
- Names and positions are required to be trimmed, nonempty UTF-8 with explicit
  code-point ceilings. Picker order is stated directly as Unicode code-point
  name order with numeric-ID tie-break, and duplicate IDs reject the complete
  dataset.
- The asset is an exact repository-owned bundled source with no environment
  override. The contract no longer invents a runtime digest/revalidation
  protocol, and both CSP policies are present as byte-exact literals rather
  than relying only on the status of another draft spec.
- Initial/result/selection strings, result accessible names and post-rerender
  focus are materially more explicit than v11.

These amendments close v11 findings 1–3 and the bundled-asset/CSP portion of
finding 4. They do not yet leave one executable server/client failure contract
or a complete result-container accessibility oracle.

## Blocking findings

### 1. Dataset rejection has two incompatible HTTP outcomes

`PILOT-PREPARE-FORM-001` lines 38–42 assign the six-field validation to the
browser parser and say an invalid field, nonempty busy value, malformed UTF-8,
duplicate or over-limit dataset gives a redacted HTTP `503`. Once the browser
has received successful HTML and runs `picker.js`, it cannot replace that
already committed HTTP status and headers with `503`. Lines 77–81 separately
require load/error/parser rejection to leave the visible fallback, zero hidden
IDs and zero mutation in the already delivered page.

Thus the same malformed serialized dataset has two incompatible observable
outcomes: server `503` or successful HTML with client fallback. Gate 2 and Gate
4 would have to invent which validations are performed before response commit
and which are repeated by the browser.

Required correction: split the contract explicitly. Require server-side
validation of all source records before rendering, with redacted `503` on
failure; then define client parser rejection only as fail-closed fallback in an
already successful response. State whether and how the two validators share
the exact grammar without making browser JavaScript responsible for an HTTP
status.

### 2. Result-container accessibility semantics remain under-specified

Lines 61–74 now fix the dialog role/name, search label and a textual label
`Результаты поиска`, but do not fix the result container element/role or the
programmatic mechanism attaching that label. A generically labelled DOM
container may not be exposed as a navigable result collection in the
accessibility tree. The contract also does not define whether result buttons
are a list, region or another pattern.

This leaves independent RED and GREEN authors to invent materially different
accessible structures while both can claim the same visible label and
`aria-pressed` buttons.

Required correction: choose and state one constructible result-container
pattern, including element/role, label association, ownership of result
buttons, empty-result structure and its relation to the search/live metadata.

### 3. String normalization is not exact enough for deterministic order

Lines 35–42 require strings to be “trimmed” and then order records by Unicode
code points, but do not define which whitespace set is trimmed or whether any
Unicode normalization form is applied. ASCII-only PHP `trim`, Unicode-aware
trimming and NFC normalization can produce different accepted values,
duplicate-looking names and order for the same UTF-8 input.

Required correction: name the exact trimming/normalization algorithm and
failure behavior, or explicitly state that no Unicode normalization occurs and
enumerate the code points removed at the boundaries. Gate 2 literals must use
that same independently fixed rule.

## Scope and security assessment

The amendment remains within the read-only `GET|HEAD` slice. It does not add a
file input, multipart parsing, CSRF, upload persistence, composition command or
opening transition. Authorization-first reads, zero mutation, text-only DOM
construction, no inline/remote script and redacted failures remain preserved.
The blockers above concern exactness of the already selected picker boundary;
they do not authorize expanding the slice.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Task 1.6 remains open. Commit `ebf0ab1` is not
ready for exact-hash owner approval and does not authorize replacement RED,
Gate 3 or GREEN. Preserve all earlier approvals and reviews as historical
append-only evidence.

After separating server and browser validation outcomes, fixing an exact
accessible results pattern and making string normalization deterministic, the
complete changed exact-hash batch requires another fresh independent Gate 1
rereview and then explicit owner approval.

## Exact reviewed hashes

```text
32cf48e782dfa7aef6435b4b4269084c36f6bf28ae928977d01193d5512b8311  specs/PILOT-PREPARE-FORM-001.md
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
ebf0ab1bba8087b48cae4589079ca30f195b5ade

$ git ls-remote origin refs/heads/codex/pilot-prepare-rbac-green
ebf0ab1bba8087b48cae4589079ca30f195b5ade  refs/heads/codex/pilot-prepare-rbac-green

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ git diff --check 40f8a8d..ebf0ab1
PASS (exit 0, no output)

$ git diff --stat 40f8a8d..ebf0ab1
2 files changed, 187 insertions(+), 17 deletions(-)
```
