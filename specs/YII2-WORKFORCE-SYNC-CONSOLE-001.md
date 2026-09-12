# YII2-WORKFORCE-SYNC-CONSOLE-001 — операторская синхронизация кадров через Yii2 console

Status: `ACCEPTED_FOR_GATE_2`
Actor: авторизованная deployment/operator automation
Public seam: `php bin/yii workforce-sync/run --interactive=0` и retained `bin/fmonitor2-sync-workforce.php`
Source: issue #76; inherited `BITRIX-WORKFORCE-DELIVERY-001`, `BITRIX-WORKFORCE-HISTORY-001`, `WORKFORCE-CANONICAL-RUNNER-001`

## Простыми словами

Ручная синхронизация справочника монтажников запускается через общий Yii2 console runtime и использует ту же прикладную операцию, что фоновая job. Кадровые правила, Bitrix protocol, расписание и история не меняются; OTIZ, web cutover и deployment не входят в срез.

## A1. Закрытый transport

Допустима только точная argv-последовательность `workforce-sync/run --interactive=0`. Позиционные аргументы, unknown/abbreviated/repeated options, другое значение или положение `--interactive=0` отклоняются до filesystem, network и DB access: exit `64`, stdout `{"ok":false,"reason":"CONFIGURATION_INVALID"}\n`, stderr пуст.

Configuration принимается из абсолютного private `FMONITOR_BITRIX_CONFIG` по действующему `WorkerConfiguration` либо, когда файл не задан, из прямого набора Bitrix environment. File mode имеет приоритет и не читает прямые Bitrix values. DB требует host, canonical port 1..65535, nonempty name/user, explicit password (empty допустим) и process prefix `/^[A-Za-z0-9_]{0,25}$/D`. Invalid configuration даёт exit `64`; недоступная DB — exit `69` и `DATABASE_UNAVAILABLE`; unexpected composition failure — exit `70` и `SYNC_FAILED`. Каждый исход печатает один JSON object и newline в stdout при пустом stderr.

## A2. Единственный application owner и durable result

После validation общая composition создаёт существующий Bitrix delivery client, владеет mysqli resource и делегирует `MariaDbWorkforceSynchronization`, гарантированно освобождая resource при terminal outcome. Controller, alias и job transport не владеют SQL, нормализацией или транзакцией. Manual command создаёт canonical UUIDv4 run identity; job сохраняет immutable `jobIdentity` и прежний retry mapping. Gate 2 доказывает единственную достижимую source composition boundary, `finally`-ownership и отсутствие composition в launchers; точный внутренний invocation count уже утверждённого domain owner не переопределяется этим refactoring slice.

`completed` возвращает exit `0` и закрытый существующий synchronization result; иной owner result возвращает exit `1`. Full snapshot, append-only history, unchanged repeat, identity/concurrency и failure atomicity не меняются и подтверждаются неизменёнными independent delivery/history/synchronization oracle. Новый Yii subprocess отдельно доказывает полный success и unchanged repeat; этот transport slice не создаёт новую commit-reconciliation policy.

## A3. Alias, jobs и package closure

Retained `bin/fmonitor2-sync-workforce.php` является тонким launcher того же Yii route. Делегирование проверяется direct success → alias unchanged repeat на одной isolated database. Для failure alias сохраняет исторический public envelope: exit `1`, `{"status":"failed","reason":"SYNC_UNAVAILABLE"}\n`, пустой stderr; direct Yii command сохраняет точные причины из A1. Domain transport failures остаются результатом одного неизменённого owner и его independent oracle. Scheduled workforce job переиспользует ту же composition, сохраняя jobs lease/retry/deduplication и configuration-failure contract.

Command load set содержит Yii controller/composition и канонические workforce owners, но не `rapid-pilot`, demo, web/session, OTIZ или вторую legacy composition/autoloader. Ordinary web startup не запускает sync; production artifact содержит command closure. Schema, schedule, stand и deployment не изменяются.

## A4. Authorization, redaction и verification

Срез не создаёт HTTP/user permission и не расширяет deployment/operator admission. Token contents/path, Bitrix configuration, DB credentials/coordinates/prefix, employee PII, exception/message/trace и SQL отсутствуют в command stdout/stderr; staged token удаляется после file-mode success или failure. UNKNOWN не является success, GREEN или разрешением deployment.

Gate 2 через public subprocess проверяет argv/config до side effects; real local HTTPS+MariaDB проверяет 51-record multi-page success, durable facts, unchanged repeat и direct/alias parity; fault cases проверяют failures, cleanup и redaction. Ownership/package tests подтверждают один seam и закрытый load set; existing delivery/history/jobs tests остаются controls. Обязательны независимые Gates 3/5, bounded local GREEN и один full exact-source CI.

## Done definition

Срез завершён только при полном mapping, intended RED, Gate 3 APPROVED, минимальной реализации, focused GREEN, Gate 5 APPROVED, strict OpenSpec и merge-ready exact-source PR. Stand/deployment и общий #76 остаются отдельными решениями.
