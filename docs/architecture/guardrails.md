# Architecture guardrails

`tools/architecture/check` is the canonical, deterministic architecture check.
It is a current-state ratchet: existing debt is recorded in
`tools/architecture/baseline.json`; removal is always allowed, addition is not.
The baseline is not an allow-list and must only be regenerated after an explicit
architecture review. CI and local verification must run the checker without
`--write-baseline`.

## Enforced policy

1. **DDL ownership.** Production `CREATE`, `ALTER`, `DROP`, and `TRUNCATE` are
   owned only by `app/InstallationProcess/*SchemaMigration.php`. Existing
   runtime DDL remains baseline debt and new runtime schema-on-demand DDL fails.
2. **SQL ownership.** New business persistence SQL is confined to MariaDB
   adapters, schema migrations, and the named persistence/import adapters in
   `app/InstallationProcess`; HTTP may use named MariaDB read adapters. Existing
   SQL in HTTP and rapid-pilot is debt, not precedent.
3. **Dependency direction.** `app/InstallationProcess` is the application
   module. It must not acquire dependencies on PilotHttp, rapid-pilot, or direct
   construction of concrete MariaDB adapters. Existing composition debt is
   ratcheted.
4. **Hotspot ratchet.** Every production source file at or above 150 lines is
   baselined with its exact line ceiling. It may shrink, but may not grow; a new
   150-line hotspot also fails. Moving a behavior behind a seam may require more
   changed files and is preferred to adding it to a hotspot.
5. **Public seam ownership.** Public application methods with state-changing
   command verbs are the detectable capability seams. Current seams are
   registered in the baseline; a new one requires architecture review and a
   baseline update proving one owning application module.
6. **Rapid-pilot boundary.** Rapid-pilot is a UX reference, behavioral oracle,
   and temporary adapter. New mutation SQL or DDL there fails. Presentation,
   observability, characterization, critical fixes, and wiring to application
   seams remain permitted when they do not introduce these ownership violations.

## Usage and interpretation

```sh
tools/architecture/check
tools/architecture/check --json
```

Exit `0` means the architecture did not regress. Exit `1` identifies a policy
regression. Exit `2` means setup/baseline failure. Findings use stable hashes of
normalized source lines, so unrelated line movement does not invalidate the
baseline. Test/verifier/profile and demo files are excluded; migration tooling
under `rapid-pilot/legacy-migration` is excluded because it is not runtime
product behavior.

When a deliberate new public seam or exceptional hotspot growth is approved,
record the reason in an ADR, then run `tools/architecture/check --write-baseline`
in the reviewed change. Never rebaseline merely to make a failure green.
