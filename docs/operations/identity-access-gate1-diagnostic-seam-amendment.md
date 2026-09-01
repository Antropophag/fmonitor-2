# IDENTITY-ACCESS-SCHEMA-001 Gate 1 diagnostic-seam amendment

- Date: `2026-09-01`
- Owner decision: `APPROVED`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`

Owner согласовал разделение наблюдаемости migration contract:

- operator CLI `php bin/fmonitor2-migrate.php` остаётся redacted и не раскрывает
  table identifiers, SQL или catalog diagnostics;
- exact ordered conflicting/missing/created lists проверяются через public
  migration application result object до CLI redaction;
- v6-dependent unexpected-failure и post-v6 short-circuit assertions должны
  быть написаны и независимо reviewed в RED-пакете, но впервые исполняются после
  minimal v6 GREEN; expectations нельзя менять ради GREEN.

Это amendment уточняет test seams и не меняет nine-table ownership, literal
version `6`, partial recovery, conflict semantics, prefix contract или границу
`GRILL-002`.
