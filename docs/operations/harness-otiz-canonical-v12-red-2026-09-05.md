# OTIZ prepared-v12 fixture RED и unapplied patch

Дата: 2026-09-05. Автор patch: `/root`.
Base: `063ae4e`; production/test bytes не изменены.
Technical Gate 1: `harness-otiz-canonical-v12-gate1-review-2026-09-05.md`,
APPROVED, spec SHA-256
`f0c2e1119ef37f149fc0355c5c6de36edc0ca6afe31f8e4655ccd8d0dd39afc2`.

После Gate 1 выполнен `make migrate`, exit0, exact stdout:
`{"ok":true,"schemaVersion":12,"appliedVersions":[]}` плюс LF, stderr empty.
Это independently prepared canonical prerequisite до test invocation; setup
не создаёт predecessor внутри failing test. PHP/Docker/rg/vendor исправны.

Команда `php tests/Verification/harness_otiz_canonical_compat_001_test.php`
дала exit255, intended existing fixture assertion:

```text
SETUP_FAILURE: migration JSON must report schemaVersion=11;
evidence={"status":0,"stdout":"{\"ok\":true,\"schemaVersion\":12,\"appliedVersions\":[]}\n","stderr":""}
Expected: 11
Actual: 12
```

Label SETUP_FAILURE принадлежит старому test. Реальная MariaDB/CLI успешна;
это qualifying stale-fixture RED по supporting contract, не missing-production
RED и не environment failure. Никакой business rejection здесь не маскируется.
Raw log вне repository `/tmp/fmonitor2-otiz-v12-red.log`, SHA-256
`f79330443ead1c099480c33747f9c6b988b70a3b651743e6a9c269abd3b4cbfe`.

Предложен, НЕ применён patch
`docs/operations/patches/harness-otiz-canonical-v12-v1.patch`, SHA-256
`97cc7fd60a6fa469eb45c298934e6bd2e47355e62cb0945d94872e7609a332e1`.
Input harness SHA-256
`dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30`.
`git apply --check` — PASS; actual harness hash неизменен.

Patch сравнивает полный exact no-op process result, добавляет две canonical v12
identities только в preservation snapshots и меняет labels на v1-v12.
Финансовые assertions, sentinels, cleanup, auto-increment allowlist, imports,
production и protected E2E неизменны. Fresh independent Gate 3 обязателен до
применения patch. Scope не включает одиннадцать других fixture поправок.
