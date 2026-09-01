# IDENTITY-ACCESS-SCHEMA-001 Gate 1 UCA-alias amendment

- Date: `2026-09-01`
- Owner decision: `APPROVED`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`

MariaDB 11.4.7 reports database default `utf8mb4_uca1400_ai_ci`, while
`information_schema.COLLATIONS` exposes the corresponding UCA alias as
`uca1400_ai_ci` with nullable character-set metadata. Owner approved this
normalization:

- database default charset remains exactly `utf8mb4`;
- reported collation name must pass the safe identifier regex;
- membership accepts an exact utf8mb4 row or the documented UCA alias obtained
  by removing `utf8mb4_`;
- exact reported default must pass safe trial application to utf8mb4 before the
  first target DDL;
- unknown aliases and non-utf8mb4 databases remain redacted
  `DATABASE_UNAVAILABLE` with zero identity mutation.

This amendment does not relax existing-table collation compatibility and does
not permit conversion, seed, repair or RBAC behavior changes.
