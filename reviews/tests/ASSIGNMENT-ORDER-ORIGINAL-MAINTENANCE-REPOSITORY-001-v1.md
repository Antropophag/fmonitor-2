# Независимый Gate 3 review: MAINTENANCE repository v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `04efeab79ac59f5cd41282339f438f9f2d0485b2`
- Specification SHA-256: `d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e`
- Test SHA-256: `1b666f6d1f07136f4a205d4e6106ca6b8318ac222f008b4cb0ed42c6e3baa0eb`
- Verdict: **APPROVED**

Public concrete repository is обязательный seam. Real controls доказывают, что
existing service ошибочно replay-ит request без audit и с нарушенной count sum;
valid backing проходит. Native cases требуют atomic request+audit, immutable
collision, exact read/counts, invalid DTO/prefix zero SQL, active caller
transaction ownership и before/after commit/read-release classification.

Valid PARTIAL/REJECTED native paths, pagination после удалённого cursor и exact
physical evidence уже остаются в неизменённом approved
`assignment_order_original_maintenance_001_test.php`; их reuse допустим и не
подменяется private fixture. Поэтому новый test не обязан дублировать эту
матрицу.

**APPROVED** разрешает minimal MariaDB repository GREEN без расширения schema или
public API.

## Общее RED evidence

Archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-maintenance-owner-red-ztd88nf4`.

```text
c88526ba71edc6c02677997072745e2655a96d95eaba97184de09cee4065ce40  evidence.json
1c0898d5b04c1eadccdb52cf264877e6adf9417916193119db1c27306fd04f49  owner log
9d8bbc58921236db7e816294dc8046749c93efcc1f7e809d89f89342352f0558  storage log
d7180e688db5e74815a7ad439c674a5e5321c55e5873c8ce28d169c7cf597e7c  repository log
```

Manifest `complete=true`: три existing controls PASS и 59 intended FAIL.
Решения не утверждают implementation, Gate 5, combined command или launch.
