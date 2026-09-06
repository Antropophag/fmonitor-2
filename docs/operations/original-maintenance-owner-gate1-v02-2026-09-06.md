# Независимый Gate 1 rereview: ORIGINAL-MAINTENANCE-001 v0.2

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Specification SHA-256: `d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e`
- Parent ORIGINAL-UPLOAD v74 SHA-256: `c98405ee3ef5506e42b25e220ecd87976ca0bdd552f742f59e9ddc44a1e1ed21`
- OpenSpec design SHA-256: `98c3c7cb3707d63f9879752a15ca3a680e7b8e47008999609a53a00bd5264989`
- Verdict: **APPROVED**

Review ограничен v0.1→v0.2 section 7. Предыдущий v0.1 Gate 1 verdict и
sections1–6 не переоткрывались. Reviewer не писал specification/tests/code.

Concrete adapter теперь конструктивно определён: public final
`AssignmentOrderOriginalMariaDbMaintenanceRepository` реализует уже объявленный
MaintenanceRepository и имеет exact passive constructor
`(mysqli $connection, string $tablePrefix='', ?AssignmentOrderOriginalPersistenceObserver $observer=null)`.
Construction не выполняет I/O и не создаёт второй application mutator.

Transaction precedence однозначна. Invalid commit DTO либо prefix возвращает
ROLLED_BACK до state SQL и observer. Valid DTO на active borrowed transaction
делает ровно один state SELECT, затем возвращает ROLLED_BACK без observer,
BEGIN/COMMIT/ROLLBACK или writes; caller state не изменяется. Reader при active
transaction, state/read/release error возвращает UNAVAILABLE и не управляет
caller transaction. Valid idle path продолжает ранее утверждённые transaction
semantics.

Observer surface не расширен: только existing BEFORE_READ_RELEASE,
BEFORE_WRITE_BEGIN, BEFORE_NATIVE_COMMIT, AFTER_NATIVE_COMMIT и
BEFORE_WRITE_ROLLBACK в соответствующих owned phases, только для verification.
Production constructor использует null. Result snapshot копируется до release;
release failure не публикует mutable/partial result и даёт UNAVAILABLE.

Уточнение достаточно для direct invalid-DTO/prefix zero-SQL и real active-caller
Gate 2 tests через public adapter, без private invocation или новой API policy.
OpenSpec design согласован; strict validation проходит. Новых блокирующих
неоднозначностей нет.

**APPROVED** разрешает native repository RED и независимый Gate 3 на этих exact
хешах. Решение не утверждает tests, implementation, Gate 5, combined command или
launch readiness.
