# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.3 — independent Gate 1 rereview

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed commit: `a79bd2a6ca4acac2fc785af29b78525d111f0742`.

Verdict: **APPROVED**.

Reviewer authored neither the specification, parent nor OpenSpec artifacts. The
v0.1 and v0.2 reviews remain immutable. This review covers the cumulative
data-integrity package at v0.3 and does not approve separate lifecycle, parser,
maintenance, selection or combined-command scopes.

## Exact reviewed hashes

```text
0ad31853e7a348205660110482be776eec29b61e7fc8149d6d3dbe8544858773  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
153f56abfd1239c9adbd3b1b2e44da3522c4b2d4a141f80cdff7093b158ec74c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d28cba82a6f7220fe19b623741133a479778570560b64876e02618d70e8627b9  docs/operations/original-data-integrity-gate1-review-v02-2026-09-06.md
a4a0c7e0e5fb71bd1a7c9eb03e6ffedf6eeaa00cca7937d9249b7522f40ed8eb  openspec/changes/replace-pilot-registration-with-original-upload/design.md
73a32ef9ad9d7bd50809722428e06708970358c69a400014b53122d2af3b33a4  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
162f998925cf0787014eff8e9cfc7526829e0aa60d4aa8379007fb5708fd5e67  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c1a03ff14ca134d39a256c0d9fa62699505632b72ad4097f9f89191362444bb6  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## v0.2 finding disposition

### Authoritative composition conflict ambiguity — RESOLVED

Both initial and correction commits lock and rederive the authoritative legacy
order composition inside their owned write transaction. Changed composition,
missing order/case, composition no longer valid for acceptance, malformed source
or query/protocol failure now returns confirmed `ROLLED_BACK`. Through the
application this is retryable `FAILED/PERSISTENCE_FAILURE`; no domain conflict is
invented and no root/current pointer or immutable fact is mutated.

Generic accepted-commit `CONFLICT` is reserved for actual root/current/uniqueness
winner states that the approved fingerprint and complete-lineage rereads can
resolve. Incoming self-consistent composition identity/hash remains insufficient
without the locked authoritative source match.

For INITIAL conflict, validated fingerprint FOUND wins as replay. Fingerprint
miss then uses the explicit case/order lineage query. Only complete valid FOUND
lineage for that exact case/order selects
`CONFLICT/INITIAL_ALREADY_EXISTS`. Lineage NOT_FOUND, UNAVAILABLE, incomplete or
malformed maps to persistence failure. This prevents the false-existing-root
outcome identified in v0.2.

For CORRECTION conflict, existing validated fingerprint and current-lineage
stale/target/no-change precedence remains. Same current document date and PDF
hash is a valid repository conflict state that the lineage reread can prove and
maps to exact nonretryable `REJECTED/NO_CHANGES`. It is not confused with
authoritative composition drift.

The required Gate 2 matrix is constructible: initial and correction source
missing/invalid/changed cases assert confirmed rollback and zero facts; actual
uniqueness/root-current controls exercise the reserved conflict path; initial
lineage found/miss/unavailable/malformed cases prove exact selection; correction
NO_CHANGES remains a nearby positive conflict-resolution control.

## Preserved v0.2 conclusions

### Stored terminal integrity — PASS

Stored `INVALID_COMMAND` and AttemptCommit with that reason remain invalid
backing. Invalid current input stays an application-only result with zero
persistence.

Authorization-denied backing requires its original matching audit but imposes no
upper cardinality and neither creates nor limits additional denial attempts. No
deferred owner policy is selected.

### Closed results and complete lineage — PASS

Lookup status/payload combinations, single getter snapshots, stored
status/reason/retry/evidence grammar and request/fingerprint identity rules remain
closed. Getter Throwable and contradictions are unavailable rather than absence.

FOUND lineage still requires the optional complete extension with exact
case/order ownership, contiguous ordered membership, current-last revision,
current evidence and composition binding. Negative base-only lookup compatibility
is preserved. Historical accepted requests remain tied to their accepted
revision rather than the root's later current pointer.

### Composition and MariaDB consistent reads — PASS

Application validates exact case/order echo, identity/version grammar, positive
strictly increasing installer list and recomputed canonical hash. Protocol or
ownership failure maps to persistence failure; genuine absence maps
ORDER_NOT_FOUND; found-invalid business composition maps INVALID_COMPOSITION.

MariaDB order/member reads use one owned REPEATABLE READ read-only snapshot,
lossless SQL numeric/calendar/action validation and release-before-return. The
two-connection observer race, malformed backing and no-repair requirements remain
fully observable.

### Commit DTO validation and native outcomes — PASS

AcceptedCommit and AttemptCommit scalar/mode/status/relationship validation
precedes transaction, SQL, escaping and observer calls. Active borrowed
transactions are not committed or rolled back. Real MariaDB correction locking,
CAS, append-only writes, native false, confirmed rollback and unknown
acknowledgement remain distinct.

Pure lifecycle ports may keep approved synthetic opaque content identity; only
the real MariaDB persistence boundary requires canonical digest binding.

### Fresh recovery and construction compatibility — PASS

Fresh open and close values remain closed, one-shot, non-cloneable and
non-serializable. Dependencies adds one nullable non-promoted trailing argument,
initializes its readonly public provider once, and installs an explicit
unavailable factory when absent.

Two-argument production construction remains explicitly degraded;
`createRecoveryReady` requires a provider. Launch readiness must reject degraded
wiring and probe the actual target. The provider opens one new configured
utf8mb4 connection and never reuses or extracts credentials from the borrowed
writer.

Unknown accepted or authorized-attempt outcomes perform at most one fresh
validated read and one close, with no second commit/allocation/clock. Close
failure preserves a validated read result and produces only its fixed
best-effort diagnostic.

### Clock, worker ordering and observer ownership — PASS

The application invocation clock and storage clock are separate owned adapters.
Authorized early terminal outcomes lazily acquire one canonical application
instant; exact epoch is valid data. Attempt conflict/unknown/unconfirmed Throwable
uses fresh recovery. Denial behavior remains deferred.

Worker composition acquires the approved safe-log owner before password content,
provider or DB access. Invalid safe-log configuration therefore admits exact zero
secret/provider/DB proof without a new selector or native mechanism.

Exact production class names and optional trailing persistence observers remain
pinned. Public observer phases cover snapshot races, pre-SQL checks, native
commit/rollback acknowledgement and fresh close without private interception,
sleeps or payload disclosure.

## OpenSpec and scope coherence

Parent v68 and the OpenSpec proposal, design and delta describe the same v0.3
resolution: authoritative composition changes are rollback/persistence states,
generic conflict is limited to resolvable root/current/uniqueness states, initial
miss cannot fabricate an existing original, and correction NO_CHANGES remains
exact. Tasks remain unchanged because the cumulative Gate 1/RED/Gate 3/GREEN/Gate
5 sequence was already open.

No new product workflow, capability, denial-cardinality policy, maintenance
behavior, schema version, HTTP route, renderer, selection writer or launch claim
is introduced.

## Gate disposition

No blocking behavioral, port, constructor, persistence, recovery, test
constructibility or deferred-policy finding remains.

`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001` v0.3 is **APPROVED** at exact
SHA256 `0ad31853e7a348205660110482be776eec29b61e7fc8149d6d3dbe8544858773`
to proceed to the cumulative public/real-adapter RED described in section 13.
That RED must then receive independent Gate 3 before minimal GREEN. This verdict
does not approve future tests, implementation, combined command review or launch
readiness by implication.
