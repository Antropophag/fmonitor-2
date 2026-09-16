# Test review: CANONICAL-INTEGRATION-RUNTIME-001

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/cir001_gate3`); authored neither the specification nor tests.
- Reviewed source: base `992e32f103b55a968d2744d0918491257b8090d0` plus retained snapshot patch; exact candidate source `9cd7531d7787fc3e00a3e28b21966eaeaeacb2cf5869e78f471294cf4535fac0`; executable source `9128392fbdc549fe4a4d1ae05fd10bfbfe5447a24b89e5eed9b6fa6f00122796`.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T154754Z-86e49fa955/package.json`; plan SHA-256 `aa96deb3179889cf1dd67f33f63f149c743e063f0e6cda7782140670a9d1cc01`; context manifest SHA-256 `4a0ae5c76e2e3796cc88ee6416cffee54af71c87c8bbcaf9ec59903f6f15c1a4`; required-context SHA-256 `181be17f531295273136d0ff30be0319fb13e3818f6a26450c6fd4a415c01d40`.
- Reconstructible source: package snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T154754Z-86e49fa955/snapshot`, base `992e32f103b55a968d2744d0918491257b8090d0`, patch SHA-256 `f26984b70c937117da50ef087d1fcfe8df63694ea9ebf488ef57f0ed15e3c08a`.
- Normative specification: `specs/CANONICAL-INTEGRATION-RUNTIME-001.md`, package-bound SHA-256 `94010d3aeef6eb9abfcbf43f61c32114bc1637cf483f79b35286aad7b4c78b7a`.
- Executable contract: `tests/Verification/canonical_integration_runtime_001_test.py`, package-bound SHA-256 `f19a2c50da0bebf2825ef0428ce28d903bf9a7eb103d6d340822d8c4a979aa34`.
- Public seam: `tools/delivery/run-in-profile integration <command> [args...]`.
- Planner: `CRITICAL`; required reviews `gate3`, `final`; required categories `governance`, `integration`.
- Verdict: `APPROVED`.

## Evidence reviewed

- `python3 tests/Verification/canonical_integration_runtime_001_test.py` — exit `1`, recorded `INTENDED_RED` for the exact candidate source in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789573555014518000-9f27c5f2374d4cc1b0c977cb65fce3eb.json`. Its retained stderr is `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/stderr/1789573555014518000-9f27c5f2374d4cc1b0c977cb65fce3eb.log`.
- The RED completes disposable MariaDB setup and reaches the public profile. The container payload reports `extension=false`, drivers `['sqlite']`, PHP `/usr/local/bin/php`, Yii `/workspace/vendor/yiisoft/yii2/Yii.php`, autoloader `/workspace/vendor/autoload.php`, and the expected executable-source digest. The profile exits `42` at `INTEGRATION_PDO_MYSQL_MISSING`; this is the intended absent-image-driver behavior, not fixture/setup failure.
- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `0`, `GREEN`, same exact candidate and executable source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789573555014505000-dd57bdaa6fd44a4ba0c5aa19b7047e35.json`.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `0`, `GREEN`, same exact candidate and executable source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789573555014525000-2e5e2cabd62f4c919ac6cd56876cb890.json`.
- No full local `make test` or `make verify` was run. This review used the retained exact-source focused evidence and source inspection.

## Acceptance mapping and quality assessment

- CIR001-01 is directly sensitive to both `extension_loaded('pdo_mysql')` and `mysql` in `PDO::getAvailableDrivers()`. It proves container execution through `/usr/local/bin/php`, container-owned Yii/autoload origins, a 64-hex executed-source value, integration-profile compact evidence, exit status and source-digest agreement. The retained RED demonstrates that removing/omitting the image driver fails at this exact assertion.
- CIR001-02 invokes the specified DB-backed Yii command with the exact argv and requires exit `0`, existing `PASS: YII2-USER-ACCESS-001`, no `could not find driver`, integration compact evidence and the same source identity. Its expected application result comes from the pre-existing Yii test contract, not the planned Dockerfile change.
- CIR001-03 is covered jointly by the new test's `/workspace` origins and before/after host dependency inventory, and the package-selected #123-A regression. The latter independently validates frozen-source/image identity, read-only composition, hostile host vendor rejection/preservation, corrupt dependency fail-closed behavior and K/L/M profile origins. No host PHP or host vendor can satisfy the new probe.
- CIR001-04 uses a randomized Compose project and an OS-selected loopback host port, explicitly rejects the known `23306` default, starts/waits for only the disposable external fixture, and cleans its volumes/orphans in `finally`. The public launcher still receives only the Compose environment; the existing #167 regression independently proves conditional attachment to the declared existing network, `test-db:3306`, missing/stopped-service non-ownership, and unchanged network/service identity.
- CIR001-05 is explicitly witnessed by the GREEN #123-A K/L/M suite and GREEN #167 quality-graph setup suite on the same exact source. Their expected results and identities are independently derived rather than copied from the new implementation plan.
- Determinism and isolation are adequate for this bounded infrastructure test: unique Compose project identity, dynamically selected host port, ready-service wait, immutable/read-only container composition, bounded cleanup on success/failure and host dependency inventory checks. The close-before-Compose ephemeral-port interval can surface only as an explicit setup failure, not a false behavior GREEN.
- The combined matrix is sensitive to the public seam and material adjacent regressions. It does not alter product facts, schema, authorization, audit/history, application expectations, runtime versions, launcher ownership or production topology.

## Independently determined expected results

1. Before implementation, the unchanged focused-check image must complete DB setup, load PHP/Yii from `/workspace`, report no `pdo_mysql`/MySQL PDO driver and exit nonzero with `INTEGRATION_PDO_MYSQL_MISSING` before application behavior.
2. After the minimal image capability change, the same probe must report container PHP `/usr/local/bin/php`, `/workspace` Yii/autoload paths, `pdo_mysql=true`, a `mysql` PDO driver, matching exact-source evidence and exit `0`.
3. With ready disposable MariaDB, the unchanged Yii user-access command must emit its existing pass marker and exit `0`; a 503, missing-driver exception, host-origin dependency or mismatched source is failure.
4. Changing the host port must not change the in-container endpoint, image/source identity or launcher lifecycle ownership; K/L/M and #167 regressions must remain GREEN.

## Findings

None.

## Required corrections

None. Gate 3 advances this exact source to implementation. Any specification/test change or newly discovered scope requires a refreshed package and independent review of the changed delta.
