# Gate 3 review: PILOT-HEALTHCHECK-SESSION-001

- Reviewer: independent separately tasked agent `/root/health_review`; did not author these tests or implementation.
- Date: 2026-09-07.
- Reviewed base commit: `e1d3a64824395c96ceb47827b21e114dc4c60076`; working-tree artifact hashes below.
- Specification: v0.1, independently approved in `PILOT-HEALTHCHECK-SESSION-001-gate1-v1.md`.
- Public seam: subprocess invocation of `sh rapid-pilot/healthcheck.sh` against an isolated PHP HTTP fixture using real LocalAuth/native session ownership.
- RED command: `python3 tests/Verification/pilot_healthcheck_session_001_test.py`.
- RED evidence: external local diagnostic `health-red.log`, SHA-256 below. Read directly; not rerun by this reviewer.
- Verdict: **CHANGES_REQUESTED**.

## Findings

The retained RED is valid for the intended missing seam: `PASS healthy fixture; predecessor creates additional sessions`, followed by `INTENDED_RED first bounded CLI succeeds`, expected exit 0 but observed exit 127 because `rapid-pilot/healthcheck.sh` does not exist. The fixture successfully executes the existing anonymous lifecycle before this assertion. Expected exit values and twenty-run final native-session inventory equality come from the specification. Inventory hashes observe the specified persistence effect without using a private method as the action. Both protected paths have independent 503 cases, and success cannot require a database-backed authenticated identity.

The review found two material coverage/isolation issues:

1. The `external` fixture redirects to `http://192.0.2.1/out`. An implementation that follows this prohibited redirect and then times out still passes the exit-1 assertion. This case is insensitive to loopback confinement and permits an actual non-loopback network attempt during a supposedly isolated test. Replace it with controlled evidence that rejects before a prohibited outbound request; use a deterministic transport observer/guard or equivalent isolated harness. Keep the real native-session fixture for the core behavior.
2. The approved storage contract explicitly rejects unsafe healthcheck directories and lock files as well as cookies. Current tests cover root/cookie symlinks and cookie permissions only. Add healthcheck-directory and lock symlink/permission cases, missing/non-directory roots, and verify rejected pre-existing artifacts are not repaired or mutated. These are separate plausible filesystem regressions, not implementation-mirroring checks. Foreign-owner coverage may use an appropriately isolated supported fixture or explicitly document a verified platform constraint; no privileged host mutation is requested.

## Other review notes

- Exact max-3 redirect behavior should be checked with finite redirect chains (three succeeds, four fails); an endless loop cannot distinguish a limit of 3 from a larger limit or deadline-only stopping.
- The stale-cookie case supplies a valid-format absent session identity and exercises existing login recovery. Session-reuse assertions following that recovery would also catch retaining the stale jar forever.
- Requiring both output streams to be completely empty is stricter than the normative prohibition on cookie/body output. Empty output is compatible with the contract, but the review does not interpret benign secret-free error messages as a product-level violation.
- The bounded network deadline and contention tests have reasonable scheduling tolerance. Resource cleanup terminates/reaps the PHP fixture and removes its temporary directory. No production implementation or remote action was performed for this review.

## Required changes

Address findings 1 and 2 and make the redirect-limit assertion sensitive to the exact approved bound. Capture RED for the revised test artifact and obtain a new independent Gate 3 verdict before implementation.

## SHA-256

| Artifact | SHA-256 |
| --- | --- |
| `specs/PILOT-HEALTHCHECK-SESSION-001.md` | `393fe89622bc0247d3c40eda045edaff22d7f7eeefe5d4f1d3b57be27baf47f6` |
| `tests/Verification/pilot_healthcheck_session_001_test.py` | `e5a26c1262b7393dec69cb5cc9710823dc7e9dd983cb1a4e0ea8630607ff0d94` |
| `tests/Support/pilot_healthcheck_session_router.php` | `97c78add9def19e5a5d58f0057566d8d20bc9aadcadc0c874cd5226ec7384e90` |
| external diagnostic `health-red.log` | `ee4978a73f8c148576f2bcebdfa8f8fa8be77b0e574d09071c3c55eb843d76cf` |
