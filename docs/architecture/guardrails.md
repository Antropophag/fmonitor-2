# Architecture guardrails

`tools/architecture/check` is the canonical, deterministic architecture check.
Meaningful architecture rules are a current-state ratchet: existing debt is recorded in
`tools/architecture/baseline.json`; removal is always allowed, addition is not.
The baseline is not an allow-list and must only be regenerated after an explicit
architecture review. CI and local verification must run the checker without
baseline-update options.

## Enforced policy

1. **DDL ownership.** Production `CREATE`, `ALTER`, `DROP`, and `TRUNCATE` are
   owned only by `app/InstallationProcess/*SchemaMigration.php`. Existing
   runtime DDL remains baseline debt and new runtime schema-on-demand DDL fails.
2. **SQL ownership.** New business persistence SQL, including the Otiz module, is confined to MariaDB
   adapters, schema migrations, and the named persistence/import adapters in
   `app/InstallationProcess`; HTTP may use named MariaDB read adapters. Existing
   SQL in HTTP and rapid-pilot is debt, not precedent.
3. **Dependency direction.** `app/InstallationProcess` is the application
   module. It must not acquire dependencies on PilotHttp, rapid-pilot, or direct
   construction of concrete MariaDB adapters. Existing composition debt is
   ratcheted.
4. **File-size advisory.** Every production source file at or above 150 physical
   lines is tracked as review metadata. A new hotspot or growth above its recorded
   size produces a non-blocking advisory, including after a move/rename or a
   threshold crossing caused only by comments or blank lines. Review should assess
   cohesion and responsibilities; decomposition is not required merely to meet a
   line ceiling.
5. **Public seam ownership.** Public application methods with state-changing
   command verbs are the detectable capability seams. Current seams are
   registered in the baseline; a new one requires architecture review and a
   baseline update proving one owning application module.
6. **Rapid-pilot boundary.** Rapid-pilot is a UX reference, behavioral oracle,
   and temporary adapter. New mutation SQL or DDL there fails. Presentation,
   observability, characterization, critical fixes, and wiring to application
   seams remain permitted when they do not introduce these ownership violations.

## Local gate

`make architecture-check` first runs the existing HTTP global-call qualification
contract, then the structural checker below. This prevents a green local structural
check from hiding violations in a new `app/PilotHttp` adapter. The token-based HTTP
oracle remains defined once in its existing test; no second implementation is added.

## Usage and interpretation

```sh
tools/architecture/check
tools/architecture/check --json
tools/architecture/check --write-size-baseline
```

Exit `0` means the architecture did not regress. Exit `1` identifies a policy
regression. Exit `2` means setup/baseline failure. Findings use stable hashes of
normalized source lines, so unrelated line movement does not invalidate the
baseline. Test/verifier/profile and demo files are excluded; migration tooling under `rapid-pilot/legacy-migration` remains excluded from
the general legacy SQL ratchet. The three decision-ledger/projection adapters
called by OTIZ are explicitly scanned for runtime DDL despite that directory
exclusion (ADR0002); this named inventory is not a general PHP call-graph proof.

Human output shows file-size advisories for both passing and failing checks. JSON
consumers receive separate `errors` and `advisories` arrays; only `errors` control
`ok` and the process exit status. To refresh size metadata, run
`tools/architecture/check --write-size-baseline`; it preserves every non-size
baseline section even if the current scan contains an unrelated violation. The
legacy `--write-baseline` spelling remains a deprecated size-only alias and no
longer rewrites meaningful exceptions.

Deliberate new meaningful exceptions, including a public seam, require an ADR and
an explicit reviewed edit to the applicable baseline section. Never rebaseline
merely to make a failure green. SQL, DDL/runtime migration, dependency direction,
public-seam, session/workforce ownership, and rapid-pilot boundary violations
remain blocking independently of file size.
