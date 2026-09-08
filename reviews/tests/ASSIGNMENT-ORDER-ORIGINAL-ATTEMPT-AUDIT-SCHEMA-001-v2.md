# Независимый Gate 3 rereview: ATTEMPT-AUDIT schema v2

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `c41cdac924ea6aa68c92f4fd10b6a99ac52a552c`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `13f2733fa565396d2bc0bac1d581c5ac2fb59e529eb046659a04b187c9436547`
- Unchanged support SHA-256: `e88beb48c2499b54f57630204e5ff596fbde4ae1664db86b38ff47607338e06e`
- Verdict: **APPROVED**

Rereview ограничен исправлением двух findings из
`ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-SCHEMA-001-v1.md`. V1 review
сохраняется неизменным. Reviewer не писал test/support/source.

Named-lock contention теперь требует завершения раньше5.5 секунд при
нормативном timeout5 и явно ограниченном scheduler allowance0.5. Отдельный
connection реально удерживает exact lock, а session statement fuse остаётся8.
Таким образом timeout6–7 больше не может пройти; fixed unavailable, no-DDL
catalog и release владельца по-прежнему проверяются без hook/interception.

FK oracle теперь независимо строит восемь exact ожидаемых ссылок из literal
logical owner/local column/target/target column. Для каждого имени вычисляется
`fk_ao_` плюс первые48 lowercase hex SHA256 от exact
`prefix + NUL + logical-owner + NUL + local-column`. Actual inventory читается
из `KEY_COLUMN_USAGE` и `REFERENTIAL_CONSTRAINTS` и включает name, physical
owner, local column, mapped referenced table/column и обе RESTRICT rules.
Отфильтрованный полный inventory сортируется и сравнивается exact с восемью
ожиданиями; неверный hash input, duplicate, missing/extra FK или только короткое
правдоподобное имя тест больше не пропустит.

Остальные v1 findings отсутствовали и соответствующие tests byte-semantically
не расширены. Consumer prefix25 case всё ещё исключён из этого approval: его
retained failure происходит в predecessor v2 setup и пока не доказывает
consumer-specific misaddress. После naming GREEN нужен отдельный фактический RED
до изменения maintenance/evidence consumers.

Final retained archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-schema-red-v2-8dbr6w2n`.

```text
0778c7a3eedad768c9eddc6ac1e83e137b34514c9e0d2390e9763bed492b9860  evidence.json
f958f75aeec457c86a74a0d4ac243d27599772896a1365e0e108223c4935b7e3  red.log
```

Manifest фиксирует exact clean source HEAD и новые test hashes. Run exit255:
21 cases, три valid controls PASS, 18 FAIL по прежним отсутствующим migration,
naming, v5 recognition и canonical13 причинам. Source/schema не менялись.

**APPROVED** разрешает minimal schema/naming/v5/canonical GREEN против exact
reviewed expectations. SQL audit writer, consumer correction, pure command,
Gate5, combined command и launch readiness не входят в verdict.
