## Context

См. `proposal.md`, delta spec и
`docs/operations/otiz-snapshot-acceptance-behavior-evidence.md`. Текущий
public transition проходит через LocalAuth/router, после broad `otiz.manage`
конструирует несколько schema-owning pilot collaborators и только затем
проверяет CSRF и блокирует snapshot row. Существующий широкий verifier заранее
bootstrap-ит OTIZ schema и напрямую dispatch-ит класс, поэтому скрывает эту
границу и связывает acceptance с premium calculation fixtures.

Этот change создаёт только characterization harness. Candidate owner будущей
команды — bounded application module `PremiumDecisions` с seam-кандидатом
`acceptPremiumSnapshot`; он не становится утверждённым контрактом до решений
GRILL-001. Future module сможет зависеть от premium contracts/ports, но не от
HTTP/UI, rapid-pilot или concrete MariaDB adapter. Durable persistence будет
принадлежать отдельному adapter, а DDL — canonical migrations; данный change не
создаёт ни то, ни другое.

## Goals / Non-Goals

**Goals:**

- доказать настоящий LocalAuth/session/router/form-CSRF path и точный порядок
  authorization, constructor DDL и business checks;
- изолировать acceptance на literal draft от formulas, evidence calculation и
  payment closure;
- наблюдать exact HTTP/fact outcomes, child-row immutability, replay и реальную
  двухворкерную row-lock serialization;
- обеспечить collision-safe DB/session/process ownership, repeatable normalized
  transcript и cleanup при success/failure;
- зарегистрировать oracle один раз в canonical characterization без новой
  rapid-pilot domain logic или расширения architecture debt.

**Non-Goals:**

- утверждение target acceptance authority, meaning, evidence sufficiency или
  separation of duties;
- проверка premium formulas, `content_hash`, calculation или payment commands;
- canonical premium schema, application seam implementation или persistence
  redesign;
- изменение текущего route, runtime DDL, blocker semantics или replay policy.

## Decisions

### 1. Public seam — реальный loopback HTTP через LocalAuth и router

Harness запускает штатный pilot router с несколькими PHP workers, создаёт
реальные LocalAuth sessions и отправляет URL-encoded POST без автоматического
follow redirects. Assertions связывают status, `Location`, `Cache-Control`,
content type/body с независимыми raw DB snapshots.

Альтернатива — прямой вызов `RapidPilotOtiz` — отвергнута: она обходит auth,
router ordering, session/CSRF exchange и constructor reachability.

### 2. Literal draft отделяет transition от финансовых вычислений

Fixture заранее задаёт все snapshot columns, исходный `content_hash` и child
rows как literals из Gate 1 spec. Он не вызывает calculation/bootstrap workflow
и не вычисляет ожидания production helpers. Полный before/after fingerprint
разрешает измениться только трём acceptance fields и одному новому event.

Альтернатива — переиспользовать широкий OTIZ workflow — скрывает самостоятельный
контракт acceptance и импортирует неутверждённые formula/payment assumptions.

### 3. Constructor DDL наблюдается в отдельном admission track

Private namespace заранее содержит только exact LocalAuth/RBAC prerequisites.
Unauthorized request доказывает отсутствие двенадцати constructor-owned tables;
authorized bad-CSRF request доказывает их создание и conditional
`unique_reversal` до CSRF rejection. Отдельный bad-CSRF namespace заранее
содержит все exact tables, но payment-closures без этого индекса, и доказывает
именно conditional `ALTER TABLE` repair при нулевой иной mutation. Business
scenarios затем используют тот же точно проверенный schema shape. Runtime DDL
остаётся baselined architecture debt, не новым persistence precedent.

### 4. Concurrency требует двух наблюдаемых workers и независимых sessions

Server запускается с `PHP_CLI_SERVER_WORKERS` не меньше двух; preflight требует
разные serving PIDs/connections. Два клиента с отдельными cookie/session/CSRF
освобождаются общей start barrier против одного draft. Oracle сравнивает
winner-neutral multiset success/immutable redirects, один accepted snapshot и
ровно один event; победитель и равенство timestamps не фиксируются.

### 5. Live Moscow clock проверяется раздельными bounds

Harness снимает `Europe/Moscow` whole-second bounds вокруг request и отдельно
проверяет `accepted_at` и event `occurred_at`. Concrete values нормализуются в
transcript; равенство двух independently sampled timestamps не требуется.
Bounded retry при календарной границе сначала удаляет только private namespace.

### 6. Shared session directory требует точного ownership

Каждый запуск резервирует уникальный loopback port, следовательно уникальное
cookie name, и генерирует уникальные validated session ids. PHP запускается с
`session.gc_probability=0`, что подтверждается preflight. Harness snapshot-ит
ambient session decoy, отслеживает точные verifier-owned filenames и после
остановки workers удаляет только их — без glob/sweep каталога.

### 7. Failure contract отделяет environment от regression

Port/worker/PHP/session/DB readiness завершаются `SETUP_FAILURE`; расхождение
HTTP или persisted facts — `REGRESSION`. Один bounded teardown сохраняет
первичную классификацию, удаляет owned SQL/session/process artifacts и отдельно
сообщает cleanup fault. Canonical registration выполняет oracle ровно один раз.

### 8. Architecture impact ограничен verification layer

Изменяются только focused verifier, verification registration и review/status
records. Не добавляются application dependencies, production SQL owners,
rapid-pilot behavior или architecture baseline allowances. Обязательный
`make architecture-check` должен подтвердить отсутствие роста DDL/SQL/mutation
debt.

## Risks / Trade-offs

- [Characterization может выглядеть как одобрение broad `otiz.manage`] → spec,
  transcript и backlog маркируют каждую такую семантику `PILOT_ONLY`, target
  slice остаётся под GRILL-001.
- [HTTP setup выдаёт ложный behavior failure] → явный preflight и раздельные
  `SETUP_FAILURE`/`REGRESSION` outcomes.
- [Concurrency фактически сериализована одним server worker] → distinct-PID
  preflight, две sessions/connections, общая barrier и bounded timeout/reaping.
- [Живой clock создаёт нестабильный transcript] → независимые bounds и
  нормализация concrete timestamps без требования равенства.
- [Shared session cleanup повреждает ambient state] → GC off, exact owned-id
  allowlist и byte-for-byte decoy preservation на success/failure.
- [Constructor DDL превращается в новый стандарт] → он фиксируется только как
  migration debt; architecture baseline не расширяется.

## Migration Plan

1. Завершить и независимо проверить OpenSpec planning package.
2. Подготовить exact Gate 1 executable spec и получить явное owner approval
   только для `PILOT_ONLY` characterization.
3. Fresh RED author создаёт минимальный accepted public-HTTP test; другой fresh
   reviewer утверждает intended RED до GREEN.
4. Минимально реализовать accepted harness, затем отдельным reviewed RED/GREEN
   расширить admission/DDL/rejections/replay/concurrency/isolation matrix.
5. Зарегистрировать oracle один раз, выполнить focused/regression/architecture
   checks и получить отдельный fresh independent code review.
6. Обновить inventory/status, сохранив target acceptance/payment решения в
   NEEDS_GRILL, затем sync/archive OpenSpec change после полного Done.

Rollback удаляет только verifier registration, test harness и lifecycle records;
production behavior/schema/facts не изменяются. После созданных pilot tables
destructive production rollback не применяется: fixtures всегда находятся в
private verifier namespace и удаляются ownership-aware cleanup.
