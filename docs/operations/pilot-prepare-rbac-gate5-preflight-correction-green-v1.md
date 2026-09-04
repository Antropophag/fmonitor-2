# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 5 preflight correction GREEN v1

Дата: `2026-09-04`  
Исполнитель: separately tasked implementation agent `/root/prepare_v15_green`  
Base: `4955a0b89d7d1128a593159f43dfa0f1ba4033d6`  
Source verdict: Gate 5 preflight axes `CHANGES_REQUESTED`  
Verdict: **CORRECTION GREEN / fresh independent Gate 5 required**

## Corrections

- Удалено fixture-specific rewriting SVG path для `3099/4001/75/76`.
  Shared icon renderer теперь одинаково entity-encodes каждую decimal digit в
  SVG path data; decoded DOM/SVG geometry не меняется, а business identifiers
  не возникают как raw response substrings из presentation decoration.
- Prepare workforce read перенесён в SQL-owning
  `MariaDbPrepareWorkforceDirectory`. Удалены busy query, промежуточная sort и
  последующее отбрасывание результата; inert record всегда получает exact
  `data-busy=""`.
- Provenance вычисляется только из отсортированных eligible rows. Одинаковая
  pair выводится один раз над group; mixed pairs выводятся отдельно для каждой
  eligible строки вне six-field inert template.
- Picker order validator сравнивает массивы Unicode code points и только при
  равном name использует numeric ID.

Tests, test support, specs и reviews не изменялись.

## Verification

Focused run started `2026-09-04T10:22:07+03:00`:

```text
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
# exit 0, no output

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check
# exit 0, no output
```

The first combined run and one immediate isolated retry of
`pilot_http_auth_001_test.php` observed a transient live MariaDB process-list
row in its request-scope cleanup assertion (`130242`, then `130283`). A later
isolated run completed with the PASS literal above. No assertion or harness was
changed.

## Coverage limits

The approved verifier has no mixed-provenance successful fixture and no
supplementary-plane name pair whose UTF-16 order differs from code-point order.
Those two corrections are therefore supported by direct spec-to-code inspection
plus existing homogeneous provenance/parser regressions, not by new executable
assertions. Adding tests would invalidate Gate 3 and was explicitly out of this
production-only correction scope.

Production hashes at `2026-09-04T10:23:31+03:00` before this evidence commit:

```text
773926e7a03f2c3192401b079f04b18c90634fc63de473b8aa94ed77428068ee  app/PilotHttp/PilotHttp.php
312ba8e9da7530e92f192460c783dbbeb4f60ee12045dc263db60121665c7310  app/PilotHttp/PilotView.php
75cb00f2aee33bf81e71dadbdd730d19e4571847e1d01b0b5d9103de9f23412b  app/PilotHttp/PrepareFormView.php
3658e9aa3b7b72664ad6049d2152bde694434f5b9c87223a3605c4e593c08191  app/PilotHttp/picker.js
ec246d65f3f17a314eb214ce97f03352e307a9035a86a34731131f454b1eca9e  app/PilotHttp/MariaDbPrepareWorkforceDirectory.php
```

This record does not claim Gate 5 or integration readiness.
