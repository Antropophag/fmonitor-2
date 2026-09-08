# Identity registry — first public tracer RED

Дата:2026-09-05. Автор test `/root`.
Gate1: `assignment-order-identity-registry-gate1-review-v01-2026-09-05.md`,
APPROVED exact spec `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`.
Base HEAD `7c9303dee000920ec48ec51de932013825fadd94`.

Command: `php tests/InstallationProcess/assignment_order_identity_registry_001_test.php`.
Exit1; exact stdout/stderr assertion:

```text
RED_ASSERTION: public assignment-order identity registry migration is missing
Expected: true
Actual: false
```

До assertion успешно создана task-owned real MariaDB database, применена
public predecessor migration, вставлены literal source rows2/7 и установлен
legacy AUTO_INCREMENT81. Никакой target engine/class ещё не существует.
Это intended missing public seam RED, не include/setup error. Finally удалил
только exact owned database, catalog query подтвердил отсутствие, connection
закрыта fixture owner. Existing/shared database не изменялась.

Тест фиксирует canonical UTC instants, literal receipt hashes, preserved source
rows/catalog/counters, repeat и позднюю compatible legacy identity выше frozen
historical subset. Source-only fixture SQL не является новым application writer.
Expected values взяты из approved spec, не renderer или production DDL output.

Это первый tracer, не полная mandatory negative/recovery/concurrency matrix.
Для полного engine Gate5 все строки approved matrix сохраняются обязательными,
с отдельным Gate3 для добавляемых tests до соответствующей implementation.
Canonical registration/writer cutover/parent selection Done не заявлены.
`php -l` и `git diff --check` PASS. Independent test review ещё требуется.
