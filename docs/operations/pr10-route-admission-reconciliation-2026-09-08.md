# Актуализация PR10 и закрытие PR37 — 2026-09-08

Владелец разрешил предложенный порядок: закрыть PR37 с сохранением ветки,
актуализировать PR10, провести независимую проверку и довести до merge.
Это новое разрешение относится к этим двум PR; рабочий стенд и данные не входят в изменение.

## PR37

PR37 закрыт без merge. Ветка `codex/qg-parity-20260908` сохранена на
`9f530017ab769de4e6e1647cadb990281e34c0e9`, состояние проверено GitHub API.
Положительная runner parity остаётся историческим evidence. Остаток Quality Graph,
negative matrix и publisher записан в issue25 и не объявляется завершённым.
Ускорение интеграционных fixtures относится к отдельной issue55.

## PR10: ограниченный объём

База актуализации: main `73a9dd17934606712414b4f74cd2cbade8a26163`.
Старая ветка сохранена через merge main без переписывания истории.
Исторические approvals и evidence PR10 сохранены отдельными файлами.

Исправляется только преждевременное исполнение обработчика при распознавании
маршрута. Чистое распознавание обязано сохранять оба действующих пути:
`/pilot/objects/{id}/checklist/operations` и
`/pilot/construction-control/objects/{id}/checklist/operations`.
Обработка тела и бизнес-ответ остаются после session/auth admission.
Предметные условия закрытия последних 15% не меняются.

Основание: PILOT-SESSION-STORAGE-001 §§6–7 и ранее одобренный corrective slice
в `reviews/tests/PILOT-SESSION-STORAGE-001-v9-route-recognition-v4.md`.
Это продолжение `define-pilot-session-storage-contract`, а не новая миграция.
Незавершённые общие пункты OpenSpec не отмечаются автоматически выполненными.

## RED на актуальном main

Команда: `php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`.
Production router и CompletionFlow временно побайтно равны origin/main;
raw-HTTP regression включает оба URL. Первый запрос с `{"itemId":42}` и
present-empty session root завершается кодом теста255:
ожидались503 и `Service unavailable.\n`, получены303 и JSON бизнес-отказа
о закрытии последних15% актом ПТО и декларацией. Предыдущие route/asset/Host/URI
проверки прошли. Это воспроизведённый дефект порядка, не ошибка setup.

## Проверка и поставка

Актуальные independent Gate3/Gate5, focused результаты и итоговый CI
дописываются после фактического получения. На момент исходной записи merge
не выполнен, full VERIFY_OK текущей ветке не приписывается.

## Уточнение fixture после первого GREEN-кандидата

Первое обновление теста сохраняло историческое ожидание503 для POST без cookie.
Нынешний `LocalAuth::startSession` для такого запроса не открывает сессию и
направляет пользователя на login. После чистого распознавания исчез бизнес-вывод,
но корректный303 остался; прежний expected503 неприменим к этому входу.
Первая Gate3 approval сохранена как история и подлежит superseding rereview.

Fixture разделён на два явных входа для каждого URL: без cookie ожидается303,
пустое тело, Location `/pilot/login`, без новой cookie; с синтаксически допустимой
cookie и present-empty root сохраняется точный503 без бизнес-вывода/cookie/redirect.
Оба входа достигают настоящего router/LocalAuth. На main no-cookie проверка
вновь RED255 из-за JSON бизнес-отказа в теле redirect; runtime auth policy не меняется.

## Выбор актуальной реализации

Перенос старого helper в CompletionFlow создавал новый150-строчный hotspot и
был отклонён architecture-check. При повторной проверке установлено: существующий
`PilotRouteAdmission::isKnown` уже чисто распознаёт оба checklist URL. Поэтому
новый helper не нужен: достаточно убрать ранний `blocksLegacyCompletion` из
аргумента route admission. Единственный вызов обработчика остаётся после auth.
Это устраняет дублирование распознавания и сохраняет CompletionFlow побайтно
равным main; baseline архитектуры не расширяется.

## Focused GREEN и test review

Superseding independent Gate3 APPROVED: `reviews/tests/PILOT-SESSION-STORAGE-001-pr10-reconciliation-2026-09-08.md`, test SHA256 `0d91453a7bff6c8f4714cc8328cd50367ec3d158e2e9798501048214bf976010`.
Reviewer отдельно воспроизвёл оба cookie-входа на обоих URL.
После удаления единственного преждевременного вызова PASS:
`pilot_session_storage_protocol_001_test.php`,
`pilot_route_csp_completion_flow_001_test.php`,
`pilot_http_auth_001_global_calls_test.php`; PHP lint router PASS.
Тест запускает свои loopback HTTP servers; рабочий стенд не используется.
