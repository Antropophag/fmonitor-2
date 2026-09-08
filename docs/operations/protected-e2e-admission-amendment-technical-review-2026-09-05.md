# Protected E2E admission amendment — independent technical review

- Recorded: `2026-09-05`
- Reviewer: `/root/protected_e2e_gate1_scope_audit`
- Candidate: `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`
- Candidate SHA-256: `eaeee5f7e701cdb0fccd70ba823a674b16014132ad4da585bc75460968454d8e`
- Verdict: **CHANGES_REQUIRED**
- Scope: technical Gate 1 readiness only; no approval claim and no protected
  specification, test or production change.

## Accepted findings

The representation contract is correctly derived. For configured production,
`PILOT-UI-SHELL-001` section 5 supersedes the predecessor presentation choice:
the collection is `ul|ol`, each object is one `li` with one canonical link,
the visible link label is the exact numeric ID, and native `table` plus
`.shlz-table-wrap` are forbidden. The candidate correctly covers the initial
and repeated actor-18 observations together, retains the fixed fixture facts,
RBAC/snapshot/cleanup obligations, and gives no authority to change production
or approve the downstream manual-registration journey.

## Blocking finding — no independently green public verifier seam

The candidate names the unchanged full
`tests/InstallationProcess/pilot_e2e_flow_001_test.php` execution as the RED and
post-patch evidence, while explicitly accepting a later downstream failure and
claiming no whole-verifier GREEN. That does not yet satisfy the mandatory Gate
4 rule that the independently reviewed test and relevant suite are green.
Observing that admission assertions were reached before a later failure is a
useful boundary diagnostic, but it is not a passing focused test.

Revise the candidate before owner approval to name an independently executable
public verifier-correctness seam whose exit/result becomes RED for the stale
admission oracle and GREEN after the exact reviewed test-only correction. The
full protected E2E must still be executed without skip, early return,
catch-and-success or accepted-failure exit and must preserve its next real
downstream failure as separately classified regression evidence. The revision
must state the exact permitted test/support-file patch boundary; the current
“assertion-only patch” boundary is insufficient if creating that focused seam
requires extraction or a support verifier. No gate waiver or production change
is needed or authorized.

## Required oracle clarification

Fix the Gate 3 sensitivity matrix in the candidate rather than leaving
“fictional HTML” unconstrained. The independently specified test-only inputs
must make the focused verifier reject at least: missing 4512 item; duplicate
4512 item/link; wrong ID text or href; correct link outside the object `li`;
missing/wrong one of the five inherited neighboring facts; a `table`; and a
`.shlz-table-wrap` element. Include one conforming semantic-list control. These
inputs must be literal test data, not renderer output, while real configured
HTTP remains the acceptance observation.

With those two changes, the candidate can return for a fresh exact-hash review.
No change is requested to the representation, authority, original-first or
downstream boundaries.

## Exact reviewed bytes

```text
eaeee5f7e701cdb0fccd70ba823a674b16014132ad4da585bc75460968454d8e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```
