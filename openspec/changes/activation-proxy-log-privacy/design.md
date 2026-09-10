## Context

Root готовит spec/tests; отдельные sol/low implementation/review. Текущий owner
разрешил автономную #76 после завершённой #82. Этот изолированный checkout
не пересекается с pending Yii admin implementation.

## Decisions

Полная матрица: [ACTIVATION-PROXY-LOG-001](../../../specs/ACTIVATION-PROXY-LOG-001.md).
Safe JSON access format сохраняет request ID, method/path/status/timings; raw
request/query/headers не записываются. Exact activation location отключает
request error sink, сохраняя startup/прочие diagnostics и прежние FastCGI params.

Existing final Yii stage разделяется на runtime-base (прежний PHP/nginx RUN) и
application FROM runtime-base. Финальные COPY/ENV/USER/CMD сохраняются. Test строит
только base once, BuildKit отбрасывает независимые Composer/shlz stages. Local
focused evidence может использовать уже установленный exact nginx image; конфиги
всегда candidate-owned и readonly. CI проверит default build target на exact source.

## Dependency impact before Gate2

- Schema/fixtures/current frontier/readiness/backup: не меняются, SQL не выполняется.
- Runtime dependency: новых пакетов нет; nginx уже установлен тем же Dockerfile.
  Stage split меняет build topology, не финальные файлы/privileges/entrypoints.
- Deployment: оба конфигурационных файла уже COPY-ed в свои runtime images.
  Включён parity check FastCGI params и реальный nginx -t.
- Verification inventory: новый E2E Python test + categories/suites/historical
  baseline addition; existing Yii runtime/dependency and production runtime
  contract regressions указаны в verification plan. Полный CI один на объединённом
  кандидате #76, local full не дублируется.
- Test cleanup: сохраняет только свои debug logs вне repo, удаляет свои containers
  по returned ID; не трогает image или работающие контуры.

## Risks / Trade-offs

Activation upstream failures диагностируются по safe access status/timings и
application/health observations; подробный nginx request error на этом path скрыт,
поскольку включает bearer query. На обычном path подробная диагностика сохранена.
Default image build — CI path; local cache override отдельно отмечается evidence.

## Complete acceptance traceability

Один executable test покрывает один составной acceptance целиком. Его обязательные
проверки: syntax → real nginx -t; access → GET/POST stdout/canaries/JSON;
error → activation stderr; diagnostics → invalid config + ordinary upstream;
parity → unchanged FastCGI handoff; setup → verified cache/default runtime-base;
isolation → owned-container context and deterministic failure-before-ID probe.

Попытка отдельных JSON entries для этих строк была отвергнута самим planner:
`duplicate test mapping`. Нормативный CHANGE-VERIFICATION-001 прямо запрещает
один test path в двух mappings. Поэтому вход сохраняет составной acceptance,
а эта карта раскрывает все его проверки без фиктивных wrapper tests или изменения
планировщика. Команды существующих runtime regressions входят в тот же mapping.

Cleanup now retains unique name plus ownership label before Docker creation.
The same cleanup context is exercised by an intentional client failure after
real docker create, without assigning its returned ID; an unrelated labelled
fixture survives. Non-owned names are never removed. This closes the observed
pre-assignment failure window without touching the stand.
