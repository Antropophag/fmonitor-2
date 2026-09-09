# Test review: CHANGE-VERIFICATION-001

- Reviewer: root, independent of verification_plan author.
- Base: d9811cdd5e4521457a1d61277069fe3d0793f3fe; uncommitted spec/test.
- Public seam: tools/delivery/change-verification.py plan/check/run.
- Initial RED: author reports absent CLI, exit 1, 5 tests.
- Verdict: CHANGES_REQUESTED.

## Complete findings — pass 1

1. Bind each real acceptance spec file, not only the planner specification;
   permit multiple acceptance statements from one spec through unique IDs.
2. Prove missing required category coverage rejects or creates a real mandatory
   command; a category label alone is not an executable obligation.
3. Mutate planner with a harmless comment; replacing it with an empty program
   cannot test its own stale-plan check.
4. Keep fake interpreter and trace outside the repository or explicitly ignored
   in fixture setup; arbitrary untracked work must retain boundary admission.
5. Exercise committed, rename/deletion, existing-content drift and invalid paths
   or runtimes covered by the normative contract.
6. Prove concrete HTTP and monetary/DB boundary obligations from shipped policy.

Initial public CLI and independent command expectations are suitable. Await
revised RED before implementation; bootstrap manually enumerated obligations
are explicit because the planner does not exist yet.

## Revised Gate 3 - APPROVED

Root independently inspected revised spec/test and ran the public test file. Six tests / 25 failed assertions, all because the copied public planner CLI does not exist; positive fixture reaches intended missing behavior. Negative assertions also lack their future SETUP_FAILURE output. Implementation is absent.

Acceptance spec binding and multiple acceptance IDs, executable category requirements, runnable source drift, external fake interpreter, git/content drift and concrete boundary commands are covered. The shipped policy mapping itself remains a Gate 5 integration review obligation. Explicit mappings require independent completeness review; no natural-language extraction is claimed.

Reviewed SHA-256:
- specs/CHANGE-VERIFICATION-001.md: `f252282dcf086894351ad8b41da374f841806c8d5a98646067c39ff8c02ff673`
- tests/Verification/change_verification_001_test.py: `73d0e038a8aac1b4c4d43aa6b404b04b08b550fb317463a3725a2c77a8ea4319`

## Supplemental Gate 3 after Gate 5 findings — APPROVED

The independent Gate 5 review found that aggregate required categories could
mask a wrong test/category binding and that the shipped generic test boundary
selected a DB-backed integration command for a governance-only tooling test.
The implementation author amended only the normative specification and public
contract test before correcting production code or policy.

The revised assertions are traceable and sensitive: one introduces a second
governance boundary while deliberately misclassifying the HTTP boundary test,
proving that unrelated aggregate coverage cannot satisfy its exact binding; the
other imports the shipped policy and inventory into an isolated git repository,
checks concrete HTTP, persistence and money commands, and rejects the unrelated
storage command for a governance-only test change. Expected argv values come
from the specification and repository inventory, and no production DB or shell
execution is involved.

Independent RED on 2026-09-09:
`python3 tests/Verification/change_verification_001_test.py` -> exit 1; 8 tests,
6 pass and exactly 2 intended failures:

- `test_exact_category_binding_cannot_be_masked_by_another_boundary`: planner
  incorrectly returned 0;
- `test_shipped_policy_has_concrete_boundaries_and_bounded_governance_focus`:
  plan incorrectly contained `php tests/Runtime/runtime_storage_001_test.php`.

Reviewed pre-implementation SHA-256:

- `specs/CHANGE-VERIFICATION-001.md`: `f20cad3cbedc162e4c727af782f0a3f609b456ecd0e81b7db730cb3b2b9db1dd`
- `tests/Verification/change_verification_001_test.py`: `f4e8ffa3dd7e6568f6f4a7cd4d7a572bd301e85a47be5aacfd9c0da799f37345`
- unchanged `tools/delivery/change-verification.py`: `34f60eb3c9026c8512b8ca87c0409c304cf9e8279983568eee634c07dced2429`
- unchanged `.quality-graph/verification-policy.json`: `cf6f80ba0a34155ddf7fd8eb97e2a0f7d48bbeb875ebc9ca6709b7a45feea15a`

Verdict: **APPROVED** to correct the two independently reproduced failures.

## Supplemental Gate 3 after correction review — APPROVED

Reviewing the first correction exposed a separate normal-use gap: a registered
acceptance test could be executable yet rejected when its inventory category was
not already selected by the changed source boundary. The amended contract now
requires an existing mapped acceptance test to contribute its exact inventory
category; future tests absent from inventory remain valid pre-Gate-2 mappings.

The new isolated test imports the shipped policy and inventory, maps an ordinary
`app/IdentityAccess/**` change to the existing integration storage contract, and
requires both a successful plan and `integration` in `required_categories`. This
directly detects the reported failure without a database or private method.

Independent RED on 2026-09-09:
`python3 tests/Verification/change_verification_001_test.py` -> exit 1; 9 tests,
8 pass and exactly one intended failure,
`test_registered_acceptance_adds_its_inventory_category`: `SETUP_FAILURE: test
category omitted: tests/Runtime/runtime_storage_001_test.php requires integration`.

Reviewed pre-implementation SHA-256:

- `specs/CHANGE-VERIFICATION-001.md`: `77f96f328369523879b5f60fa741834030f2f3ad8279bd17ed5d8a04958c7266`
- `tests/Verification/change_verification_001_test.py`: `daa0a19443b490b0ead6ac7881021c89bdaeb38e77ee99092a643c047796a5c6`
- unchanged `tools/delivery/change-verification.py`: `033d822018916d3c11c15dc88ec6e577f1e16edf6fdc37379b5f85c4dab6bd56`
- unchanged `.quality-graph/verification-policy.json`: `83c751d71c9225a5a330fc0494b3d0c0188800cdcbf4535e67611f6ac2317675`

Verdict: **APPROVED** for the minimal acceptance-category inference correction.

## Supplemental Gate 3 for changed registered tests — APPROVED

The real #78 plan showed that a changed registered verification test contributed
only its generic boundary category and was not itself executable in the focused
plan. The amended requirement makes every effective registered test contribute
its exact inventory category and runtime argv, with normal command de-duplication.

The new test commits a registered verifier change in the isolated repository,
maps the acceptance to a different future test, and requires the changed verifier
argv exactly once. This separates actual-path coverage from acceptance mapping,
uses the public CLI, and needs no production database.

Independent RED on 2026-09-09:
`python3 tests/Verification/change_verification_001_test.py` -> exit 1; 10 tests,
9 pass and exactly one intended failure,
`test_changed_registered_test_schedules_itself`: the expected
`python3 tests/Verification/verification_inventory_001_test.py` argv is absent.

Reviewed pre-implementation SHA-256:

- `specs/CHANGE-VERIFICATION-001.md`: `2b2f87ffad995f63cb55f9f21c39768d5e17c87618236a36d02507f0351ecc90`
- `tests/Verification/change_verification_001_test.py`: `34b68f0b4553cf747e867ae368359baef3c6058831b48c3b6db491e6bad7ae61`
- unchanged `tools/delivery/change-verification.py`: `f677f3ea1434d79e5db0813e201f8fc731d04aaddde0d933e95682e561eadc7d`
- unchanged `.quality-graph/verification-policy.json`: `83c751d71c9225a5a330fc0494b3d0c0188800cdcbf4535e67611f6ac2317675`

Verdict: **APPROVED** for the minimal registered-test scheduling correction.
