# YII2-LOCAL-DATA-BOOTSTRAP-001 — наполнение локального Yii2-стенда

Status: `ACCEPTED_FOR_GATE_2`
Owner authorization: прямое решение владельца 2026-09-14; связано с #76/#128
Actor: локальный оператор тестового стенда
Public seam: `make import-legacy`, `make sync-workforce`, `make up-with-data`
OpenSpec: `openspec/changes/yii2-imports-workforce/`

## Простыми словами

`make up` только идемпотентно поднимает Yii2 runtime. Для чистого стенда
`make up-with-data` последовательно поднимает runtime, импортирует допустимые
production-объекты через read-only legacy connection и обновляет справочник
монтажников из Bitrix. Каждый внешний этап доступен отдельно. Production runtime
и Make-команды не загружают `rapid-pilot`.

## A1. Make interface и порядок

`make import-legacy` SHALL выполнить один Yii2 console import в уже поднятый
canonical runtime. `make sync-workforce` SHALL выполнить существующий
`php bin/yii workforce-sync/run --interactive=0`. `make up-with-data` SHALL
выполнить `up → import-legacy → sync-workforce`, остановиться на первой ошибке и
не печатать success раньше завершения всех трёх этапов. `make up` SHALL не читать
production systems.

## A2. Приватная конфигурация

Legacy source SHALL читаться только из `.local/legacy-source.env`; Bitrix SHALL
читаться только из `.local/bitrix-workforce.json`. Оба файла SHALL быть regular,
не symlink, mode `0600`; отсутствие или неверный формат SHALL вернуть stable
non-zero до network/DB effects. Secret contents SHALL отсутствовать в argv,
stdout/stderr и Compose environment dump.

Legacy-файл SHALL содержать ровно `FMONITOR_SOURCE_HOST`,
`FMONITOR_SOURCE_PORT`, `FMONITOR_SOURCE_NAME`, `FMONITOR_SOURCE_USER`,
`FMONITOR_SOURCE_PASSWORD`, `FMONITOR_MIGRATION_CUTOFF`. Host/name/user/password
непустые; port `1..65535`; cutoff пустой либо exact `Y-m-d H:i:s`. Bitrix JSON
наследует `WorkerConfiguration::fromFile`: absolute HTTPS `/rest/<positive-id>/<token>`,
непустой список уникальных positive department IDs. Rejection SHALL вернуть
`LOCAL_INTEGRATION_CONFIG_INVALID`, exit `64`, без нового DB/network факта.

## A3. Native Yii2 legacy import

Public seam SHALL быть `php bin/yii legacy-import/run --interactive=0` внутри
canonical runtime image. Он SHALL читать legacy MariaDB только read-only,
зафиксировать единый cutoff, импортировать применимый checklist template, только
ещё не открытые eligible objects, object details и template
associations в canonical Yii2 database. Repeat с тем же cutoff SHALL быть
идемпотентным; incompatible/non-empty generation, partial или UNKNOWN result
SHALL завершиться non-zero без ложного success.

Пустой cutoff SHALL вычисляться один раз на invocation как конец текущего дня
Europe/Moscow и использоваться всеми source reads этого invocation. Success SHALL
вернуть один JSON result `LEGACY_IMPORT_COMPLETED` с counts
eligible/imported/alreadyPresent/details/templateAssociations и завершённым run.
Configuration invalid SHALL вернуть exit `64`; любой недоказанный terminal result
SHALL быть non-zero и не содержать success. После сбоя повтор SHALL безопасно
сверить уже записанные immutable facts и продолжить, не переписывая историю.
Replay comparison SHALL одинаково canonicalize source expectation и сохранённое
mirror-представление дат: необязательный time suffix и legacy zero-date variants
не создают `MIRROR_CONFLICT`, когда обозначают тот же импортированный факт.

Legacy `workdatefinish` SHALL считаться фактом завершения только когда значение
не позднее зафиксированного cutoff. Будущая относительно cutoff дата SHALL
нормализоваться в `null` до classification/eligibility и не должна сама по себе
исключать ещё не открытый объект. Это тот же cutoff contract, который действует
для остальных фактов, недоступных на момент снимка.

Eligibility, all-or-nothing case facts и replay наследуются из
`PILOT-CASE-IMPORT-001`; classification/provenance — из действующего
classification contract; template/details — из checklist/object-detail snapshot
contracts. Новый seam меняет transport/composition, а не ожидаемые факты.
В частности, bootstrap SHALL импортировать native candidate независимо от
плановой даты начала; прежняя техническая граница `2026-10-01` не является
eligibility-критерием.

Production command, image и runtime loaded-file closure MUST NOT содержать или
загружать `rapid-pilot`. Существующие rapid scripts остаются только oracle.

## A4. Workforce и ошибки

Bitrix sync SHALL использовать существующего owner `MariaDbWorkforceSynchronization`
через `workforce-sync/run`. Недоступный legacy, Bitrix или target DB SHALL
завершить соответствующую Make-команду и `up-with-data` non-zero. Повтор команды
SHALL быть безопасен благодаря идемпотентным import owners.
