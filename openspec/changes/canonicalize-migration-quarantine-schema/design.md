## Context

См. `proposal.md` и evidence/review в `docs/operations/`. Семья состоит ровно
из трёх runtime-owned таблиц с двумя DDL owners. MariaDB DDL выполняет implicit
commit; максимальный basename равен 37 байтам, что задаёт family-local prefix
ceiling 27 байт. Composed release-supporting catalogue использует более
строгую границу 25/26 до DB access. Slice следует после ещё не приземлившихся
predecessors.

## Goals / Non-Goals

**Goals:**

- Передать persistence ownership production migration runner и получить строгий
  family-wide preflight до первого DDL.
- Сохранить все совместимые partial/populated states и запретить runtime repair.
- Добавить verifier, различающий setup failure и regression и очищающий только
  собственный namespace.

**Non-Goals:**

- Менять quarantine classification, decision semantics или authorization.
- Добавлять foreign keys, JSON constraints либо redesign persistence.
- Выполнять импорт, backfill, reconciliation, retention или cleanup данных.
- Фиксировать literal migration version до приземления predecessors.

## Decisions

1. **Один family migration с symbolic ordering.** Публичная migration seam —
   `bin/fmonitor2-migrate.php`; canonical persistence owner размещается в
   `app/InstallationProcess` и регистрируется в его migration catalogue по
   существующему runner pattern. Allowed dependencies ограничены DB
   abstraction/schema fingerprint primitives. Имя owner-класса и реальная
   версия выбираются после predecessors. Альтернатива — три версии — увеличивает
   устойчивые partial states и не даёт family-wide conflict guarantee.
2. **Read-all preflight, затем create-missing.** Сначала снимается fingerprint
   каждого существующего члена, и только при полном успехе создаются
   отсутствующие в детерминированном порядке registry → observations →
   decisions. Это сохраняет restartability при implicit commits. `CREATE TABLE
   IF NOT EXISTS` без preflight отвергнут как скрывающий conflicts.
3. **Семантический fingerprint.** Сравниваются columns, engine, resolved
   collation и ordered indexes, но не presentation names. Plain TEXT JSON и
   отсутствие FK/CHECK сохраняются как наблюдаемый contract, без усиления
   domain semantics.
4. **Runtime adapter только требует schema.** Rapid-pilot registry/ledger остаются
   временными behavioral adapters; создание DDL удаляется, а общий точный
   precondition вызывается до data/business transaction. Новая domain logic в
   rapid-pilot не добавляется.
5. **Полная state matrix.** Gate 1 verifier покрывает восемь compatible
   present/absent состояний и отдельные конфликты по каждому member/классу
   fingerprint. Verifier использует уникальный prefix, literal rows, decoys,
   counters, repeat и cleanup на success/failure.
6. **Architecture ratchet уменьшается.** Runtime DDL удаляется без baseline
   exception; `make architecture-check` подтверждает границу.

## Risks / Trade-offs

- [Predecessors меняют version и catalogue ceiling] → непосредственно перед
  Gate 1/RED обновить literal catalogue evidence; до этого ordering symbolic.
- [Environment выбирает другую utf8mb4 collation] → preflight проверяет
  разрешённое окружение, migration эмитирует уже разрешённое точное значение.
- [Implicit commit оставляет partial family] → каждый повтор начинает с полного
  preflight и создаёт только missing members.
- [Совместимая по форме, но невалидная JSON data] → migration не валидирует и не
  переписывает данные; это отдельная behavior/data-quality работа.
- [OTIZ constructor сейчас создаёт DDL рано] → removal следует только после
  clean/partial/conflict и DDL-denied runtime GREEN.

## Migration Plan

1. После landing predecessors закрепить версию, runner order, composed 25/26
   pre-DB-access boundary и сохранить family-local 27/28 evidence.
2. Через обязательный SDD/TDD workflow утвердить executable spec, доказать RED и
   получить независимый test review.
3. Добавить family migration/preflight и verifier; зарегистрировать runner.
4. После GREEN удалить оба runtime DDL owner, включить fail-closed precondition и
   выполнить focused regression, architecture-check и full verify.
5. Получить независимый code review. Rollback кода допустим до использования
   нового runtime; additive tables/data не удалять, а оставить следующему
   совместимому migration fix.
