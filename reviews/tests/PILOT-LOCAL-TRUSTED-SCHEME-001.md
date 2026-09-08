# Test review: PILOT-LOCAL-TRUSTED-SCHEME-001

- Reviewer: `/root/original_gate3`, independently tasked agent; not author of spec, test, fixture amendment or configuration implementation.
- Test author: root implementation agent.
- Reviewed source: `a5419effa8a2da44edd216da0f5fc99d64bd978c`, with the test-only additions identified below; Compose configuration unchanged.
- Specification: v0.2, final Gate 1 v3 APPROVED.
- Public seams: actual Docker Compose effective configuration and real native-session router GET `/pilot/admin/users`.
- Verdict: `APPROVED`.

## Findings

Read specification, Gate 1 v3 record, new test, three-line fixture amendment and operational RED record. Effective configuration is obtained through real `docker compose config --format json` using synthetic bootstrap values and `/dev/null` envfile. The deliberately conflicting ambient trusted scheme is https; the expected literal http and exact loopback port tuple independently derive from the approved local profile. The native server receives the actual extracted Compose value, so a separate hard-coded test default cannot hide missing configuration.

Native session and roles-page prerequisites distinguish Users503 from broken authentication, authorization, directory/CSS or setup. The synthetic actor receives the exact existing access.administer permission during fixture setup, before baseline capture. The tested GET must return the Users page with 32-hex action tokens; the public session-storage owner then reads committed payload and checks every rendered token belongs to the actual administrator and a real fixture user. A repeat GET must work. Full database rows and original private-file hashes are compared across the operation; ordinary successful session-token writes are intentionally permitted by the specification.

The negative path removes the trusted scheme and sends a forged forwarded-http header. It requires exact 503/body and preserves all previously observed session-file hashes, as well as database/original storage state. This is sensitive to an unsafe header-derived/default scheme or token publication on failed admission. Backend validation remains inherited; the slice requires no new session or authorization behavior.

Fixture change uses ordinary `env KEY=` arguments solely for explicitly empty environment entries before launching the same native PHP/router command. It preserves the supplied empty prefixes and scheme instead of reconstructing an application or intercepting native calls. Existing environment values, routes, storage and session ownership remain otherwise unchanged. The recorded original-upload HTTP flow regression passes after this fixture amendment.

No blocking test findings. Minimal one-line Compose binding may proceed, followed by actual native GREEN and independent Gate 5. Operational recreation/preservation checks are separate subsequent obligations: this test does not prove old-image recreation, sentinel nonce preservation, existing preview sessions, full verification or launch readiness.

## RED evidence

Root command: `php tests/InstallationProcess/pilot_local_trusted_scheme_001_test.php`, using the documented explicit synthetic MariaDB environment. Inspected `/Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907/red-v3.log`: both fixtures have healthy native login/directory setup; effective-config Users GET is 503 instead of 200; missing-scheme/forwarded-header control PASS; both CLEANUP_OK; exit 1. `fixture-regression.log` records direct-original/replay/correction/historical-binding/no-apply-or-open PASS.

Earlier missing-runtime and empty-env roles404 failures are excluded setup attempts, not accepted RED. Reviewer inspected output and artifact identities rather than claiming another execution. Only this review record was written.

```text
636ce90585b4e5472a97349303a11f0d0c91f17c43bce87f5d8737f2787c58f3  tests/InstallationProcess/pilot_local_trusted_scheme_001_test.php
820bfe30bd8415642ccbbd949829e66168e2559e5b2a6ba73a7c055f25d09a59  tests/Support/SelectionHttpFixture.php
695fb7328545c0fae5e0430bb23237ff34e6fb3e97089484fe74b48c4b3e3a63  compose.yaml
91eec5258b9d55b31fc4fc3688338b3c26c4dffa886b0569d9be43cc62ee3bc6  specs/PILOT-LOCAL-TRUSTED-SCHEME-001.md
```
