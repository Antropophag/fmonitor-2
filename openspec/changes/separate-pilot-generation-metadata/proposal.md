## Why

Обычный запуск pilot-контейнера сейчас смешивает generation identity с
неатомарным schema/data bootstrap: каждый рестарт меняет nonce, выполняет DDL и
удаляет таблицы до публикации manifest. Для TEST-USER-READY restart должен
сохранять состояние, а создание/reset поколения должны быть явными setup seams.

## What Changes

- Выделить DB sentinel и filesystem manifest в setup-only generation metadata,
  не регистрируя их как production/domain migration.
- Сделать обычный restart чистой проверкой уже опубликованного generation и
  запретить ему менять nonce, schema, data или artifacts.
- Ввести единый fail-closed validator для Compose HTTP startup, workers и
  import/apply tools, включая DB server/database identity, manifest,
  prefix/mode/state и consumer-relevant listener consistency.
- Определить сериализованное создание generation, crash-boundary recovery,
  приватную atomic publication и scoped cleanup incomplete artifacts.
- Сохранить `make reset` единственной явной destructive whole-environment
  operator seam; destructive действия не переносятся в production migrations.
- Отделить fixture/import/schema/identity/OTIZ operations от publication
  generation identity и оставить их в собственных gated slices.
- **OWNER DECISION:** единственный TEST-USER release contour — Compose
  `make up`; standalone CLI остаётся synthetic fixture harness и не доказывает
  release readiness. Будущий рабочий contour также разворачивается через
  Compose-подход, но production credentials, backup, scaling и эксплуатационный
  runbook не входят в эту change. Supported topologies MUST prove disjoint
  state/prefix, with an explicit discriminator wherever co-location is allowed.
  GRILL-004 закрыт отдельным approved test-user data/reset decision.

Behavior slice: `SEPARATE-PILOT-GENERATION-METADATA-001`. Actor — локальный
Compose pilot operator/container orchestrator. Source oracle — текущая пара
`fm2_pilot_generation_sentinel` + `active.json` и характеризованные generation
guards. Target public seam — `make up` / `rapid-pilot/docker-entrypoint.sh`,
делегирующие одному Compose-contour setup initializer/validator до запуска HTTP
и workers.
Release value — повторный запуск test-user contour без потери/скрытого изменения
состояния и fail-closed защита от смешанной generation. Non-goals — production
schema ownership, product/domain facts, fixture choice, import/backfill,
destructive data migration и массовый persistence redesign.

По принятому owner decision `bin/fmonitor2-pilot-demo.php` является отдельным synthetic fixture harness с
`owner.json`/`ready.json`/`active.json` и DB table-comment markers. Он явно не
является consumer или owner этой Compose-contour capability и не переводится на
третий shared lifecycle этой change. Supported invocations MUST prove disjoint
state root/prefix; co-located invocation requires an explicit discriminator.
Успех standalone harness не является доказательством TEST-USER readiness.

## Capabilities

### New Capabilities

- `operations/pilot-generation-metadata`: Setup-only создание, публикация,
  проверка, повторный запуск и явный reset локальной pilot generation.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только Compose/Make test-user startup-reset contract,
`rapid-pilot/docker-entrypoint.sh`, bootstrap/startup, manifest/sentinel
validation, optional workforce worker и import/apply tools. Production seam
`bin/fmonitor2-migrate.php` и domain persistence не получают generation table.
Изменение требует согласования с release-critical schema/bootstrap slices до
GREEN, но не утверждает заблокированную GRILL-004 fixture semantics. Synthetic
fixture/lifecycle semantics не переписываются, но supported-topology isolation
и исправление противоречивого root runbook входят в collision closure при выборе
Compose.
