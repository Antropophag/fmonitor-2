## Context

См. `proposal.md` и delta spec. В `origin/main` после #140 приложение уже владеет нормализацией, fingerprint, append-only replay и административным listing; `app_version VARCHAR(80)` хранит неизменяемое значение. После #228 актуальный router содержит календарь, дашборд и guided ОТиЗ. После #234 production image создаёт root-owned read-only `/usr/local/share/fmonitor2/runtime-build-id`, а `RuntimeBuildIdentity::read()` при пустой настройке намеренно переходит к дорогому `sourceIdentity()` для startup/readiness. Этот fallback запрещён в feedback HTTP.

## Goals / Non-Goals

**Goals:** сохранить закрытый route allowlist в owner seam; читать заранее сформированную identity без source fallback; сохранить replay/history; проверить таблицей application seam и одним связным HTTP/browser-сценарием.

**Non-Goals:** общий route registry, изменение router или бизнес-правил ОТиЗ, статусов/карточки/календаря/справок, release framework, telemetry, migration, изменение readiness, загрузка/download/export контексты, внешний стенд или реальные обращения.

## Decisions

- Владелец состояния остаётся `FeedbackApplication` + `MariaDbFeedback`. Route allowlist расширяется адресно по текущему `config/yii/web.php`; `MainNavigation` продолжает передавать только `pathInfo`, поэтому query/fragment не попадают в hidden field. Альтернатива «любой `/pilot/*`» отвергнута из-за open redirect и mutating/download endpoints; общий реестр router не строится.
- Object ID берётся только из первой capture-группы объектных паттернов. Snapshot ID ОТиЗ не имеет object semantics и возвращает `null`. Существующие order IDs также не меняют object ID.
- Fail-soft reader остаётся внутри существующего `FeedbackApplication`: он проверяет только настроенный абсолютный build-файл по тем же ограничениям immutable file (regular, не symlink, one link, exact 64 hex + newline, read-only) и возвращает полный digest либо `unknown`. Узкая injectable filesystem dependency (open/fstat/read/lstat/close) существует только для детерминированной проверки same-handle/path binding; production default использует встроенные файловые операции, нового Runtime-модуля или release seam нет. Reader не вызывает `RuntimeBuildIdentity::read()`, чтобы пустая/ошибочная настройка никогда не дошла до `sourceIdentity()`. Production использует уже заданный `FMONITOR_RUNTIME_BUILD_ID_FILE`; тесты подставляют A/B/temp files и детерминированный pathname-rebind fake. Readiness продолжает вызывать строгий существующий reader и fail closed.
- `config/yii/common.php` передаёт в feedback component только server-owned путь `FMONITOR_RUNTIME_BUILD_ID_FILE`. `FeedbackApplication::init()` один раз вычисляет полный digest или `unknown`; клиентских полей версии нет. Значение сохраняется только внутри values первой insert.
- Fingerprint не меняется: `[description, normalized path, objectId]`. Это уже обеспечивает replay через смену build, collision и concurrency; `MariaDbFeedback::append()` сначала возвращает receipt и не UPDATE-ит root. Schema `VARCHAR(80)` достаточна, backup/restore frontier неизменен.
- Операторское view уже выводит `pagePath` и `appVersion` из persistence с escaping. Тест делает labels/значения явными, но не создаёт новый экран или журнал.
- Root является автором normative spec и RED tests на apply-этапе. Отдельный `gpt-5.6-sol/low` executor реализует production code; независимые `gpt-5.6-sol/low` reviewers принимают planner-required Gate 3/5. Architecture-check impact ограничен существующими YiiRuntime/Runtime dependencies; `rapid-pilot` не изменяется.

## Risks / Trade-offs

- [Расхождение router и allowlist] → адресная таблица положительных и отрицательных current routes; новые экраны добавляются осознанно, fail-safe остаётся `/pilot/objects`.
- [TOCTOU при чтении build-файла] → использовать одно короткое чтение с проверкой metadata до/после либо открыть handle и сверить inode/size/mtime; любая неоднозначность даёт `unknown`, не 503.
- [`unknown` скрывает ошибку поставки] → feedback остаётся доступным, но readiness сохраняет независимый строгий fail-closed контракт #234 и не использует fail-soft reader.
- [Слишком широкий контекст] → не включать query/fragment, API-like GET, downloads/exports и endpoints без feedback shell.

## Migration Plan

Миграции данных и схемы нет. Код поставляется вместе с существующим immutable build-файлом production image. Rollback к прежнему коду не меняет уже сохранённые записи; они остаются читаемыми в `VARCHAR(80)`. Перед публикацией: focused RED/GREEN, planner-selected reviews, один exact-source GitHub CI; локальный полный suite запрещён.
