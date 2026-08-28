# FMonitor 2.0 — session handoff

Актуально на `2026-08-28`.

## Возобновление

Работа остановлена на чистой границе срезов. `PILOT-OBJECT-CARD-001 v0.2` прошёл Gate 1–5; следующий срез ещё не начат.

1. Прочитать `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`.
2. Проверить `git status --short`: HEAD — только initial commit, почти вся реализация, тесты и reviews находятся в dirty worktree. Сохранять все несвязанные изменения; `reset/clean` недопустимы.
3. Запустить полный suite:

```bash
set -o pipefail
passed=0
for test_file in tests/InstallationProcess/*_test.php; do
  php "$test_file" || exit 1
  passed=$((passed + 1))
done
printf 'TOTAL_PASS=%s\n' "$passed"
```

Ожидается `TOTAL_PASS=42`. Последний последовательный прогон дал `42/42 PASS`; `.test-artifacts` пуст. Последний независимый Gate 5 зафиксировал focused `3/3 PASS`, sequential `42/42 PASS` и чистую серию из трёх полных parallel `-P8` прогонов по `42/42 PASS`.

Известная внешняя test-infrastructure flake: старый `PILOT-HTTP-AUTH-001` global resource-observation probe изредка видит transient соединение другого параллельного HTTP-теста. Она не воспроизводится в чистой серии и не оставляет ресурсов; не смешивать её с новым UI-срезом. Если станет воспроизводимой, оформить отдельный harness slice.

## Обязательная оркестрация

- Root-сессия оркестрирует и хранит короткое состояние.
- Каждый Gate выполняет новый агент с пустым или минимальным fork-контекстом. Старого агента между Gate не переиспользовать.
- Gate 3 и Gate 5 выполняют отдельно назначенные независимые reviewers.
- Review фиксирует SHA-256 всех входов; изменение состава или байтов manifest инвалидирует verdict.
- Одновременно выполняется один вертикальный behavior slice, строго Gate 1 → 5.

## Фактическое состояние

Работают доменные команды подготовки распоряжения, подтверждения номера 1С ДО и открытия работ; append-only аудит; MariaDB persistence; production migration process-таблиц `fm2_*`; pilot-case import; legacy object snapshot; user/capability adapters; authorization; HTML renderer; artifact store; production composition; HTTP authentication/security shell; read-only карточка явно импортированного объекта монтажа.

Production entrypoints:

- `bin/fmonitor2-migrate.php`;
- `bin/fmonitor2-import-cases.php`;
- `public/router.php` (`GET /pilot/`, `GET|HEAD /pilot/objects/{positive-id}` и stylesheet).

Последние manifest-pinned approvals:

- `PILOT-HTTP-AUTH-001 v0.12` — `reviews/code/PILOT-HTTP-AUTH-001.md`;
- `BITRIX-WORKFORCE-SCHEMA-001 v0.3` — `reviews/code/BITRIX-WORKFORCE-SCHEMA-001.md`;
- `PILOT-OBJECT-CARD-001 v0.2` — `reviews/code/PILOT-OBJECT-CARD-001.md`.

Точные финальные hashes карточки:

```text
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
4577d14d1323844cc36aab3935269e708c1adb854e4f1d6e9f036950d66c45dc  tests/InstallationProcess/pilot_object_card_001_test.php
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
ae3a730987d59196a2daa96c47fbd64d6593f19caef523d80d949e5c0fc4f36b  reviews/code/PILOT-OBJECT-CARD-001.md
34e294b65e30499293687414fdf8f87791cbca752c9a1c8830f8094cebe07661  app/PilotHttp/PilotHttp.php
```

Более ранние Gate records существуют в `reviews/tests/` и `reviews/code/`, но многие предшествуют полному manifest-подходу. Перед релизным утверждением нужен свежий независимый аудит всей композиции; переписывать работающие модули только по этой причине не требуется.

## Архитектурные решения

FMonitor 2.0 — отдельное приложение и владелец нового процесса в собственных `fm2_*` таблицах. `../fmonitor` — read-only integration source. `../shlz-ui` используется через публичные exports.

Инженер строительного контроля — пользователь FMonitor с ролью/capability. Монтажник не является пользователем FMonitor и не получает роль.

Кадровый поток: `1С ЗУП` (организационный первоисточник) → `Bitrix24` (операционная реплика и прямой endpoint) → versioned workforce history FMonitor 2.0. Bitrix сейчас даёт актуальный `ACTIVE`, но не доказанную дату увольнения. Поэтому различаются:

- `dismissal_effective_at` — фактическая дата из явного ZUP-origin поля;
- `first_observed_dismissed_at` — момент первого наблюдения неактивного статуса.

Пропуск записи в одной доставке не означает увольнение. Evidence: `docs/bitrix-workforce-integration-research.md`, термины: `CONTEXT.md`.

Legacy data-estate source audit зафиксирован в `docs/fmonitor-2-legacy-data-estate-audit.md`: обнаружено 38 concrete local tables и существенные факты вне `fm_maintable`. ADR `docs/adr/0001-no-generic-legacy-metadata-platform.md` исключает из 2.0 generic MDM, custom-field/view-builder platform и runtime formulas. Они не мигрируются и не исполняются; transient read-only metadata допустима только для толкования явно выбранных legacy facts.

## Что ещё не готово

- Нет HTTP-очереди объектов; карточка реализована только как read-only overview tracer.
- Нет HTTP command forms, CSRF/session workflow.
- Для Bitrix реализована только schema migration; публикация снимка и исторические переходы отсутствуют.
- Legacy `MariaDbWorkforceCatalog` не должен остаться финальным источником монтажников; production composition переводится на Bitrix-derived projection отдельными срезами.
- Нет развёрнутого тестового стенда и end-to-end smoke его конфигурации.

Maintainability follow-up после видимого tracer bullet: декомпозиция крупного `PilotHttp.php`, затем оценка `InstallationProcess.php`. Не смешивать её с новым UI-срезом без нарушения спецификации.

## Следующий срез: read-only очередь объектов

Начать только Gate 1 и присвоить новый стабильный spec ID. Прочитать `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, `docs/fmonitor-2-screen-flow.md`, `docs/fmonitor-2-leader-queue-discovery.md`, `docs/installation-process-interface.md` и successor contracts `specs/PILOT-HTTP-AUTH-001.md`, `specs/PILOT-OBJECT-CARD-001.md`.

Узкая граница среза:

- авторизованный read-only `GET|HEAD /pilot/objects` collection route;
- только явно импортированные pilot cases;
- минимальный один acceptance statement/tracer: канонический список и переход в уже утверждённую карточку;
- точные ordering/pagination/access outcomes должны быть решены Gate 1, не переносить предположения из старого широкого UI;
- ноль process/audit мутаций, форм и command controls;
- UI через публичные exports `shlz-ui`.

После затянувшегося object-card slice держать каждый следующий срез существенно уже: один acceptance statement → один RED → одна minimal implementation. Не объединять route grammar, весь state matrix, corruption census и harness refactoring в один Gate 2.

После очереди: минимальные command slices → Bitrix workforce publication/history → concrete legacy live-data census (без generic MDM/custom fields) → deployment и end-to-end smoke.

## Bitrix backlog

`specs/BITRIX-WORKFORCE-HISTORY-001.md` — retired horizontal epic, не Gate 2 spec. Остались отдельные срезы: first publication; repeat/material/missing transitions; delivery validation; transport security; concurrency; commit reconciliation; history read model; DB privileges.

## Неблокирующие продуктовые вопросы

- Нужен ли финальный файл из 1С ДО помимо номера.
- Область уникальности регистрационного номера и исправление ошибки.
- Формулы SLA очередей.
- Retention/export security-аудита.
- Явное ZUP-origin поле фактической даты увольнения.
