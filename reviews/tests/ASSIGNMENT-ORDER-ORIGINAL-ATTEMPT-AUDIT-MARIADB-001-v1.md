# Независимый Gate 3 review: ATTEMPT-AUDIT MariaDB v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `a9245a9af03442714a23be30de69360422a15c47`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `8a723bce61ed41c53aade571c5669cdf66b8b335b11c9148750b7eeb57035bb5`
- Database support SHA-256: `e88beb48c2499b54f57630204e5ff596fbde4ae1664db86b38ff47607338e06e`
- Race support SHA-256: `691dc471931ebfc174aca6d000d19b0d11824e09eecbeb11b9fdfe3bbff42ab1`
- Race child SHA-256: `aef0d7aec707ffe551421b5c44929c8266e17e1e8513f5b57be682ded9569b3b`
- Verdict: **CHANGES_REQUESTED**

Reviewer не писал tests/support/source. Scope — native audit writer, strict
terminal reader compatibility и две synchronized real database races. Schema
implementation и pure application review отдельны.

## Блокирующий finding

Нет negative test для invalid table prefix до SQL. Sections 2 и 9 требуют
DTO/prefix validation до единственного `SELECT @@in_transaction`; invalid DTO
cases правильно доказывают SQL0, но каждый writer создаётся только с valid
`data_`/fixture prefix. Реализация, которая вообще не валидирует prefix либо
обращается к transaction state/escaping до его отклонения, пройдёт текущие 35
cases. Нужен один компактный direct sentinel case для invalid prefix на обоих
methods: exact ROLLED_BACK, SQL/escaping/observer/transaction0. Новая prefix
матрица не требуется. После поправки нужен exact-hash Gate 3 rereview.

## Остальная assessment

Reader cases проверяют valid extra denial рядом с accepted terminal, оба valid
orphan failure reasons как NOT_FOUND, malformed additional row как UNAVAILABLE и
nonfailure orphan corruption. Existing accepted/nonfailure controls проходят;
reader остаётся read-only и accepted evidence сравнивается exact.

Writer cases фиксируют два denial с одним immutable terminal и двумя audit IDs,
denial после accepted без изменения request/root/revision/event, failure-only
audit без terminal и reader NOT_FOUND. Invalid UUID/positive IDs/date и wrong
method-specific status/reason pairs требуют ROLLED_BACK и zero SQL. Active caller
transaction проверена реальным pending fact для обоих methods: observer0,
transaction остаётся active, pending state неизменен, rollback выполняет caller.

Observer failure matrix различает before-begin/before-commit confirmed rollback,
after-commit UNKNOWN с durable row и rollback-observer UNKNOWN без ложного
commit. Owned transaction всегда закончена; terminal создаётся только committed
recordDenied. Writer не возвращает и не читает confidential terminal payload.

Race harness имеет рабочий existing repository child control. Две native races
останавливают A перед commit и подтверждают вход B в writer before begin; до
release нет видимого terminal/audit. После упорядочивания denial-vs-denial и
accepted-vs-denial имеют один terminal и две независимо attributable audit rows,
а первый terminal сохранён. Child использует bounded select/deadlines/output,
fixed redacted stderr и finally termination/reaping. Исправленная require setup
входит в retained run и не представлена behavioral RED.

Final archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-writer-red-u0h2_5kg`.

```text
c16dd50cb380dc038817d6df37f41bc77292463792c14392796b542aad2e0894  evidence.json
9b1a97f8792414fb3781ea116917d5be757770b49baad189585c07f02351e54d  red.log
```

Run exit255: 35 cases, три controls PASS, 32 intended FAIL. Четыре reader
failures точно показывают новое backing behavior; native writer отсутствует;
existing repository race child проходит и подтверждает fixture/process/observer.

**CHANGES_REQUESTED** относится только к invalid-prefix sensitivity. Tests,
implementation, Gate5, combined command и launch readiness не утверждаются.

