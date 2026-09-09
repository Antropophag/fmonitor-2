# Gate 3 test review: OTIZ-SETTLEMENT-001 runtime compatibility dependencies

- Reviewer: runtime_review, independently tasked; did not author the specification, test, image implementation, or retained HTTP implementation.
- Reviewed test candidate: `8130bf8e4c5b33b3990053e4f5bf6c1ebbc3a8f1`.
- Public seams: built `deploy/runtime` image and retained HTTP routes through image-owned `public/runtime.php`.
- Verdict: **APPROVED**.

## Complete findings

No blocking or non-blocking findings.

The test traces the temporary runtime dependency contract through a real built
image. The child mounts only the repository tests, so `composer.lock`, Composer's
autoload/runtime metadata, application code, router, Yii2, and TCPDF must all be
owned by the image. It compares the image lock digest with the source lock,
compares installed Yii2 and TCPDF versions with their exact locked versions,
requires `mysqli`, `pdo_mysql`, and `pcntl`, and executes
`composer check-platform-reqs --no-dev`. A fake TCPDF loader or host vendor tree
cannot satisfy these assertions.

The downstream compatibility case crosses the retained public HTTP seam. It uses
the actual login sequence and rendered CSRF tokens, then submits the retained
complete-payment route with a canonical operation UUID. The database principal
used by the HTTP process receives only SELECT/INSERT/UPDATE/DELETE on the
disposable database. Given an immutable earlier 100000-cent closure and a later
accepted 150000-cent accrued snapshot for the same object, the test independently
requires a 50000-cent append, exactly one operation receipt and two events. It
also checks the earlier row byte-for-byte, exact replay response and zero new
facts, and the retained GET's completed-budget state with no misleading payment
form. Invalid CSRF is checked before success with zero facts.

The use of PHP's built-in server is limited to the test listener. The assertion
target remains the packaged router/application and public HTTP behavior; it does
not claim that this listener is the production FPM process model. Existing
nginx/FPM packaging and browser checks remain registered, so this focused slice
does not replace them or expand the compatibility contract.

The new test is registered consistently as `e2e` in the suite manifest, category
inventory, and inventory expectation. No existing suite entry or category is
removed.

## RED evidence

The recorded image build succeeded. The child then stopped before constructing a
database fixture:

```text
php tests/Runtime/runtime_settlement_compatibility_001_test.php
child exit 255
INTENDED_RED: runtime image carries the shared Composer lock
Expected: true
Actual: false
```

This is the intended missing image-content behavior. The old image contains its
synthetic TCPDF-only autoloader and no shared Composer distribution. The image and
container were cleaned up; the host's lack of a Composer executable is explicitly
excluded from the RED claim. The repository verification plan reports
`CHANGE_VERIFICATION_OK` after the independently approved deployment-boundary
policy correction.

Independent lightweight verification:

```text
php -l tests/Runtime/runtime_settlement_compatibility_001_test.php
No syntax errors detected
```

Reviewed SHA-256:

- `specs/OTIZ-SETTLEMENT-001.md`: `87f994a528712b0ce83f5e93e804924240363a31da304d2997238bdf7082e983`
- `tests/Runtime/runtime_settlement_compatibility_001_test.php`: `09a324c1dbf2510c620e5bb7528592cca75aab54acd8f18d180c266fe5069f32`
- `docs/operations/otiz-runtime-dependencies-red-2026-09-10.md`: `c8465a3b9bedc14eb8d83aef1c602b370ff0d067e3a1439733360230de200551`
- `tests/Verification/verification_inventory_001_test.py`: `03cfae2e3c7ddb9f97c8db65b8ec102a557816a7ffe893e712fcc530bad56f40`
- `tools/verification/categories.json`: `5dcb2098b285020ebec758f2be5bd323b7da2b06c2e97a3c220cf43d5aa5d37c`
- `tools/verification/suites.tsv`: `8c02d21e698eed292cd60000493e75354000c37a643f3231027e3929414b59ed`

Gate 4 may make the runtime image install the exact shared production dependency
set and apply only compatibility fixes exposed by this public flow. Gate 5 must
retain the production FPM model and prove no alternate financial writer is
introduced.
