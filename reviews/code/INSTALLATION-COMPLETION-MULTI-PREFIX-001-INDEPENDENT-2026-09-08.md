# Independent code review — installation completion multi-prefix

Verdict: **APPROVED** for the bounded v10/v17 compatibility correction.

Reviewer: `/root/bootstrap_review`, independent from the implementation author.
Review date: 2026-09-08 Europe/Moscow. Source HEAD:
`8c0cc63f0454b0a620dafa399d5d2462d8447feb`.

Reviewed production hashes:

- `InstallationCompletionDefinitionSchemaMigration.php`: `312551ba2fdb580d5670418daa042958cf91332f163f3fa890e063cfd562e9da`.
- `InstallationCompletionSchemaMigration.php`: `b08e93daaa38a8d21aa97b8d89fbaab4fce629fa52a8a5db72aec2ecb9da7a0b`.
- `InstallationCompletionDetailsSchemaMigration.php`: `f1a40c30b5378e1787decc9809bd85d38e83764e9c36393547fe7643240e6e6e`.

MariaDB foreign-key symbols are database-wide, so fixed symbols prevented a
second prefixed canonical namespace in the same database. New non-empty-prefix
DDL now derives both symbols from the already validated prefix. The longest
allowed symbol is 58 bytes, below MariaDB's 64-byte limit. Empty-prefix DDL keeps
the historical symbols byte-for-byte.

Readiness accepts either the complete scoped manifest or the complete historical
manifest for existing prefixed schemas. Exact fingerprint comparison still rejects
mixed, missing, additional or renamed keys and preserves all columns, indexes,
targets, actions, checks, engine and collation checks. V17 mirrors the same two
allowed correction manifests and does not rewrite historical data.

No blocking findings.
