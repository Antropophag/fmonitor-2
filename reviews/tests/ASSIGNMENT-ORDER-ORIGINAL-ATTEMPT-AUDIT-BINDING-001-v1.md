# Независимый Gate 3 review: ATTEMPT-AUDIT binding v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `a9245a9af03442714a23be30de69360422a15c47`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `0fbc1ab8662449757099e0714cdfb02c16bd72efc051b33f75a4bfd3db0f7626`
- Worker support SHA-256: `59080e25e57d14cf6274c373d2411c98cfdaa5c95bcb3631cf5d2e82b0c9df81`
- Verdict: **APPROVED**

Reviewer не писал tests, helper или production source. Review ограничен двумя
добавленными cases `production-factory-records-denial` и
`real-worker-records-denial`; прежние 39 native writer/reader cases сохраняют
отдельное Gate 3 approval.

Production case вызывает actual public `createRecoveryReady` с real borrowed
MariaDB connection, owned private root и safe-log, а fresh factory заменяет
только явный spy dependency. Valid-shape actor999 действительно denied через
real authorizer. Ожидаются exact REJECTED/AUTHORIZATION_DENIED, одна physical
audit row с caller echo и время внутри независимо снятых UTC bounds. Stream
read0/close1 и fresh opens0 доказывают отсутствие file/confidential recovery
пути. Case чувствителен именно к обязательному real audit-writer binding, а не к
private factory detail.

Worker case вызывает existing public worker entry с canonical0600 config,
password/safe-log и task-owned private root. Command/result используют exact
11-key protocol, actor999 и fixed application clock. Четыре distinct Unix
socketpairs передают command/release/barrier/result FDs вместе со стандартными
stdio; command input закрывается после полной записи, unused release direction
получает EOF. Bounded `stream_select`, 8-second monotonic deadline, output cap,
termination и reap/close в `finally` предотвращают зависший child или утечку FD.
Expected result и persisted audit exact связывают worker composition с тем же
real writer, actor и injected time.

Final retained archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-factory-worker-red-5z29kct2`.

```text
4337d76c033f782fed3f323b72b46ca70d769704d7a69680ea98e89c48c9407a  evidence.json
f7212a9b89aa8ce4fb70c868fa516f68fa29e5081873cbedab02b9ecf6362d33  red.log
```

Manifest фиксирует полный dirty production hash inventory и
`sameSourceAfter=true`: test run не менял source. Результат 41 cases — 39 PASS,
два intended FAIL. Оба новых seams завершаются exit0 без setup/transport error,
но возвращают exact старый FAILED/PERSISTENCE_FAILURE вместо confirmed denial;
это показывает использование degraded default writer. Native writer/reader,
transaction и race controls остаются GREEN.

**APPROVED** разрешает минимально связать real
`AssignmentOrderOriginalMariaDbAttemptAuditWriter` в production factory и
worker, сохранив safe-log/host/password/DB ordering и fresh dependency. Нового
public mutator или writer selector не требуется. Решение не утверждает resulting
GREEN, code review, combined command или launch readiness.
