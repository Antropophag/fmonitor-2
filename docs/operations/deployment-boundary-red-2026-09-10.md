# Deployment verification mapping — root-authored correction

The required OTIZ runtime dependency plan stopped with
`SETUP_FAILURE: unknown or ambiguous boundary: deploy/runtime/Dockerfile`.
The repository policy omitted deploy/ entirely. The fix must classify this known
runtime boundary, not bypass or relax unknown-path failure.

Separate prerequisite input/plan (ignored local artifacts):
`.local/verification/deployment-boundary-input.json` and
`.local/verification/deployment-boundary-plan.json`. This input binds only the
policy and its regression test, so it can be generated before the missing
runtime mapping is implemented. Its required obligations were read before RED.

Root added a public-CLI test in a disposable Git repository using the real policy,
with fixture test files/inventory. It plans retained runtime Dockerfile/compose and
Yii Dockerfile; expects dependency-or-runtime, unit/governance and full make test.
No production files or policy were changed by the test author.

Command:
`python3 tests/Verification/change_verification_001_test.py ChangeVerification.test_repository_policy_maps_deployment_sources`

Actual exit1, intended assertion:
`INTENDED_RED: deployment must have an explicit verification boundary:
SETUP_FAILURE: unknown or ambiguous boundary: deploy/runtime/Dockerfile`.
Existing tests still retain all generic unknown/ambiguous refusal cases.
