## 1. Contract

- [x] 1.1 Утвердить executable ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001; verification: independent Gate1 exact hash, stale outcomes и lock owner однозначны.

## 2. RED and review

- [x] 2.1 Доказать native selection→direct original RED через public constructor/application; verification: valid synthetic setup, exact composition и no-template footprint.
- [x] 2.2 Проверить replaced target и обе стороны replace/upload race; verification: exact outcomes, общий case lock и отсутствие crossed history.
- [x] 2.3 Получить independent Gate3; verification: explicit APPROVED для tests/evidence до production изменений.

## 3. Implementation

- [x] 3.1 Связать preflight и locked query с shared registered validators; verification: reviewed direct-original cases GREEN, existing constructors не переключены.
- [x] 3.2 Сохранить correction/replay и terminal/audit stale mapping; verification: accepted81 correction при pending82, exact unchanged source facts и original regression GREEN.

## 4. Completion

- [x] 4.1 Зафиксировать exact-SHA regression/architecture/OpenSpec evidence; verification: все команды terminal PASS, baseline unchanged.
- [x] 4.2 Получить independent Gate5 и связать parent tasks; verification: explicit APPROVED, standalone binding не подменяет full portal VERIFY_OK/deploy.

Gate1 source73b05d1 APPROVED: `docs/operations/selected-original-binding-gate1-2026-09-06.md`.
Planning-only checkpoint; RED/production ещё не написаны.

First direct-original tracer sourcea0e3784 Gate3 APPROVED:
`reviews/tests/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-tracer-v1.md`.
RED archive `selected-original-red-nuxsw3nd`; both native constructors missing,
real dependencies/setup/cleanup proven. Full task2.3 remains open for race/correction
tranches. Next minimal GREEN binds preflight and locked validation together.

Direct-original binding source9eab3b1 scoped Gate5 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-tracer-v1.md`.
Both constructors accept real direct original from selection, exact composition/
private bytes/replay preserved. Archive `selected-original-green-w7cwj56u`, manifest
e1ae2654cd89a08161514c2e5ddeb5d8acb3831f18ca404b5e11de72c18f473d:
26commands PASS clean, including original/reader/selection regressions/architecture.
Next: replaced target, historical correction, and both directions of case-lock race.
No full integration, canonical registration or portal VERIFY_OK claim.

Final binding Gate5 APPROVED: `reviews/code/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-binding-v1.md`.
Source9eab3b1; lifecycle verificationfdbd634, archive
`selected-original-lifecycle-final-4ru_g0az` (6lifecycle+2direct+4worker regression PASS).
Original denial expectation corrected from inherited ATTEMPT-AUDIT-001 section3;
initial failed expectation archive retained. Canonical activation/routes/full VERIFY
and deploy remain outside this completed application-binding slice.
Package cap exceeded (observation409515 tokens vs60k); scope frozen to reviews/record.
