## Context

См. `proposal.md`. После PR #127 `deploy/runtime/compose.yaml` и Yii2 CLI уже владеют runtime lifecycle, но корневой Makefile направляет `up/down/logs/ps/reset` в legacy Compose. Production runbook намеренно подробен и не должен становиться пользовательским quickstart.

## Goals / Non-Goals

**Goals:**

- сделать Makefile тонким публичным orchestration adapter над существующими Yii2 runtime seams;
- обеспечить один local environment contract и одну Compose project identity для всего lifecycle;
- сохранить fail-closed bootstrap, migrations и initial-owner semantics;
- покрыть clean и repeated startup executable black-box контрактом.

**Non-Goals:**

- перенос orchestration ownership в `rapid-pilot` или добавление туда логики;
- автоматический production import, jobs/Bitrix, backup/restore или deployment;
- совместимость нового `make up` с данными старого pilot Compose project;
- удаление legacy файлов либо исторических инструкций.

## Decisions

### Make остаётся adapter, Yii2/Compose владеют эффектами

Make targets вызывают существующий `deploy/runtime/compose.yaml`, `prepare`, `schema-migrate/run`, runtime check и initial-admin CLI. DB bootstrap оформляется отдельным идемпотентным public provisioning command/script в production-owned boundary, а не многострочным SQL в Makefile. Альтернатива — скопировать production runbook в Make — создаёт второй не тестируемый lifecycle owner и отвергнута.

### Один local `.env` contract

Корневой `.env.example` заменяет legacy-required Bitrix поля обязательными local Yii2 settings: unique project, image tag, port, DB/migration credentials, prefixes, session instance, Yii keys, trusted host/scheme, owner email/password. Placeholder validation выполняется до destructive/persistent effects и не печатает secret values. Jobs остаются opt-in. Альтернатива с несколькими env-файлами сохранена только для production runbook.

### Повторный `make up` сходится, а не пересоздаёт

DB user provisioning использует проверяемый create-or-verify contract с exact DML grants; migrations и preparation уже идемпотентны; initial owner допускает только clean create или exact replay. Никаких `down --volumes`, demo bootstrap и ручных INSERT в startup нет.

### Reset ограничен Compose identity

`make reset` вызывает `down --volumes --remove-orphans` только после local configuration validation и только для compose project из `.env`. Production-like либо отсутствующая project identity отклоняется. Обычный `down` volumes не удаляет.

### Verification проходит через публичные команды

RED/acceptance test создаёт изолированный disposable checkout/config/project/port, вызывает Make targets и наблюдает Compose plan, lifecycle order, repeated-up preservation и health. Секреты и полные Docker logs остаются во внешнем evidence root. Architecture checks запрещают ссылку canonical Make targets на rapid-pilot.

Owning module: `deploy/runtime` и Yii2 runtime CLI. Allowed dependencies: Docker Compose v2, canonical application image и MariaDB service. Persistence owners: MariaDB `database` volume и runtime `state`/`secrets` volumes. Rapid-pilot adapter: отсутствует в canonical path. Architecture impact: обновляется проверка production runtime ownership и development setup contract.

## Risks / Trade-offs

- [Смена смысла привычного `make up`] → отметить breaking local migration в README и оставить явную историческую ссылку на legacy contour.
- [Пользователь укажет существующий Compose project] → validate local identity и никогда не удалять volumes в `up/down`.
- [Секреты попадут в diagnostic output] → не печатать expanded Compose config и проверять только имена/placeholder state.
- [Первый bootstrap завершится частично] → повтор использует create-or-verify DB seam и exact-replay owner semantics; UNKNOWN не объявляется ready.
- [Docker acceptance дорогой] → отдельный focused disposable test для Gate 2/4; полный graph только один раз в CI exact candidate.

## Migration Plan

1. Добавить RED public-seam tests и независимое Gate 3 review.
2. Реализовать runtime provisioning seam и Make adapter.
3. Обновить `.env.example`, README/development setup и architecture checks.
4. Проверить focused clean/repeated lifecycle в уникальном disposable project.
5. После Gate 5 опубликовать PR; production deployment и удаление старого стенда не выполняются.

Rollback к предыдущему Makefile возвращает legacy local command routing, но не удаляет уже созданные Yii2 volumes. Данные нового local project сохраняются до явного reset.
