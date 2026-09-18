## 1. Contract and RED evidence

- [x] 1.1 Update `LOCAL-INTEGRATION-ENV-001`, OpenSpec artifacts and current delivery pointer; verify `openspec validate cross-platform-local-integration-staging --strict` passes
- [x] 1.2 Add focused tests for non-exact host modes, forbidden paths, atomic replay/cleanup, cross-UID container reading, both Make seams and downstream loaders; capture intended RED results
- [x] 1.3 Prepare and read the harness verification plan, resolve every mandatory obligation and obtain the planner-required independent Gate 3 review

## 2. Runtime implementation

- [x] 2.1 Relax only local integration exact-mode checks while retaining regular/non-symlink/accessibility/format and atomic publication; pass focused staging tests
- [x] 2.2 Implement private container delivery/cleanup and connect it to both Make-targets; verify UID 10001 reads a host-UID-mismatched `0600` snapshot and importer failure remains nonzero
- [x] 2.3 Update legacy/workforce PHP loader checks without changing unrelated production/session policies; pass focused loader tests

## 3. Integrated acceptance and delivery

- [x] 3.1 Run isolated MariaDB plus local Bitrix endpoint acceptance through both public Make-targets, confirming updated values and no production connections
- [x] 3.2 Run selected bounded checks and secret-leak assertions; record unavailable WSL/Docker Desktop coverage honestly
- [ ] 3.3 Obtain independent final review, commit exact candidate, push one branch, open one PR with `Refs #185`, and complete the selected exact-source CI without merge/deploy

## 4. PR #190 interruption correction

- [x] 4.1 Add RED coverage for TERM/INT/QUIT forwarding, real-container stop, child reaping, nonzero interruption and tmpfs-only one-shot config; obtain independent delta Gate 3 review
- [x] 4.2 Implement signal supervision and one-shot tmpfs wiring without changing production PHP-FPM stop policy; keep success, exit 23, replay and Make E2E GREEN
- [ ] 4.3 Obtain independent delta Gate 5 review, push corrected head and complete required CI for the new exact source
