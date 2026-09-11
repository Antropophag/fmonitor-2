## Context

См. `proposal.md`. Текущий `bin/fmonitor2-migrate.php` вручную валидирует environment, создаёт mysqli connection и собирает `CanonicalMigrationApplication`; production Compose и Make вызывают этот файл напрямую. После PR #96 общий Yii2 console runtime уже владеет jobs commands. Canonical schema catalogue, lock, restart и exact schema contracts ранее поставлены и не должны переписываться.

## Goals / Non-Goals

**Goals:**

- Подключить существующий migration owner к общей Yii2 console composition.
- Сохранить публичный JSON/sysexits, exact catalogue, lock и restart semantics байт-совместимыми там, где это является действующим oracle.
- Сделать production deployment callers зависимыми от Yii2 console runtime и реального Composer autoload.
- Сократить production migration bootstrap до одного composition root.

**Non-Goals:**

- Не переводить schema classes с mysqli на ActiveRecord или Yii migrations.
- Не вводить таблицу `migration` Yii и не импортировать в неё canonical ledger.
- Не менять DDL, порядок каталога, recovery policy или данные.
- Не переносить imports, workforce sync, runtime recovery, web runtime или stand.

## Decisions

1. **Owning module остаётся `InstallationProcess`.** Yii console controller — только transport/composition adapter; он собирает и вызывает существующий `CanonicalMigrationApplication`. Альтернатива с `yii migrate` отвергнута: она создаёт второй ledger и меняет уже проверенную restart/concurrency модель.
2. **Одна shared command service для direct `bin/yii` route и compatibility alias.** Валидация environment, mapping исключений и JSON emission извлекаются из старого bin script в application-facing console adapter под `app/YiiRuntime`; и Yii controller, и временный bin alias вызывают его. Копирование прежнего script в controller запрещено. `bin/fmonitor2-yii.php` остаётся внутренним bootstrap, а executable exit code задаёт существующий wrapper `bin/yii`.
3. **Один DB boundary на весь запуск.** Существующий mysqli owner и `MariaDbMigrationLock` сохраняются до отдельной целостной миграции persistence boundary. Yii DB connection не участвует в DDL/ledger transaction этого slice, поэтому смешанных транзакций нет.
4. **Production callers переключаются атомарно с тестами package/load trace.** `deploy/runtime/compose.yaml` и Make target используют Yii console command; image обязан содержать Composer/Yii config и не загружать `rapid-pilot`. Legacy alias сохраняется лишь для явно найденных retained callers и удаляется после доказательства их отсутствия.
5. **Verification наследует существующие schema oracles.** Новые тесты сравнивают direct/alias outcomes и transitive load, а существующие production migration, lock/concurrency, fresh/repeat/upgrade и runtime-no-DDL suites остаются источником ожидаемых schema facts.

Boundary impacts: schema frontier применим полностью; fixture/table inventory и migration verification обязательны. HTTP/auth/user-flow/browser группы неприменимы, потому что публичный seam только operator CLI и web/runtime не меняются. Deployment/package, secrets/redaction, concurrency/restart и architecture-check применимы.

## Risks / Trade-offs

- [Risk] Тонкий alias может скрыть оставшегося production caller → [Mitigation] lexical caller inventory плюс runtime package/load trace; condition удаления записывается явно.
- [Risk] Yii console bootstrap изменит stdout/stderr до JSON → [Mitigation] isolated process tests для config/bootstrap failures и assertion ровно одной JSON-строки.
- [Risk] Неявное создание Yii DB connection приведёт к двум DB boundaries → [Mitigation] migration controller не получает `db`; composition владеет ровно одним mysqli lifecycle.
- [Risk] Existing long schema suite дорог локально → [Mitigation] bounded focused fresh/repeat/upgrade/concurrency checks локально и один exact-source full GitHub CI по owner decision 2026-09-11.

## Migration Plan

1. Зафиксировать normative spec/verification input, подготовить harness package и получить Gate 3 для intended RED.
2. Реализовать shared console adapter и Yii command; переключить Compose/Make и compatibility alias.
3. Выполнить focused migration, concurrency, package/load и architecture checks; независимый Gate 5.
4. Запустить один exact-source full GitHub CI. После GREEN открыть/merge PR; рабочий stand и его БД не трогать.
5. Rollback source выполняется возвратом caller к предыдущему entrypoint; уже применённые canonical migrations не откатываются destructively и остаются совместимыми с прежним owner.
