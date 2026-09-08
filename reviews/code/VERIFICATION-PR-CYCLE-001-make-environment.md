# VERIFICATION-PR-CYCLE-001 — corrective Gate 5 make-environment review

- Gate: 5 — corrective implementation review
- Reviewer: separately tasked agent `/root/cycle_review`
- Independence: reviewer did not author the amended specification, test, implementation, or evidence
- Fixed point: `7d1f6894795e43a03916f32ef3d91d006101d9bf`
- Reviewed head: `86875bb524aba9fe26f837dd9da2c7e4c155bf53`
- Date: 2026-09-08
- Verdict: **APPROVED**

No finding remains in the bounded corrective diff.

`run_category` copies the existing process environment once, removes exactly
`CATEGORY`, `MAKEFLAGS`, `MFLAGS`, and `MAKEOVERRIDES`, and passes that environment
to every selected test subprocess. Category selection remains the explicit Python
argument and is therefore unaffected. Other runner defaults and caller-provided
test configuration remain present. Placing the boundary in `ci.py` covers both
`make test CATEGORY=...` and direct `ci.py run ...` callers, which is the correct
owner of child test execution.

The approved test is unchanged from Gate 3 v3. Its deliberately contaminated
parent environment now reaches neither the fake PHP runtime nor its nested make,
and the nested target executes once. The complete real governance category also
finishes all seven members, including the CI and inventory Python contracts and
both Make harness contracts; this is direct evidence that the observed recursive
hang is removed without dropping the affected tests.

## Evidence

```text
d69492bd2c741285dbefc771dc1ffce59f802eca6126015b99c059842ac8236a  /tmp/fmonitor25-make-env-green.log
a7238086b65ab590773d68b81c69d72832d114328b17e183435f22ae09a8079c  /tmp/fmonitor25-make-governance-green.log
02338efd16412c172c2f6ebd56bca23122fc31c64721ef0ad464e8189b4684fb  tools/verification/ci.py
72be6efd28902ccdf456d88545df6063b5a3d3a11bf516fa13d6439a0cba05c7  tests/Verification/verification_ci_001_test.py
3db577024f92eb1e19cfbb5c5647085710b21db2b8c473dc5439fbcd8b4fb969  openspec/changes/optimize-verification-pr-cycle/specs/verification/pr-cycle/spec.md
```

- Corrective contract: 1/1 pass in 0.859 seconds.
- Real governance category: 7/7 pass in 18.820 seconds.
- `git diff --check 7d1f689...86875bb`: pass.

Corrective Gate 5 is **APPROVED**. The previous cancelled CI run remains historical
failure evidence and cannot support merge readiness; a new exact-head Actions run
must complete through the terminal fail-closed `verify` job.
