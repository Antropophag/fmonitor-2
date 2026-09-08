# V12 fixture patch — отдельная граница OTIZ precondition

Дата: 2026-09-05. Автор: `/root`.
В ответ на independent Gate 1 CHANGES_REQUESTED
`canonical-v12-consumer-fixtures-gate1-review-2026-09-05.md` supporting contract
оставляет 11 exact patch targets: десять InstallationProcess tests и calendar
verifier. OTIZ canonical compatibility harness в этот patch не входит.

Это append-only correction к inventory
`object-detail-v12-consumer-fixture-amendment-inventory-2026-09-05.md`:
его рекомендация expand arbitrary subset range для OTIZ не применяется.
Требуется отдельный exact predecessor/expected-appliedVersions contract,
после которого можно исправить реальный retained failure без ослабления checks.
Остальные exact amendments/inventory hashes остаются применимыми.

Parent OpenSpec tasks4.1/4.2 и persistent goal включают OTIZ regression и остаются
незавершёнными. Никакая проверка не удаляется, не пропускается и не объявляется
допустимым failure. Это выполнение независимой READY-части при отдельном
незавершённом contract, а не уменьшение конечной launch goal.
