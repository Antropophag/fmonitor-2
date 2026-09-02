## 1. Gate 1 executable specification

- [x] 1.1 Create `specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md` from repository evidence with literal worked fixtures, operator CLI seam, covered serial outcomes, rejection categories, isolation contract and explicit PILOT_ONLY exclusions; verify every acceptance statement is observable without implementation-derived expected values.
- [ ] 1.2 Obtain and durably record owner approval of the executable specification before writing RED; verify GRILL-004 and all UNKNOWN transition/concurrency/product semantics remain outside the approved scope.

## 2. Gates 2–3 reviewed RED

- [ ] 2.1 Add the smallest RED verification test that requires a real CLI execution and independently checked target facts, and capture `tools/verification/run.sh red <test-file>` failing for the missing executable oracle rather than setup failure.
- [ ] 2.2 Extend RED coverage to the approved clean detail/quarantine, serial repeat, atomic detail conflict, incomplete metadata, unknown dictionary, deterministic rerun and bounded-cleanup scenarios; verify current static token scanning cannot satisfy the tests.
- [ ] 2.3 Assign a fresh independent test reviewer, record findings and RED evidence under `reviews/tests/`, resolve every finding without consulting planned implementation, and verify an explicit `APPROVED` Gate 3 verdict before GREEN work.

## 3. Gate 4 minimal GREEN

- [ ] 3.1 Implement the isolated private source/target fixture harness and real child-process CLI verifier without changing importer or consumer production behavior; verify the reviewed focused test turns GREEN.
- [ ] 3.2 Register the verifier once in the canonical characterization stage and run it twice from clean state; verify normalized results are deterministic, setup and regression failures remain distinct, owned artifacts are removed and ambient decoys are preserved.
- [ ] 3.3 Run focused characterization, architecture check, lint and relevant regression suites; verify architecture debt counts do not grow and no new regression is introduced.

## 4. Gate 5 and Done

- [ ] 4.1 Assign a different fresh independent code reviewer and record review under `reviews/code/`; verify explicit `APPROVED` covers the real seam, assertion independence, cleanup safety, secret/privacy boundary and PILOT_ONLY exclusions.
- [ ] 4.2 Run canonical characterization plus `make verify`, classify every failure, and mark the slice Done only when reviewed GREEN evidence is durable and `canonicalize-object-detail-snapshot-schema` can cite this oracle without treating excluded behavior as accepted semantics.
