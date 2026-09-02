## 1. Gate 1 — executable schema contract

- [x] 1.1 Собрать exact evidence predecessor table, populated rows, landed v1–v9 catalogue, proposed correction member, MariaDB constraints/collation и full-catalogue prefix arithmetic; verification: evidence не выводит schema из production implementation под test.
- [x] 1.2 Написать executable `INSTALLATION-COMPLETION-SCHEMA-001` с exact manifests, allowed predecessor/partial states, preservation, correction-chain invariant, runtime outcomes и target 85/15/declaration meaning; verification: spec hash и все rejected cases определены.
- [x] 1.3 Поручить fresh independent Gate 1 reviewer проверить executable spec против owner decision, evidence и architecture; verification: verdict `READY_FOR_OWNER_APPROVAL`, reviewer не автор artifacts.
- [x] 1.4 Получить explicit owner approval exact reviewed spec/hash до test edits; verification: отдельная approval record, после которой Gate 2 разрешён.

## 2. Gates 2–3 — RED и independent test review

- [x] 2.1 Fresh RED author добавляет MariaDB matrix для clean/repeat/populated root-only predecessor/exact complete/reverse-partial conflict/metadata conflict/collation/prefix/decoy isolation и deterministic output; verification: qualifying RED показывает отсутствие canonical owner без setup failure.
- [x] 2.2 Добавить RED для lossless roots, empty correction ledger, single-successor chain constraints, rejected branch и DDL-denied ObjectQueue/card/checklist/completion/bootstrap runtime; verification: failures соответствуют spec, existing history не меняется.
- [x] 2.3 Поручить fresh independent test reviewer проверить literal independence, metadata accuracy, traceability, mutation snapshots и runtime reachability; verification: `reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md` имеет `APPROVED`, иначе tests возвращаются в Gate 2 и проходят fresh rereview.

## 3. Gate 4 — minimal GREEN

- [x] 3.1 Реализовать exact completion-family migration с validated explicit database-default collation, family-wide preflight, additive predecessor upgrade и restartable partial completion; verification: approved schema matrix GREEN без test edits.
- [x] 3.2 Зарегистрировать следующую literal version после exact landed catalogue и применить утверждённый full-catalogue prefix ceiling до DB access; verification: composed runner clean/repeat/prefix matrix GREEN.
- [x] 3.3 Заменить completion runtime DDL единым read-only readiness seam во всех consumers, не добавляя domain logic в rapid-pilot; verification: DML-only runtime matrix и ObjectQueue prerequisite GREEN, missing/drift fail closed.
- [x] 3.4 Обновить architecture ratchet только на реально удалённый completion DDL/mutation debt; verification: `make architecture-check` GREEN без расширения baseline.

## 4. Verification и Gate 5

- [x] 4.1 Запустить focused completion, queue, checklist, planning-v9 и canonical runner tests, lint, built-image/fresh lifecycle и зафиксировать результаты; verification: owned suites GREEN, unrelated failures классифицированы без ослабления assertions.
- [x] 4.2 Запустить `make verify`; verification: integration GREEN либо только заранее доказанный unrelated debt разложен по approved slices с честным evidence.
- [x] 4.3 Поручить fresh independent code reviewer проверить Standards и Spec axes, approved tests, production diff и verification evidence; verification: `reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md` имеет `APPROVED`, test changes возвращают slice в Gate 2.

## 5. Done

- [x] 5.1 Обновить operations status/backlog и закрыть slice только после strict OpenSpec validation, Gates 1–5 и снятия ObjectQueue DML-only blocker; verification: runtime completion DDL отсутствует, history preserved, planning v9 full verifier проходит этот prerequisite.
