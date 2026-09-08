# Code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 dynamic ports v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation: `738b9adf00e6e808e0a89f5899ce5db412daa7e6`
- Implementation base: `621e7e83efdb0073086aba5f87ab955b2b1aaba7`
- Active parent specification SHA-256: `d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890`
- Approved Gate 3 rereview SHA-256: `f696f646db0097c2b6b3fa3b4f604baf5d46b3fca682137759675b2210ebcdc4`
- Approved test SHA-256: `4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86`
- Scope: command-v3 findings `G5-CMD3-01`, `G5-CMD3-02` and `G5-CMD3-03` only
- Verdict: **APPROVED**

The reviewer authored neither the implementation nor its tests/specification.
No production source, test or specification was edited by this review.

## Findings

No blocking finding remains in the scoped dynamic-port correction.

### Dynamic fingerprint unavailability

The real post-stream fingerprint lookup now explicitly handles
`AssignmentOrderOriginalLookupStatus::UNAVAILABLE` before the
`AFTER_FINGERPRINT_MISS_BEFORE_CAS` phase and before every ID, finalize, lease,
commit and domain operation. It returns retryable
`FAILED/PERSISTENCE_FAILURE` through the ordinary stage cleanup path. The
earlier empty-fingerprint availability probe remains, but no longer masks or
substitutes for failure of the later real-fingerprint call.

The approved test independently proves the exact real fingerprint was queried,
the earlier request-miss lifecycle event may exist, all three later lifecycle
events are absent, both ID call counts are zero, finalize/lease/commit/delivery
are absent, and the already created stage plus stream are cleaned exactly once.

### Exact ID API and bounded allocation

Runtime now declares the exact normative enum:

```text
GENERATED, COLLISION, EXHAUSTED, UNAVAILABLE
```

`AssignmentOrderOriginalIdResult` exposes the specified nullable `id` field.
No obsolete `FAILED` status or ID-result `identity` property remains in
production/verification ID-source call paths. The deterministic sequence source
returns `EXHAUSTED` when its configured list is consumed; the production random
source remains a valid always-generated source.

`AssignmentOrderOriginalPortValues::nextId` owns the total port boundary. It
catches source Throwable, rejects `UNAVAILABLE`, `EXHAUSTED`, unexpected status,
null and empty generated IDs, retries only `COLLISION`, accepts the first
generated nonempty ID, and stops after exactly eight collision responses.

Initial mode validates root first and returns immediately if it fails, so no
revision call occurs. It then validates revision. Correction preserves the
existing root and allocates only revision. Every failed allocation returns
retryable `FAILED/PERSISTENCE_FAILURE` before finalize, lease or commit and uses
the existing bounded stage cleanup. Successful collision-then-generated cases
use the generated value. The focused matrix covers null/Throwable/unavailable/
exhausted/collision/generated outcomes independently for initial root, initial
revision and correction revision, including exact call counts and all eight-
collision boundaries.

The helper is eagerly loaded by Runtime after the clock/ID interfaces and enum
are declared. Existing direct Runtime imports therefore receive the exact API
without an autoloader or alternate source implementation.

### Canonical clock validation

`AssignmentOrderOriginalPortValues::nowUtc` catches clock Throwable and first
requires exact `YYYY-MM-DDTHH:MM:SSZ`. It then parses with an explicit UTC zone,
requires no parser warnings/errors and round-trips to identical bytes. Missing
`Z`, spaces, fractions, offsets, invalid calendar/hour/leap-second values and
Throwable therefore return null.

The service handles null before assigning `$at`, Moscow conversion, stage or
stream acquisition and returns retryable `FAILED/PERSISTENCE_FAILURE`. Canonical
instants remain byte-identical as `uploadedAt`, and the two independent Moscow
boundary controls prove correct local calendar derivation without moving that
responsibility into the clock adapter.

## Independent verification

```text
$ php tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php
PASS ORIGINAL-DYNAMIC-PORTS (33 cases)

$ php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalPortValues.php
No syntax errors detected

$ php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
No syntax errors detected

$ git diff --check 621e7e8..738b9ad -- <two production files>
PASS (no output)
```

The focused test ran without database or filesystem fixtures and did not overlap
the parent's ongoing database regression run. All 33 approved cases passed with
unchanged test bytes.

## Exact reviewed hashes

```text
2108f10eac0e7face1c2095371ae0796de8ae825a1feb05f028b656b0cd5bf3f  app/AssignmentOrderOriginal/AssignmentOrderOriginalPortValues.php
d8ca5ba2f38d463b8f84b0e91b045412f5c625a0694f0fe51e037ed98453d00a  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86  tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php
c04f1e73636869adde1eff2d6d38a2af11900f16534a3291c48daa9faa65e4aa  tests/Support/AssignmentOrderOriginalDynamicPortsFixture.php
f696f646db0097c2b6b3fa3b4f604baf5d46b3fca682137759675b2210ebcdc4  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-dynamic-ports-v2.md
cf060e6196fbd9edacb523da35dd4a04f775391ce644132135a5e6ec205fc1ed  /Users/antropophag/.local/state/fmonitor2-verification/original-dynamic-green-kb9lfcbm/evidence.json (observed during parent regression run)
```

## Scope boundary

This approval closes only the three dynamic-port findings for exact implementation
`738b9adf00e6e808e0a89f5899ce5db412daa7e6`. The separately recorded lifecycle/
storage observer and command-shape audits remain pending blockers. This is not a
combined original-command Gate 5, full `VERIFY_OK`, deployment or launch approval.

This review omits its own circular hash.
