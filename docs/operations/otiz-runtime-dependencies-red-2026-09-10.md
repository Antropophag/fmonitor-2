# Retained runtime dependency boundary — root-authored RED

Contract: OTIZ-SETTLEMENT-001 temporary runtime compatibility dependencies.
The existing production-runtime image must include the exact shared Composer lock,
real Yii/TCPDF dependencies, Composer platform validation and mysqli/pdo_mysql/pcntl.
The retained public HTTP adapter must reach the same financial owner under DML-only
credentials; this is required for the new compatibility wiring, not a stand switch.

Test: `php tests/Runtime/runtime_settlement_compatibility_001_test.php`.
It builds deploy/runtime, mounts **only tests**, and checks image-owned lock/vendor,
installed locked versions, extensions and `composer check-platform-reqs --no-dev`.
The downstream test starts a disposable test HTTP listener at public/runtime.php,
uses actual retained login/CSRF, and completes an accepted snapshot with earlier
cross-snapshot100000 paid and accrued150000. Only50000 and one receipt/two events
may append; exact replay adds nothing, old closure is unchanged, and the return
screen must show the global budget completed. This test listener is not the
production process model; existing nginx/FPM packaging/browser tests remain.

Actual RED: image built successfully, child exit255 on:

```text
INTENDED_RED: runtime image carries the shared Composer lock
Expected: true
Actual: false
```

Full output: `/tmp/fm2-root-runtime-dependencies-red.log`. The old image has the
TCPDF-only synthetic autoloader and lacks the shared Composer distribution.
No DB fixture was created before this packaging assertion. Test-owned image and
container were removed. A host-only diagnostic could not run Composer because
there is no Composer executable on host PATH; that is not claimed as runtime RED.

The accepted plan initially rejected deploy/runtime as unknown. A separate
root-authored public-CLI regression and independently approved policy correction
64dddf3b added deploy/** to the existing dependency-or-runtime boundary; unknown
unmapped paths still fail closed. The complete OTIZ plan now resolves normally.
The new smoke test is registered as e2e; no existing suite/category is removed.
