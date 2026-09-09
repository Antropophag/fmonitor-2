# Gate 3 test review: CHANGE-VERIFICATION-001 deployment boundary

- Reviewer: runtime_review, independently tasked; did not author the specification, test, policy, or implementation.
- Reviewed candidate: `b454f8a027d9c1e2f9d4df4774a862c6fed21220`.
- Public seam: `python3 tools/delivery/change-verification.py plan` with the current repository policy in an isolated disposable Git repository.
- Verdict: **APPROVED**.

## Complete findings

No blocking or non-blocking findings.

The supplement to `CHANGE-VERIFICATION-001` explicitly assigns retained-runtime
and Yii files under `deploy/` to `dependency-or-runtime`, requires unit and
governance obligations plus mandatory full CI, and preserves fail-closed handling
outside declared boundaries. The test traces those requirements through the
public CLI for `deploy/runtime/Dockerfile`, `deploy/runtime/compose.yaml`, and
`deploy/yii2/Dockerfile`.

The fixture copies the real repository policy and inventory, creates only the
files required to make their executable mappings structurally valid, and runs in
its own Git repository. It therefore detects an omission in the shipped policy
without using a private planner method or a production system. Requiring the
exact `dependency-or-runtime` boundary for every planned deployment path makes
the test sensitive to partial mappings and mappings to an unrelated boundary.
The independent category assertions require both `unit` and `governance`; the
exact integration command assertion retains the mandatory `make test` result.

The test does not weaken unknown-path behavior. Existing
`test_unknown_ambiguous_empty_and_bad_mappings_fail_closed` cases still require
nonzero `SETUP_FAILURE` and no plan for unknown and ambiguous boundaries. Here,
the inner CLI's current `SETUP_FAILURE` is the observable policy defect under
test, not broken test setup: the isolated repository, copied planner, policy,
inventory, acceptance mapping, and command fixtures all reach classification of
the first declared deployment path.

## Verification evidence

Independent focused reproduction:

```text
python3 tests/Verification/change_verification_001_test.py ChangeVerification.test_repository_policy_maps_deployment_sources
exit 1
INTENDED_RED: deployment must have an explicit verification boundary:
SETUP_FAILURE: unknown or ambiguous boundary: deploy/runtime/Dockerfile
Ran 1 test ... FAILED (failures=1)
```

The ignored prerequisite input declares only the policy and regression-test
production paths and maps `explicit-deployment-boundary` to this public seam.
The saved plan resolves `origin/main` to
`a6bafb1d4b6c2c9de524dd6637df914034eee9a2`, binds HEAD to the reviewed
candidate, includes governance/unit obligations and `make test`, and was reported
CHECK_OK after the candidate commit. A later independent `check` reports stale
because other authorized OTIZ files are now modified in the shared worktree;
that expected fail-closed response does not alter the reviewed post-commit plan
evidence.

Reviewed SHA-256:

- `specs/CHANGE-VERIFICATION-001.md`: `f54d9cba2b84ecc431b10682990b135a4e51e30498ea7f12c495300aab9fc57d`
- `tests/Verification/change_verification_001_test.py`: `19dff5d1fe266bf8779438e5353deb9642c93d13d16d14930861c36d19d4a1e0`
- `docs/operations/deployment-boundary-red-2026-09-10.md`: `b848557680bb0f458f4fa7ce49ad0cacbab3bd55d11fc7628bd3eb926e0c9218`
- `.local/verification/deployment-boundary-input.json`: `ea11684be46f6a94bdc731110820c910610520be9454d60f1df0ac1ecd3bbda7`
- `.local/verification/deployment-boundary-plan.json`: `fccaf39483f2fcba066d85436eff2adb2f75ccddf3ced4576471048433ade9f9`

Gate 4 may add the minimal explicit deployment policy mapping. Gate 5 must
confirm the production rule stays unambiguous and that an unrelated unknown path
still fails closed.
