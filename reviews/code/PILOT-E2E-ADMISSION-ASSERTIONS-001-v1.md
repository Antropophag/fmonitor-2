# Code review: PILOT-E2E-ADMISSION-ASSERTIONS-001 v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/admission_integration_gate5`
- Implementation author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `060e880cdff41b8564a005fba95d6ed796c7772f`
- Specification: owner-approved revision 3 of `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`, SHA256 `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Approved test review: `reviews/tests/PILOT-E2E-ADMISSION-ASSERTIONS-001-v1.md`, `APPROVED`
- Oracle prerequisite: `reviews/code/PILOT-E2E-ADMISSION-ORACLE-001-v2.md`, `APPROVED`
- Verification evidence: `docs/operations/protected-e2e-admission-integration-verification-2026-09-05.md`, SHA256 `9906305f0e58750f519556b011f898013382029f34b6c7295d705b201071e51b`
- Verdict: `APPROVED` for the scoped protected-admission assertion integration only

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
1ea21d78e9c1fe3681085ddeb60557eef5b113d046efa56c68fece12c168a48a  docs/operations/protected-e2e-admission-patch-gate3-packet-2026-09-05.md
2fadffb1b9ca4269f03f090a8347155f5b9f005f6d1f3874ac43f758b3e9d33d  docs/operations/protected-e2e-admission-assertions-v1-2026-09-05.patch
496b11c87227abec92c56a977c95dc7935dd81b38e7de28512af10b3be6c1ef2  tests/Support/ProtectedE2eAdmissionOracle.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
0b05d0fa48e256a4584f0937285c892598a4a23110d7e60f9bb921560cf44049  tests/Verification/protected_e2e_admission_class_tokens_001_test.php
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b  tests/InstallationProcess/pilot_e2e_flow_001_test.php
9906305f0e58750f519556b011f898013382029f34b6c7295d705b201071e51b  docs/operations/protected-e2e-admission-integration-verification-2026-09-05.md
```

## Findings

No blocking finding exists within the protected-admission assertion integration.

The reviewed commit applies the independently approved patch exactly. Its parent
protected verifier has SHA256 `a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`;
the reviewed result has the approved proposed SHA256
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.
The commit changes one file with two additions and two deletions on two physical
lines. All and only the three stale table assertions are replaced: the initial
table-link assertion becomes an oracle call on actual
`$queueAdmission['body']`, and the repeated six-heading plus table-link
assertions become an oracle call on independently obtained actual
`$queue['body']`.

The admission status-200 prerequisite, read-only authorization wrappers, DOM
setup, `pefRedesignNoRaw`, actor19 and missing-identity sentinels, exact actor18
grant, authority matrix, complete manifest equality, revoke and negative
principal checks, response/transport comparisons, redaction checks, full
downstream journey, and `finally` cleanup remain byte-preserved. The change is
test-only and introduces no production route, application behavior, database or
artifact mutation, authorization relaxation, audit/history change, skip, early
return, exception-to-success conversion, or allowed-failure list. The separately
reviewed oracle retains its 28-case semantic-list matrix and nine-case HTML ASCII
class-token matrix.

The direct protected invocation reached and passed both admission calls, the
actor19 sentinel, exact grant, authority matrix, and before/after manifests. It
then exited `255` at the first unchanged downstream card assertion on line 157:

```text
launch action visible Сформировать распоряжение
Expected: true
Actual: false
```

This proves the reviewed admission assertions accept the configured production
HTTP representation and do not stop the protected verifier from exposing its
next failure. It does not prove the remainder of the journey, the legacy manual
registration behavior, or launch readiness.

Full `make verify` at the same commit exited `2` after
`369.515255917` seconds. `test-db-reset`, `migrate`, `architecture-check`,
`lint`, `unit-test`, `characterization-test`, and `diff-check` passed.
`db-test` failed for both `pilot_e2e_flow_001_test.php` and
`pilot_demo_bootstrap_001_test.php`, whose inherited contract invokes that same
E2E; `e2e-test` failed for the direct protected verifier. Every recorded failure
is the same unchanged line-157 launch-action mismatch after the corrected
admission boundary. The raw evidence is retained with hashes
`82b7125d68e917a56f36ff877409e6f2635ad39dc69d4f1bbd8bd074ee167d36`
for `patched-e2e.log`,
`0d70070a8974b87a8ce360329bd6954cd98e9bdbbdaf33c1ce6e0850c9857e5a`
for `full-verify.log`, and
`cb5d492d805488ee3629c8259a00a122c0e04c00dd3028e5369ed6c4fed0ce28`
for `full-verify-timings.json`.

Accordingly, this verdict approves only the exact admission fixture-correction
integration. The whole protected E2E is failed, literal `VERIFY_OK` is absent,
and the downstream launch-action mismatch remains blocking. This approval does
not make the parent OpenSpec change Done and grants no Quality Graph integration,
bootstrap CI pull request or publication, deployment, or launch approval.

## Required changes

None within the scoped protected-admission assertion integration. Resolve the
retained downstream launch-action failure through its own approved specification
and gates before claiming whole-E2E GREEN, parent completion, or launch readiness.
