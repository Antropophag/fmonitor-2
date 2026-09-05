# PILOT-SESSION-EMPTY-ENV-FIXTURE-001 v0.1

Статус: DRAFT / TECHNICAL_GATE_1_PENDING. Дата: 2026-09-05.
Supporting test-only amendment внутри OpenSpec
`define-pilot-session-storage-contract`, tasks2.3/3.2/4.1.

## Простыми словами

Проверка явно пустого session root должна передать именно пустую строку.
Существующий способ запуска PHP-процесса теряет такой environment key и
проверяет другой случай — отсутствие настройки. Поправка исправляет test
transport и проверяет доставленное значение перед HTTP assertions. Runtime,
правила сессий и ожидаемые HTTP-отказы не меняются.

## Actor, seam and inherited authority

Actor — developer/CI. Public behavioral seam сохраняется:
`php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`
с real raw HTTP GET/HEAD/POST через existing router/factories.
Единственный edit target — этот test file; application, routers, session owner,
safe-log code, protected E2E и остальные tests не меняются.

Inherited approved `PILOT-SESSION-STORAGE-001` v10 и его OpenSpec config contract:
absent `FMONITOR_SESSION_STATE_ROOT` использует compatibility default,
present empty MUST fail closed без fallback, HTTP503 и без cookie/redirect.
Нового product outcome/owner policy здесь нет; нужен independent technical
Gate1 для точной test-only поправки.

## Fixed transport contract

1. Existing `pssStart` SHALL сохранить свой environment map, cwd, PHP binary,
   server arguments, readiness/stop protocol и все HTTP assertions.
2. Только для `array_key_exists('FMONITOR_SESSION_STATE_ROOT', $extra)` с exact
   value `''` child command SHALL получить argv prefix
   `['/usr/bin/env', 'FMONITOR_SESSION_STATE_ROOT=']` перед PHP executable.
   Shell не используется. Parent environment не меняется. Остальные environment
   values остаются в `proc_open` environment map; password/credentials не
   переносятся в argv. Ключ absent и nonempty значения не получают этот prefix.
3. Перед запуском такого HTTP server test SHALL выполнить setup probe с тем же
   prefix, PHP binary, cwd и environment map. PHP child читает только этот known
   key через native `getenv(name)` и `getenv(name, true)` и выводит JSON array.
   Exact result: exit0, stdout ровно `["",""]`, stderr empty. Значения paths,
   credentials, cookie/session payload и полный environment не выводятся.
4. Missing key (`[false,false]`), иной value, error/output/timeout — SETUP_FAILURE,
   не qualifying product RED. Нельзя затем продолжать HTTP assertions, которые
   на host могли случайно получить503 по недоступному default root.
5. Probe имеет monotonic deadline3s, stdout/stderr каждый <=4096 bytes; при
   истечении срока TERM, grace500ms, затем KILL и reap в пределах2s. Ресурсы
   probe закрываются до HTTP server start; ошибка cleanup запрещает success.
   Existing server readiness/stop semantics не переписываются этим slice.
6. Все existing asset/unknown-route/Host/URI/GET/HEAD/POST headers/body/cookie,
   positive HTTP/HTTPS cases, injected dependency behavior и cleanup остаются
   byte-equivalent assertions. Поправка не создаёт production selector или
   test-only handler в application. Она не касается отклонённых safe-log
   verification mechanisms.

## Independently fixed examples

Для present-empty test input environment-map-only probe на наблюдённых host и
image возвращает `[false,false]`; same child с exact `/usr/bin/env KEY=`
возвращает `["",""]`. Второе соответствует уже approved input contract.
На current unprivileged image compatibility default root доступен, поэтому
старый HTTP test получает200 вместо ожидаемого503. Это дефект fixture delivery,
не разрешение изменить runtime absent-key behavior на503.

На исправленном transport setup probe проходит, а existing GET/HEAD/POST
negative cases обязаны получить прежний exact503. Runtime не меняется.
На host исправленный probe не позволяет future loss of key masquerade as
successful negative test, даже если default directory недоступна.

## Gates and Done

После technical Gate1 повторить focused old-image test/probe и сохранить
intended fixture mismatch отдельно от пяти unrelated DB-setup failures первого
image suite. Это fixture regression evidence, не новый production TDD cycle.
Подготовить unapplied exact test patch; fresh independent Gate3 проверяет
approved input, native child observation, patch, bounds и unchanged HTTP matrix.
Только после APPROVED применить patch и получить host + exact-image focused
GREEN. Затем relevant session regression/architecture/lint/diff и fresh Gate5.
Новые assertions/исправления сверх этой границы возвращаются в Gate1/2/3.

Compose login/stop-start proof остаётся отдельным обязательным prerequisite:
clean startup сейчас не публикует active generation. Green этой поправки не
закрывает parent tasks или launch goal и не делает bootstrap failure допустимым.
