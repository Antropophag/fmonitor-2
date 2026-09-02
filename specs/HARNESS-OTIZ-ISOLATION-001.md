# HARNESS-OTIZ-ISOLATION-001 v0.1

Status: approved by the TEST-USER-READY reproducible-characterization mission. This is a harness contract, not approval of OTIZ product or financial semantics.

## Public seam

`php rapid-pilot/verify-otiz-workflow.php` against the configured disposable verification database.

## Contract

1. The verifier creates every prefixed identity, native operational-input, and OTIZ table and row required by its existing assertions. It reads no ambient demo/legacy rows.
2. The first calculation has at least one deterministic open native-evidence blocker and cannot be accepted. The verifier then supplies the missing native evidence explicitly; a later calculation is blocker-free and exercises the existing characterization path.
3. Fixture inputs are literal, versioned verifier evidence. Expected business amounts remain sourced from the already characterized calculation contract; the fixture must not derive expected values from OTIZ implementation output.
4. The verifier can run twice against the same clean database and produces the same assertion transcript apart from nondeterministic private table prefixes.
5. Success and failure both remove every table owned by the private prefix. Pre-existing tables and rows outside that prefix are unchanged.
6. Missing database connectivity is a setup failure. A behavior assertion remains a RED/regression failure; the verifier must not silently skip assertions.
7. The slice does not change `RapidPilotOtiz`, `NativeOperationalPremiumInputs`, premium formulae, acceptance/payment meaning, authorization policy, or release scope.

Each successful transcript must contain these stable harness milestones in order; their presence characterizes execution depth and does not promote the underlying financial behavior to an accepted product requirement:

1. `authorization rejects a user outside OTIZ`;
2. `calculation creates a deterministic draft`;
3. `open blockers prevent acceptance`;
4. `blocker-free draft can be accepted`;
5. `snapshot persists shared premium calculation version`;
6. `accepted snapshot is immutable on repeated acceptance`;
7. `reversal is append-only and idempotent`;
8. `accepted export is a structurally valid XLSX ZIP package`.

## Acceptance scenarios

### Clean isolated run

- **Given** an empty disposable database and configured connection variables
- **When** the verifier runs
- **Then** every existing assertion passes using only its private fixture family
- **And** no private fixture table remains

### Ambient-data independence

- **Given** unrelated decoy tables and rows in the same database
- **When** the verifier runs twice
- **Then** both runs pass with identical assertion transcripts
- **And** the decoy state is byte-for-byte unchanged

### Blocked then ready evidence

- **Given** a native operational case whose required evidence is initially incomplete
- **When** the first draft is calculated and acceptance attempted
- **Then** it remains draft with an open blocker
- **When** the verifier adds the explicit missing evidence and calculates the later draft
- **Then** the later draft follows the existing blocker-free characterization path
