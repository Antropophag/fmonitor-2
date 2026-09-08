# Независимый Gate 3 review: ATTEMPT-AUDIT naming consumer v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `a9245a9af03442714a23be30de69360422a15c47`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Test SHA-256: `13f2733fa565396d2bc0bac1d581c5ac2fb59e529eb046659a04b187c9436547`
- Support SHA-256: `e88beb48c2499b54f57630204e5ff596fbde4ae1664db86b38ff47607338e06e`
- Verdict: **APPROVED**

Этот review квалифицирует только case
`prefix25-maintenance-evidence-consumer`, ранее исключённый из schema Gate3.
Reviewer не писал test/support/source.

Final archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-schema-naming-consumer-red-x8_zgivu`.

```text
fd42d3c7122ea5cfb4e2ea1fff3956f6a4092acb95b27e717dd7d280a0a1eee3  evidence.json
3b05fc96f46a1904e00e66bfa19aae1eef2c1c7b94fae459c13a7571ff76d5a2  red.log
```

На exact source SHA public v2 setup prefix14/15/16/17 проходит, capability-v5
recognition и alias-conflict controls проходят. При prefix25 public v2 setup
теперь успешно создаёт mapped family. Затем public maintenance verification
factory достигает реального consumer SQL и падает с exact native причиной:

```text
Incorrect table name 'mmmmmmmmmmmmmmmmmmmmmmmmmfm2_assignment_order_original_maintenance_requests'
```

Это уже не predecessor setup failure: production maintenance consumer всё ещё
склеивает prefix с длинным logical suffix вместо общей v0.4 physical mapping.
Test ожидает точный COMPLETED result, одну request/audit evidence row, replay и
неизменность evidence. После maintenance write тот же public evidence reader
должен прочитать обе mapped tables. Таким образом один case чувствителен к обоим
обязательным consumers и не добавляет новый API или matrix.

Run целиком сохраняет 9 PASS/12 FAIL; остальные 11 failures относятся к ещё
отсутствующим v3/canonical13 behaviors и не используются как consumer evidence.

**APPROVED** разрешает минимально применить уже утверждённый pure shared mapper к
maintenance и evidence SQL owners. Нельзя вводить runtime DDL, existence
fallback, dual-read/write или отдельную mapping policy. Schema implementation,
native audit writer, Gate5, combined command и launch readiness не утверждаются.
