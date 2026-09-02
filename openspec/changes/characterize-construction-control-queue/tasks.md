## 1. Gate 1 — executable spec и owner approval

- [x] 1.1 Создать `specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md` с literal synthetic actors/cases/pages, exact public GET/HEAD outcomes, stable transcript, state fingerprints, four per-slot SELECT-only MariaDB principals и independent write-attempt audit/guard, cleanup ownership и `PILOT_ONLY` exclusions; проверить traceability ко всем требованиям delta spec без implementation-derived expected values
- [x] 1.2 Поручить fresh independent planning/test-spec reviewer проверить public seam, fixtures, authorization contrast, pagination boundary, event/fallback precedence, clock normalization, read-only snapshots, failure classification и cleanup; verification: review artifact имеет `READY_FOR_OWNER_REVIEW`, reviewer не редактировал проверяемые artifacts
- [x] 1.3 Получить и append-only записать explicit owner approval exact reviewed spec hash до любого RED; verification: decision разрешает только Gate 2 и сохраняет target queue semantics в `NEEDS_GRILL`

## 2. Gate 2–3 — RED и independent test review

- [x] 2.1 Отдельному RED author добавить verifier contract/test только в non-hotspot `tests/Verification/` и test-support paths; verification: focused command падает по отсутствующему минимальному harness behavior с классификацией `RED_ASSERTION`, production files не изменены
- [x] 2.2 Сохранить reproducible RED evidence с exact command/output, hashes и DB/file/session before/after cleanup proof; verification: повтор из clean owned namespace демонстрирует тот же intended RED, а setup failure остаётся отдельным outcome
- [ ] 2.3 Поручить fresh independent test reviewer проверить approved spec, expected-value independence, real production HTTP composition, separate privileged fixture connection, four exact per-slot SELECT-only runtime principals, independent per-thread `performance_schema` DML/DDL-attempt audit, unambiguous concurrent A/B mapping, workers/deadlines, decoy preservation и отсутствие self-attestation; verification: fresh review artifact имеет `APPROVED` до GREEN

## 3. Gate 4 — minimal characterization GREEN

- [ ] 3.1 Реализовать минимальный test-only loopback harness с unique prefixes, fictional fixtures, separate privileged setup/observer connections, exact serial/concurrent-A/concurrent-B/sensitivity SELECT-only principals, request barriers/per-thread statement-history audit и bounded account/process/session/artifact cleanup; verification: каждый active slot независимо связан ровно с одним thread, оба concurrent threads audited до teardown, реальные GET/HEAD и любая runtime DML/DDL attempt наблюдаются без production/rapid-pilot или global MariaDB configuration изменений
- [ ] 3.2 Доказать literal working-only ordering/pagination, engineer event/fallback/absence, checklist activity, PTO marker, escaping/canonical href, authorization denials и malformed/out-of-range page outcomes; verification: normalized transcript совпадает с approved spec и явно маркирует hazards `PILOT_ONLY`
- [ ] 3.3 Доказать полную read-only границу для success/denial/repeat/two-worker reads и failure branches; verification: owned DB schema+rows, file tree, session namespace и ambient decoy до/после равны, runtime grants не допускают write, а independent per-thread audit содержит zero DML/DDL attempts
- [ ] 3.4 Зарегистрировать verifier ровно один раз в canonical characterization stage; verification: focused run twice и canonical characterization проходят без collision/leak и различают `SETUP_FAILURE`, assertion failure и regression

## 4. Regression, architecture и Gate 5

- [ ] 4.1 Выполнить focused verifier, relevant local-RBAC/inspection-planning/object-queue characterizations, lint, `git diff --check` и `make architecture-check`; verification: все owned checks GREEN, SQL/runtime-DDL/rapid-pilot mutation baselines и shared hotspots не изменены
- [ ] 4.2 Выполнить `make verify` и классифицировать каждый не-GREEN stage как owned regression, intended baseline или environment failure; verification: slice не объявляется integrated GREEN без фактического `VERIFY_OK` либо документированного заранее доказанного unrelated baseline
- [ ] 4.3 Поручить fresh independent code reviewer проверить approved spec/tests, harness isolation, process reaping, state fingerprints, canonical registration, production diff и verification evidence; verification: `reviews/code/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md` имеет `APPROVED`, reviewer не был test reviewer/implementer

## 5. Done и lifecycle

- [ ] 5.1 Обновить behavior inventory, backlog и operations status: queue oracle covered, все hazards остаются `PILOT_ONLY`, target visibility/activity/completion/pagination/read-model seam остаются `NEEDS_GRILL`; verification: ни один UNKNOWN/current defect не помечен accepted requirement
- [ ] 5.2 Выполнить strict OpenSpec validation и сверить Gates 1–5 с durable artifacts/hashes; verification: change отмечается Done только после approved code review, focused/canonical characterization GREEN, architecture GREEN и честной full-verify записи
- [ ] 5.3 После отдельной явной команды выполнить применимый OpenSpec sync/archive lifecycle; verification: main specs получают только characterization capability, без target queue semantics
