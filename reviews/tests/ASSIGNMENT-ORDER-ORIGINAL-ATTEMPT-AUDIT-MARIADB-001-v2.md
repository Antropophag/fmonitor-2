# Независимый Gate 3 rereview: ATTEMPT-AUDIT MariaDB v2

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `a9245a9af03442714a23be30de69360422a15c47`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `8a4ae5c883b5c909f96d814787e3a8967011245b980a7dbe3f9818479dfa984c`
- Database support SHA-256: `e88beb48c2499b54f57630204e5ff596fbde4ae1664db86b38ff47607338e06e`
- Race support SHA-256: `691dc471931ebfc174aca6d000d19b0d11824e09eecbeb11b9fdfe3bbff42ab1`
- Race child SHA-256: `aef0d7aec707ffe551421b5c44929c8266e17e1e8513f5b57be682ded9569b3b`
- Verdict: **APPROVED**

Rereview ограничен исправлением единственного finding из
`ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-MARIADB-001-v1.md`. V1 review
сохраняется неизменным. Reviewer не писал tests/support/source.

Добавлены четыре cases: `recordDenied` и `appendFailure` для unsafe
`bad-prefix` и ASCII prefix длины26. Каждый использует полностью valid DTO и
`OriginalIntegrityNoSql`, ожидает exact ROLLED_BACK и пустой список всех
query/escaping/transaction calls. Для appendFailure задана валидная exact пара
FAILED/STREAM_FAILURE. Поэтому test теперь отличает prefix validation от DTO
validation и доказывает её выполнение до `SELECT @@in_transaction`, quoting,
observer и owned transaction. Невалидный символ и превышение границы25 покрывают
две независимые причины без расширения prefix matrix.

Остальные reader, native transaction, observer-failure и synchronized race
oracles не менялись и сохраняют оценку v1. Final retained archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-writer-red-v2-6pdo44k3`.

```text
8dcc95cb4d5a3caf44cb61c40496270c5ad1b6ade7bb06e9d05510f3a8c94a59  evidence.json
a7fb17ff83be84becb0ad7bafd223478a1bdc16b4cf18d9846456e63aba1587a  red.log
```

Manifest фиксирует exact source/spec/test/support hashes. Run exit255: 39 cases,
три действующих controls PASS и 36 intended FAIL. Четыре новых prefix cases
падают на отсутствующем native writer, source reader/writer неизменён; причины
остальных failures совпадают с v1. Schema GREEN состояние не используется как
ложное доказательство writer behavior.

**APPROVED** разрешает minimal native writer/reader GREEN на exact reviewed
expectations. Pure command, schema code review, naming consumers, Gate5,
combined command и launch readiness не входят в verdict.
