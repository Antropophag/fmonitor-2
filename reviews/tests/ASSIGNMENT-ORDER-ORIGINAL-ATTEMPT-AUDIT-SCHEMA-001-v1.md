# Независимый Gate 3 review: ATTEMPT-AUDIT schema v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `c41cdac924ea6aa68c92f4fd10b6a99ac52a552c`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `561e481ffd8ec2845b54d554a29ca699597b2186cc6e13bf9f864e584432ff87`
- Support SHA-256: `e88beb48c2499b54f57630204e5ff596fbde4ae1664db86b38ff47607338e06e`
- Verdict: **CHANGES_REQUESTED**

Reviewer не писал specification, tests, support или production source. Scope —
sections 6 и 8–11: migration v3, physical naming, migrations3/4 recognition и
canonical registration13. SQL attempt-audit writer и pure command не входят.

## Блокирующие findings

### 1. Named-lock test не чувствителен к нормативному пределу 5 секунд

Section 6 разрешает ожидание named lock не дольше5 секунд. Test устанавливает
`max_statement_time=8` и требует только wall time `<8s`. Реализация с
`GET_LOCK(...,7)` удовлетворит тесту, но нарушит specification. Нужна
детерминированная проверка exact timeout argument 5 через допустимый public SQL
observation либо достаточно строгая bounded проверка с обоснованным scheduler
tolerance, которая отвергает 6–7 секунд и сохраняет реальный contention control.
Не требуется новая contention matrix.

### 2. FK test не проверяет exact deterministic identifier

Section 11 требует имя каждого нового FK ровно `fk_ao_` плюс первые48 lowercase
hex SHA256 от `prefix + NUL + logical-owning-suffix + NUL + local-column`.
Physical-prefix cases проверяют только непустой inventory и `strlen<=64`.
Любое другое короткое имя, неверный input hash или одинаковое имя для разных
FK прошло бы. Нужна независимая literal/hash oracle для exact expected names как
минимум на boundary/prefix25 family и проверка уникального полного inventory.
Это не расширение поведения: exact имя уже нормативно и нужно для repeat/
metadata совместимости.

После точечной коррекции этих двух assertions требуется новый exact-hash Gate 3
rereview и новый retained RED только если изменения затрагивают исполняемый
failure path.

## Остальная assessment

Public migration/verification seams проверяют populated-v2 preservation,
nonunique audit index, снятый FK, только две новые FAILED reason pairs,
repeat, empty/leading-partial completion и unknown drift с полным before/after.
Active caller transaction удерживает реальный pending fact и доказывает fixed
DatabaseUnavailable, сохранение transaction и caller-only rollback.
BEFORE/AFTER ALTER interruptions проверяют exact events, durable state,
повторяемость и release named lock.

Prefix14/15/16/17/25 cases правильно фиксируют threshold aliases и v2/v3 repeat;
duplicate physical alias обязан дать conflict до mutation. Capability-v5
successor требует exact classifier/v3/v4 outputs и полный no-mutation snapshot;
name/literal drift являются рабочими negative controls. Ранний вариант с
replacement literal, нарушавший existing grants, не используется: retained test
добавляет extra literal и достигает ожидаемого conflict.

Canonical cases требуют реальный subprocess result version13, applied13,
clean/repeat, versions1..13 и prefix isolation. Они не допускают marker-only
успех или пропуск migrations3/4/13. Child ограничен monotonic deadline/output,
stderr должен быть пуст, process/pipes закрываются в finally.

`prefix25-maintenance-evidence-consumer` сейчас падает на predecessor v2 setup и
ещё не является demonstrated consumer-specific RED. Он исключён из этого
verdict. После naming GREEN его первый фактический consumer mismatch должен быть
сохранён и отдельно рассмотрен до изменения maintenance/evidence consumers.

## Retained RED

Archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-schema-red-kdhj2pfy`.

```text
6d23fb2138dce6a85ee5321311c829025de2adfb0e67a66af55fac1587943733  evidence.json
0147d0606fe62cde8a2ec6ab44a71bda9257988cb9ef9683888702f0de4f0d5f  red.log
```

Manifest фиксирует exact files и exit255. Из 21 cases три valid controls PASS,
18 FAIL. New migration/verification seams отсутствуют; v2 prefix cases дают
typed unavailable/native naming defect; duplicate alias ошибочно считается
UNCHANGED; v5 predecessor и canonical13 отсутствуют. Эти failures относятся к
недостающему поведению. Consumer case имеет только setup failure, как отмечено
выше, и не засчитывается отдельным behavioral RED.

## Verdict boundary

**CHANGES_REQUESTED** относится только к чувствительности двух assertions.
Спецификация v0.4 этим review не отклоняется. Pure command Gate3, SQL writer,
implementation, Gate5, combined command и launch readiness не утверждаются.
