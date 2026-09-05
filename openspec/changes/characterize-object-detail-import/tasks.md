## 1. Gate 1 executable specification

- [x] 1.1 Create `specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md` from repository evidence with literal worked fixtures, operator CLI seam, covered serial outcomes, rejection categories, isolation contract and explicit PILOT_ONLY exclusions; verify every acceptance statement is observable without implementation-derived expected values.
- [ ] 1.2 Получить fresh technical review и durably record exact owner approval v0.2 serial regression oracle до RED; table-transfer approval уже получено. Проверить private disposable server, canonical v12 setup, exact DDL-denied/schema-precondition/dry-run contract и UNKNOWN exclusions.

## 2. Gates 2–3 reviewed RED

- [ ] 2.1 Add the smallest RED verification test that requires a real CLI execution and independently checked target facts, and capture `tools/verification/run.sh red <test-file>` failing for the missing executable oracle rather than setup failure.
- [ ] 2.2 Extend RED coverage to clean detail/quarantine, serial repeat, atomic detail conflict, incomplete metadata, unknown dictionary, deterministic rerun and cleanup; добавить qualifying real-importer no-DDL/pre-source RED под least-privilege principal и оба режима. Missing-verifier RED не заменяет production no-DDL RED; shared evidence закрывает schema task 2.2 только после review.
- [ ] 2.3 Assign a fresh independent test reviewer, record findings and RED evidence under `reviews/tests/`, resolve every finding without consulting planned implementation, and verify an explicit `APPROVED` Gate 3 verdict before GREEN work.

## 3. Gate 4 minimal GREEN

- [ ] 3.1 Implement private disposable server harness и real CLI verifier без redesign serial DML/consumers; получить GREEN после отдельно gated importer no-DDL/precondition fix в schema task 3.2. Verifier-only GREEN не завершает shared axis.
- [ ] 3.2 Register the verifier once in the canonical characterization stage and run it twice from clean state; verify normalized results are deterministic, setup and regression failures remain distinct, owned artifacts are removed and ambient decoys are preserved.
- [ ] 3.3 Run focused characterization, architecture check, lint and relevant regression suites; verify architecture debt counts do not grow and no new regression is introduced.

## 4. Gate 5 and Done

- [ ] 4.1 Assign a different fresh independent code reviewer and record review under `reviews/code/`; verify explicit `APPROVED` covers the real seam, assertion independence, cleanup safety, secret/privacy boundary and PILOT_ONLY exclusions.
- [ ] 4.2 Run canonical characterization plus `make verify`, classify every failure, and mark the slice Done only when reviewed GREEN evidence is durable and `canonicalize-object-detail-snapshot-schema` can cite this oracle without treating excluded behavior as accepted semantics.
