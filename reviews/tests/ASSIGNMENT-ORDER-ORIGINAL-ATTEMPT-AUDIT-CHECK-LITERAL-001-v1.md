# Независимый Gate 3 review: ATTEMPT-AUDIT quoted CHECK literals v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `dd15fe23c6fa2ea4901ee18e6e580a1ff2862137`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `d1bc95579266106b22781095adbe8fc4736f17439eacefe6ce5aec84b525fb2b`
- Canonicalizer source SHA-256: `60b1b62516122f2e983483cc240382e6c0371cf93f267819b632f4dca9692a56`
- Verdict: **APPROVED**

Reviewer не писал test, source или specification. Scope ограничен точным Gate 5
finding: quoted literal bytes не должны исчезать при CHECK normalization.

Все четыре cases используют public `OriginalAttemptAuditSchemaMigration::apply`,
сначала создают валидную v3 family, затем изменяют ровно один owned pair CHECK
через проверенное safe constraint name. Private normalizer не вызывается.

Formatting control добавляет только внешние parentheses/whitespace и ожидает
UNCHANGED. Quoted-case control меняет байты literal и ожидает conflict, поэтому
тест не допускает case-fold quoted values. Два defect sensors вставляют internal
space либо backtick внутрь `authorization_denied` и независимо ожидают exact
`SCHEMA_MIGRATION_CONFLICT`. До/после каждого repeat/conflict сравниваются полный
SHOW CREATE и все facts, исключая repair, DDL или data mutation.

Final retained archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-audit-check-literal-red-39xa9odj`.

```text
bedfb8b4bb271de3fb8efe9761b3c25f757cdf4fe09395cf68fb3bc605674587  evidence.json
38e13af9dd02b77ff4aadf6ee3c922addc66167db7e4132bf90608a7d918139f  red.log
```

Manifest фиксирует exact source/spec/test/support hashes. Run exit255: formatting
и quoted-case controls PASS; quoted-space и quoted-backtick FAIL, потому что
current public migration ошибочно возвращает UNCHANGED вместо conflict. Это
точный behavioral RED, не setup failure.

**APPROVED** разрешает минимальную quote-aware correction общего CHECK
canonicalizer без изменения API, specification или допустимой SQL formatting
grammar. Решение не утверждает GREEN, Gate5, combined command или launch
readiness.
