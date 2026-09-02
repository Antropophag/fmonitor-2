## 1. Gate 1 executable specification

- [x] 1.1 Create `specs/CHARACTERIZE-INSPECTION-ATTRIBUTION-CORRECTION-001.md` with literal real-HTTP/session/CSRF fixtures, original-completion baseline, exact accepted correction/raw/projection facts, crew drift, replay/revision/concurrency contrasts, rejection boundaries, isolated legacy backfill, closed ownership and stable transcript; verify every expected value is independent from the future verifier and the pilot command is explicitly distinguished from `completion_retracted`.
- [ ] 1.2 Obtain and durably record owner approval for characterization-only scope; verify target attribution/retraction semantics, authorization, reason/reference, payload conflict and concurrency remain excluded before RED starts.

## 2. Gates 2–3 minimal reviewed RED

- [ ] 2.1 Assign a fresh RED author to add the smallest test requiring one accepted production-composed GET/session/CSRF plus correction POST, independent original-row immutability and correction snapshot evidence; verify `tools/verification/run.sh red <test>` fails for the missing oracle rather than setup.
- [ ] 2.2 Assign a different fresh independent test reviewer, record review under `reviews/tests/`, resolve findings without implementation-derived expected values, and verify explicit `APPROVED` before GREEN.
- [ ] 2.3 Implement only the reviewed accepted exchange in a private fixture/verifier; verify the unchanged minimal test turns GREEN twice with distinct tokens and production behavior/schema remain untouched.

## 3. Reviewed expansion and Gate 4 GREEN

- [ ] 3.1 Extend the test under a new intended RED to exact/changed replay, lower-stale/ahead, crew drift, one winner-neutral two-server race, isolated rejection matrix, legacy backfill and deterministic cleanup; verify response-only fakes, mutable originals, single-connection concurrency and GET/POST mutation confusion all fail.
- [ ] 3.2 Assign another fresh independent test reviewer for expanded RED, record a versioned review, resolve every finding, and verify explicit `APPROVED` before expanded implementation.
- [ ] 3.3 Implement only the reviewed expansion without changing `ChecklistSync`, HTTP routing, application modules or production schema; verify unchanged expanded test turns GREEN twice with distinct clean tokens.
- [ ] 3.4 Register the verifier once in canonical characterization stage; verify normalized transcript, bounded process/SQL/artifact cleanup, decoy preservation and setup/RED/regression classification.
- [ ] 3.5 Run focused characterization, relevant item-completion/current-crew/template/offline regressions, lint and `make architecture-check`; verify production DDL/SQL/rapid-pilot debt and hotspot baselines do not grow.

## 4. Gate 5 and Done

- [ ] 4.1 Reconcile the unapproved target attribution backlog/spec so it cannot treat `item_installers_changed` as approved `completion_retracted`; verify target owner decisions remain explicit NEEDS_GRILL before any target RED.
- [ ] 4.2 Assign a different fresh independent code reviewer that authored neither implementation increment and record review under `reviews/code/`; verify explicit `APPROVED` covers real seam sensitivity, expectation independence, immutable original evidence, concurrency/process safety, cleanup and non-promotion of pilot defects.
- [ ] 4.3 Run canonical characterization and `make verify`, classify every failure, and mark Done only when reviewed GREEN evidence is durable and target work cites the oracle solely as explicit behavioral contrast.
