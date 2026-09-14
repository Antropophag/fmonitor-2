# Yii2 clean stand acceptance runbook

Этот runbook относится только к exact owner-authorized disposable target. Он не переключает production traffic, не удаляет старый stand и не запускает backup/restore/reconcile/rollback.

## Package

Outer JSON `fmonitor-clean-stand-outer-authorization-v1` хранится вне checkout, mode 0600, и содержит только exact bindings/references: `operationId`, `authorizationDecisionDigest`, expiry, 40-hex source commit, immutable image digest, production Compose/acceptance override/context absolute paths и SHA-256, exact future project/database/container/network/volume names, `expectedAbsent: true`, forbidden production/neighbor resource IDs, private credential-file references, runtime configuration, allowed effects и external evidence root. Secret contents в package отсутствуют.

Перед первым effect оператор повторно проверяет SHA-256 package и вызывает:

```sh
FMONITOR_CLEAN_STAND_AUTHORIZATION_PACKAGE_SHA256=<exact-sha256> \
php tests/Support/yii2_clean_stand_acceptance.php run \
  --authorization-package=<absolute-package-path> \
  --authorization-package-digest=<exact-sha256> \
  --evidence-root=<absolute-private-evidence-root>
```

Public seam сначала доказывает expected absence/non-overlap, затем создаёт exact Compose project и повторно attest-ит actual container/network/volume IDs, labels и image до DB/bootstrap. Далее он вызывает canonical prepare/migrations/initial-admin, acceptance-only synthetic setup, production runtime/jobs, HTTP golden flows, jobs/outbox observations и mounted include probe. Только полный результат атомарно публикует `accepted.json` с `CLEAN_STAND_ACCEPTED`.

Recording mode публикует только `contract-verified.json`; он не является real acceptance. Любой missing/mismatch/partial/UNKNOWN блокирует deployment acceptance. Cleanup или production cutover этим command не разрешены.
