# BITRIX-STARTUP-CONFIG-001 — RED evidence

Дата: 2026-09-08. Baseline: `1dd62c2e24d3abb35f68573c18cc500f17d7d387`.
Стенд, реальные `.env`, Bitrix, БД и Docker volumes не изменялись.

## Новый публичный CLI

Команда:

```text
php -l tests/Deployment/bitrix_startup_config_001_test.php
php tests/Deployment/bitrix_startup_config_001_test.php
```

Синтаксис PASS. Тест RED с exit 255 на первом корректном примере:

```text
TestFailure: valid env form 0 succeeds
Expected: 0
Actual: 1
```

Причина соответствует отсутствующему поведению: запланированный публичный файл
`bin/fmonitor2-prepare-bitrix-config.php` ещё не существует.

## Стандартный Make startup

Команда:

```text
php rapid-pilot/verify-deployment-contract.php
```

RED с exit 255:

```text
RuntimeException: make up does not prepare explicit Bitrix configuration
```

Причина соответствует отсутствующему поведению: текущий `make up` не готовит
явную Bitrix-конфигурацию и Bitrix worker остаётся opt-in.

## Проверки артефактов

`git diff --check` PASS. `openspec validate
explicit-bitrix-startup-configuration --strict` PASS. Формальный Gate 3 ещё не
утверждён; production implementation до независимого review не начиналась этим
потоком.
