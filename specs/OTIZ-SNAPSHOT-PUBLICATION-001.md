# OTIZ-SNAPSHOT-PUBLICATION-001

## Простыми словами

Подготовка расчёта сохраняет черновик целиком. Ошибка не оставляет принимаемый
недостроенный результат. Принятие остаётся явной командой. Формулы не меняются.
Владелец2026-09-08 подтвердил план и запуск реализации словом «го».

## Public seam and scope

`FMonitor2\Otiz\SnapshotPublication::buildAndPublish(int actor, string reportDate,
string operationId): int`, `accept(int actor, int snapshotId): void`,
`read(int actor, int snapshotId): array`, `history(int actor): array`.
Composition предоставляет MariaDB storage, read adapter входных native facts и clock.
HTTP: существующие POST `/pilot/otiz/calculate`, `/pilot/otiz/snapshots/{id}/accept`;
GET snapshot/history/export остаются текущими. HTTP не создаёт предметные факты.

Read возвращает snapshot, objects, allocations, issues, events и publication metadata;
history возвращает список headers с identity/status. Записи audit доступны в read.
Все команды/read требуют активного пользователя с текущим `otiz.manage`. Роли не
расширяются. HTTP дополнительно проверяет server session и CSRF.

ReportDate — точная действительная YYYY-MM-DD; operationId — UUID в lowercase
каноническом представлении. Actor/operationId сохраняется при повторе запроса.
Ошибки: FORBIDDEN, INVALID_DATE, INVALID_OPERATION_ID, OPERATION_CONFLICT,
NOT_FOUND, IMMUTABLE, BLOCKERS, SNAPSHOT_INCOMPLETE. Некорректный input и denial
не меняют бизнес-историю. Техническая ошибка не выдаётся за успешную публикацию.

## Atomicity, concurrency and replay

Новые header/objects/allocations/issues/totals/content_hash, publication receipt и
одно `draft_calculated` фиксируются одной транзакцией. Все чтения входов, прошлых
accepted и closures принадлежат одному consistent cut. Читатель другого соединения
до commit не видит новый draft. Любой отказ до commit откатывает всю публикацию.
Нет глобального lock всех объектов и внешних side effects внутри транзакции.

Повтор actor/operationId с той же датой возвращает тот же id без повторного события,
включая concurrent requests и потерю ответа. Другая дата с тем же ключом даёт
OPERATION_CONFLICT. Другая новая операция на ту же дату допустима: A02 не закрыт.

Accept под lock snapshot сначала проверяет существование и draft status, затем
доказательство полной публикации и открытые blockers. Legacy draft без receipt,
pending hash или изменённые/отсутствующие строки дают SNAPSHOT_INCOMPLETE.
Принятие записывает accepted actor/time и единственное snapshot_accepted атомарно.
Повтор возвращает IMMUTABLE. Два конкурента не создают два accepted события.
Accepted legacy history остаётся читаемой и неизменной, без фиктивного backfill.

## Manifest v1

Receipt не является источником проверяемого содержимого: digest повторно строится
из DB rows. В JSON верхний порядок ключей: snapshot, objects, allocations, issues.
Header исключает status, accepted_at, accepted_by_user_id. Все остальные columns
выбираются в schema order; строки object упорядочены object_id, allocations —
object_id/tab_id/id, issues — object_id/id. Значения SQL нормализуются в string
или null независимо от режима mysqli; JSON-поля остаются сохранёнными строками.
JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES; без whitespace. SHA-256 lowercase.
Receipt хранит manifest_version=otiz-publication-v1, digest и три row counts.
Полнота не выводится из hash pending или из отсутствия blockers.
Для primitive canonicalization golden JSON: {"snapshot":{"id":"7","previous_snapshot_id":null},"objects":[],"allocations":[],"issues":[]}.
Этот пример задаёт типы/сериализацию; production header имеет полный набор колонок.

## Independently worked examples

Основание формулы: `rapid-pilot/OTIZ-PLAN.md`, раздел расчётной модели:
фонд = премия × Кшах; начисление = фонд × прогресс × Ксс; пул = начисление − закрыто.
Синтетические входы теста (не реальные кадровые/платёжные данные):

- Объект101: премия10000коп, Кшах1, прогресс85%, просрочка0, закрыто0.
  Фонд10000, начислено8500, пул8500; два равных подтверждённых участника по4250.
- Объект202: премия20000коп, остальные входы такие же.
  Фонд20000, начислено17000, пул17000; два участника по8500.
- Итого: пул25500, закрыто0, доступно25500, два объекта, четыре распределения,
  ноль issues, одно событие публикации. Никакое expected число не вычисляется
  вызовом переносимого calculator в тесте.

## Failure evidence and checks

A01 сначала воспроизводится через unchanged router с настоящими login/session/CSRF:
ошибка загрузки inputs после header, затем попытка accept. Assertion ожидает,
что incomplete не принят; старый код его принимает, что составляет RED.
Private-prefix fixture и failure injection не изменяют реальные данные/исходники.

Focused application tests: нормальный пример, ошибки input/авторизации, ошибка
после header, после первого объекта и перед receipt, второй connection до commit,
изменение соседнего входа во время чтения, replay/conflict, повреждённые rows,
legacy incomplete, acceptance replay. Failure injection через test DB triggers
и read adapter, не через production fault flags. Read/history служат наблюдателями;
SQL используется для fixture/контролируемого повреждения и дополнительной
проверки сохранения количества всех persisted rows при rollback (включая orphans);
основной пользовательский результат проверяется через read/history.

Обычный HTTP не выполняет DDL. Канонические additive migrations сохраняют данные
и допускают повторное применение. Неполная/неподготовленная схема даёт readiness
failure. Полный CI и независимые G3/G5 фиксируются отдельно; этот документ их не
подменяет. A02/A03, runtime #33 и смена политики выплат вне данного среза.

Golden полного пустого snapshot id1 при clock2026-09-08T15:00:00+03:00:
```json
{"snapshot":{"id":"1","report_date":"2026-09-08","previous_snapshot_id":null,"rules_version":"premium-calculation-v1","calculated_at":"2026-09-08T15:00:00+03:00","calculated_by_user_id":"1","total_pool_cents":"0","total_closed_cents":"0","total_available_cents":"0","content_hash":"579a023e39eed68956cc5781be52a2de2af6de46896ed82a97d2a5e70e09f871"},"objects":[],"allocations":[],"issues":[]}
```
SHA-256: `afe8736fcf02174a47e94fdb7676699ef0b9af006c04484b1a7560ea57a4742a`. Ожидание вычислено отдельно
из literal JSON, до реализации; не получается из actual result/calculator.
