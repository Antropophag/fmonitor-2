# Code review: HARNESS-OTIZ-ISOLATION-001

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/otiz_isolation_code_review`
- Independence: this reviewer did not author the specification, approved test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `specs/HARNESS-OTIZ-ISOLATION-001.md`, version `0.1`
- Approved test review: `reviews/tests/HARNESS-OTIZ-ISOLATION-001.md`, verdict `APPROVED`
- Production artifact: `rapid-pilot/verify-otiz-workflow.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

Specification conformance passes. The verifier now owns a complete prefixed fixture family for identity, authorization, installation case, migration provenance, object identity, registered order, installer membership, checklist template, checklist operations, and object-card evidence. All request workers receive the same private prefix and configured disposable-database credentials. The approved isolation test ran the verifier twice with plausible unprefixed native decoys present; both runs emitted the identical transcript and the test fingerprinted every decoy table definition and row before and after each run.

The blocker transition is explicit and correctly ordered. The initial `2026-08-31` calculation has no checklist-completion operation, acceptance leaves its snapshot in `draft`, and only then does the verifier insert the literal versioned `item_completed` operation and installer evidence. The later `2026-09-15` calculation is accepted. The successful transcript contains the normative `calculation creates a deterministic draft` → `open blockers prevent acceptance` → `blocker-free draft can be accepted` sequence in order.

Expected-value independence and scope pass. Fixture IDs, dates, template share, object-card fields, evidence UUID, installer ID, and timestamps are literals. Payload SHA-256 values are integrity metadata calculated from those literal payloads, not expected premium amounts derived from implementation output. The diff changes no `RapidPilotOtiz`, `NativeOperationalPremiumInputs`, calculation formula, acceptance/payment meaning, authorization policy, or release behavior; it is confined to database configurability, private fixture setup, explicit evidence insertion, and cleanup inventory in the characterization verifier.

Cleanup passes on success and failure. The approved test observed no private tables after either normal run. For an additional safe probe, the reviewed source was evaluated in memory with the deterministic-draft assertion replaced by an injected failure; it exited `255`, its `finally` block ran, and a catalog query returned `[]` for `otiz_verify_%`. The source file itself was not modified. The complete owned-table list includes both explicitly created native tables and every table bootstrapped by OTIZ and migration-evidence adapters.

Determinism and regression sensitivity pass. The two-run characterization transcript SHA-256 and the separately executed direct-verifier transcript SHA-256 were all `addd46ec1c19987f6e6e9c966ca79a7f440be4569f010604c1f24876b78c65e8`. Ambient table metadata and binary row state remained unchanged with fingerprint `2bc29bf6858acd78b0eca5729bdfc9536b020b7e5d30c91e2d9a643897f8f586`. The approved test requires exact ordered milestones, zero process statuses, byte-identical transcripts, byte-identical decoys, a clean namespace before the second run, and zero leaks after both runs; it would catch fixture omission, ambient reads, reordered or bypassed blocker behavior, nondeterministic output, and incomplete cleanup.

## Verification evidence

All commands used the disposable test database at `127.0.0.1:23306/fmonitor2_test` where database configuration was required.

```text
php tests/Verification/harness_otiz_isolation_001_test.php
PASS — two identical runs; transcript sha256 addd46ec1c19987f6e6e9c966ca79a7f440be4569f010604c1f24876b78c65e8
PASS — no private-table leaks; ambient decoy sha256 2bc29bf6858acd78b0eca5729bdfc9536b020b7e5d30c91e2d9a643897f8f586

php rapid-pilot/verify-otiz-workflow.php
PASS — 19 characterization assertions; transcript sha256 addd46ec1c19987f6e6e9c966ca79a7f440be4569f010604c1f24876b78c65e8

in-memory injected assertion-failure probe
PASS — verifier exit 255; post-failure information_schema query: []

php -l rapid-pilot/verify-otiz-workflow.php
PASS — no syntax errors

php -l tests/Verification/harness_otiz_isolation_001_test.php
PASS — no syntax errors

make architecture-check
PASS — ARCHITECTURE CHECK PASSED (6 rules)

git diff --check -- rapid-pilot/verify-otiz-workflow.php
PASS
```

## Reviewed hashes

```text
8952624654b5ef1c2006af0fbcf2ba090c3a8c8cc8c7e817f01d4565cf25e978  specs/HARNESS-OTIZ-ISOLATION-001.md
d39afb092702619249372376568d04e7708fce4d2f74a901546c579772e08249  tests/Verification/harness_otiz_isolation_001_test.php
19226d76b864d9e0a5f2a6e58bec9a3f0efa0155f7e85d858d7535726fd3598d  reviews/tests/HARNESS-OTIZ-ISOLATION-001.md
dfd21296c5ca0cfe8cf09c43311384a9716d79f5be73b2060916a8e678f0d556  rapid-pilot/verify-otiz-workflow.php
```

Gate 5 is approved for the reviewed bytes.

## Integration wiring review

- Reviewer: separately tasked agent `/root/otiz_isolation_runner_review`
- Independence: this reviewer did not author the specification, approved test, implementation, or runner wiring
- Scope: final `tools/verification/run.sh` characterization-suite wiring
- Date: `2026-09-01`
- Verdict: `APPROVED`

No blocking findings. The characterization inventory contains one OTIZ suite entry, `tests/Verification/harness_otiz_isolation_001_test.php`, and no direct `rapid-pilot/verify-otiz-workflow.php` entry. This is not coverage loss: the approved meta-test invokes that exact public verifier twice, requires both child statuses to be zero, requires byte-identical stdout, checks all eight ordered stable milestones in both transcripts, checks a clean private namespace before the second run, and checks zero private-table leaks plus unchanged ambient decoys after each run. There is therefore neither a duplicate third execution nor a hidden skip of the characterized OTIZ workflow.

Failure classification remains explicit at the appropriate seams. Database connection failure in the meta-test emits `SETUP_FAILURE` and exits `2`; verifier process-start failure is also labelled `SETUP_FAILURE`; missing milestones or nonzero verifier behavior runs are labelled `RED_ASSERTION`. The shared suite runner continues to classify any nonzero characterization entry as `REGRESSION_FAILURE`, exactly as it does for every file passed through `run_files`, without suppressing the more specific child evidence.

Verification evidence:

```text
bash tools/verification/run.sh characterization
PASS — all characterization entries completed; the OTIZ meta-test reported two identical verifier runs
PASS — OTIZ transcript sha256 addd46ec1c19987f6e6e9c966ca79a7f440be4569f010604c1f24876b78c65e8
PASS — no leaked private tables; ambient decoys unchanged
PASS — subsequent premium-calculation and visual-contract entries ran, excluding early exit or hidden tail skip

rg -n "verify-otiz-workflow|harness_otiz_isolation" tools/verification/run.sh tests/Verification/harness_otiz_isolation_001_test.php
PASS — one suite-level meta-test entry; exactly two explicit child verifier calls are made by the meta-test execution path

git diff --check
PASS
```

Reviewed hashes:

```text
4247a92b4dfd419d13985f888bc1ed2fa77754e735b0c88c83cee01a38e1cbc8  tools/verification/run.sh
d39afb092702619249372376568d04e7708fce4d2f74a901546c579772e08249  tests/Verification/harness_otiz_isolation_001_test.php
dfd21296c5ca0cfe8cf09c43311384a9716d79f5be73b2060916a8e678f0d556  rapid-pilot/verify-otiz-workflow.php
```

The final characterization-suite wiring is independently approved for these reviewed bytes.
