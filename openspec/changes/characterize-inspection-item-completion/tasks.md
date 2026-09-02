## 1. Gate 1 executable specification

- [x] 1.1 Create `specs/CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001.md` with a real HTTP/session/CSRF seam, literal operation/crew/template examples, exact raw facts and transcript, explicit PILOT_ONLY replay/stale/concurrency/backfill/GET-revision-initialization classification, isolation contract and four exact-mutation-boundary rejections; verify every expected value is independent from the future verifier.
- [ ] 1.2 Obtain and durably record owner approval for this characterization-only scope; verify GRILL-003 target authorization choice and all target behavior changes remain excluded before RED starts.

## 2. Gates 2–3 reviewed RED

- [ ] 2.1 Assign a fresh RED author to add the smallest test requiring one real accepted HTTP/session/CSRF exchange plus independent raw-row evidence; verify `tools/verification/run.sh red <test>` fails for the missing oracle rather than environment setup.
- [ ] 2.2 Assign a different fresh independent test reviewer for that minimal RED, record review under `reviews/tests/`, resolve findings without implementation-derived expectations, and verify explicit `APPROVED` before implementing the accepted-exchange GREEN.
- [ ] 2.3 Implement only the reviewed accepted HTTP exchange in the private fixture/verifier and verify the unchanged minimal test turns GREEN twice with distinct tokens.

## 3. Reviewed expansion and Gate 4 GREEN

- [ ] 3.1 After the minimal exchange is GREEN, extend the test under a new intended RED to exact/changed replay, lower-stale/ahead behavior, one winner-neutral two-server race, crew-history projection, legacy backfill and four isolated exact-mutation-boundary rejections; verify echo-only output, single-connection pseudo-concurrency, wrong revision initialization and cleanup leaks all fail.
- [ ] 3.2 Assign another fresh independent test reviewer for the expanded RED, record a versioned review, resolve every finding, and verify explicit `APPROVED` before implementing any expanded behavior.
- [ ] 3.3 Implement only the reviewed expansion without changing `ChecklistSync`, HTTP, application modules or production schema; verify the unchanged expanded test turns GREEN twice with distinct tokens.
- [ ] 3.4 Register the verifier once in the canonical characterization stage; verify deterministic normalized transcript, bounded process/SQL cleanup, decoy preservation and distinct setup/regression classification.
- [ ] 3.5 Run focused characterization, relevant existing checklist/template/crew/photo regressions, lint and `make architecture-check`; verify production DDL/SQL/rapid-pilot debt and hotspot baselines do not grow.

## 4. Gate 5 and Done

- [ ] 4.1 Update the unapproved `migrate-inspection-item-completion` planning artifacts so read-only projection/no manufactured attribution is a normative target requirement, validate both changes strictly, and verify this planning edit precedes target Gate 1 approval/RED.
- [ ] 4.2 Assign a different fresh independent code reviewer that authored neither implementation increment and record review under `reviews/code/`; verify explicit `APPROVED` covers real-seam sensitivity, expectation independence, concurrency/process safety, cleanup bounds and non-promotion of pilot defects.
- [ ] 4.3 Run canonical characterization and `make verify`, classify every failure, and mark Done only when reviewed GREEN evidence is durable and `migrate-inspection-item-completion` can cite the oracle while retaining target payload-conflict, strict revision, one-winner concurrency, authorization and read-only projection requirements.
