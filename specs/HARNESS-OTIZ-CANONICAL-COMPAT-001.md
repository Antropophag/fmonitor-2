# HARNESS-OTIZ-CANONICAL-COMPAT-001 v0.1

Status: approved by the TEST-USER-READY reproducible-characterization and canonical-migration missions. This harness contract changes no OTIZ or financial product semantics.

## Actor and intent

A developer, CI runner, or autonomous agent runs characterization after canonical migrations and needs the OTIZ oracle to remain isolated without assuming that canonical production tables are absent.

## Public seam

`php tests/Verification/harness_otiz_isolation_001_test.php` against the configured disposable database after `make migrate` has succeeded.

## Preconditions

- The configured disposable database contains the canonical schema produced by the production migration runner.
- No private `otiz_verify_*` fixture tables remain from an earlier run.
- The existing `HARNESS-OTIZ-ISOLATION-001` transcript and product-behavior assertions remain normative.

## Contract

1. Pre-existing canonical tables SHALL be treated as ambient state, not as a setup failure.
2. The harness SHALL place deterministic decoy facts in compatible canonical tables where needed to prove that the prefixed OTIZ verifier does not read or mutate ambient operational state.
3. Tables absent from the current canonical version MAY be created as owned ambient fixtures, but the harness SHALL distinguish them from pre-existing canonical tables.
4. Across two verifier runs, every pre-existing canonical table definition and pre-existing row SHALL remain byte-for-byte unchanged after harness-owned decoy cleanup.
5. Harness-created ambient tables and rows SHALL be removed in dependency-safe order on success and failure; pre-existing canonical tables SHALL never be dropped.
6. Private `otiz_verify_*` tables SHALL still be absent after each run, and the stable OTIZ assertion transcript SHALL remain identical across both runs.
7. A missing or incompatible canonical prerequisite SHALL be a `SETUP_FAILURE`; an OTIZ assertion mismatch remains a regression failure.

## Acceptance scenarios

### Canonical schema coexists with isolated OTIZ characterization

- **GIVEN** canonical migrations have created the process table family
- **AND** the harness records canonical definitions and rows before adding its decoys
- **WHEN** the OTIZ isolation seam runs twice
- **THEN** both verifier runs pass with identical milestone transcripts
- **AND** private tables do not leak
- **AND** the canonical definitions and original rows are unchanged after harness cleanup

### Failure cleanup preserves canonical ownership

- **GIVEN** a verifier assertion or subprocess fails after fixtures are installed
- **WHEN** harness cleanup executes
- **THEN** only harness-owned rows/tables and private-prefix tables are removed
- **AND** canonical tables are not dropped or recreated

## Done definition

A reviewed RED reproduces the post-migration failure through the public harness seam; the minimal harness change distinguishes owned from canonical state; focused isolation, characterization, canonical migration, architecture and diff checks pass; fresh independent code review is approved.
