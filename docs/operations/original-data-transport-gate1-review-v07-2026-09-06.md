# Независимый Gate 1 review: DATA-INTEGRITY v0.7 / ORIGINAL-UPLOAD v72

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный независимый agent `/root/data_transport_gate1`
- Reviewed commit: `56e494a46f201d81f6cd85808f3a3d1f6bc22a33`
- DATA-INTEGRITY SHA-256: `30f402ceb136cd72fcc97801a28c51c9186ce78e8ea5454bf0e98ee272814bd9`
- ORIGINAL-UPLOAD SHA-256: `461577954b8f89f07900e1b3ebb217ceec3f981421d6420f51e79d9c4f5f18ee`
- Verdict: **APPROVED**

Reviewer не участвовал в написании рассмотренной поправки. Проверен только diff
v0.6→v0.7 / v71→v72 из commit `56e494a`, раздел B handoff
`autonomous-restart-handoff-2026-09-06-0830Z.md` и уже утверждённый результат
гонки из `ASSIGNMENT-ORDER-ORIGINAL-DATA-WORKER-CANONICAL-PATH-001-v1.md`.

## Gate 1 assessment

Новый транспортный контракт однозначен и наблюдаем на заявленном публичном seam.
Ровно девятибайтовый hostname, ASCII case-fold которого равен `localhost`,
отклоняется и fresh-reader factory, и worker до чтения содержимого password file
и до любого вызова mysqli. Остальная ранее допустимая hostname/IPv4/IPv6 grammar
не меняется. Переписывание host, socket fallback, protocol/config option и новая
production dependency прямо запрещены. Явный `127.0.0.1` остаётся TCP control,
поэтому совместимость с существующими real-adapter проверками сохранена, а
намеренно несовместимый случай ограничен mysqlnd-специальным `localhost`.

Предложенный RED sensor выполним и чувствителен к требуемому дефекту. Короткий
канонический task-owned Unix socket, отдельный PHP child с проверенным
`mysqli.default_socket`, три case-варианта `localhost` и счётчик accept дают
внешнее наблюдение через public factory: текущая реализация делает одно socket
соединение, исправленная — ноль, при одинаковом typed `UNAVAILABLE`. Отдельный
direct-mysqli child с ожидаемым accept=1 доказывает активность ловушки. Отсутствие
MySQL greeting исключает передачу authentication payload; bounded
`stream_select`, monotonic deadlines, reap/termination и identity-checked cleanup
делают проверку конечной и изолированной. Контракт не требует real credentials,
системного socket, sleep, interception, permission trick или private seam.

Порядок проверок задан точно: host validation предшествует password content и
mysqli, а config construction остаётся passive/lazy. Транспортный счётчик
доказывает отсутствие mysqli connection; проверка исходного кода на Gate 5
отдельно подтверждает полное локальное упорядочение относительно password read и
`mysqli_init`/`real_connect`. Это разделение доказательств прямо записано и не
создаёт ложного утверждения, будто socket sensor наблюдает file read.

Добавленное в v0.7 повторное чтение того же fingerprint при впервые замеченном
step-11 drift не вводит новый outcome. Оно точно фиксирует уже отдельно
утверждённую precedence гонки: validated ACCEPTED даёт `REPLAYED` под текущим
requestId; validated miss допускает `STALE_REVISION`; malformed/unavailable даёт
persistence failure. На unchanged-current path дополнительного lookup нет;
allocation, finalize и loser terminal/audit/event запрещены. Это совместимо с
неизменённым worker oracle и не переоткрывает его независимый review.

## Граница решения

**APPROVED** разрешает переход этой точной пары спецификаций к минимальному
transport RED и независимому Gate 3. Решение не утверждает ещё не написанный
тест, реализацию, DATA-INTEGRITY Gate 5, combined command или launch readiness.
При изменении любого из двух файлов требуется новый Gate 1 review с новыми
хешами.
