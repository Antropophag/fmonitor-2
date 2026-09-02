# Installation completion characterization wiring correction

Date: 2026-09-02  
Status: **GREEN / awaiting independent review**

Fresh `make verify` reached the canonical test environment and applied migration
versions `1..10`, but `rapid-pilot/verify-completion-flow.php` stopped before
assertions because the completed v10 migration refactor now delegates its exact
schema comparison to `MariaDbInstallationCompletionSchemaFingerprint`, while
the standalone verifier required the definition and migration classes but not
that derived fingerprint class.

The bounded correction adds only the missing `require_once` to the standalone
characterization bootstrap. Production code, schema, behavior, fixtures and
assertions are unchanged.

Before:

```text
PHP Fatal error: Class "FMonitor2\\InstallationProcess\\MariaDbInstallationCompletionSchemaFingerprint" not found
```

After:

```text
make characterization-test
...
PASS rapid completion flow 85% -> PTO -> declaration -> 100%
...
exit 0
```

All characterization verifiers passed, including inspection schedule/photo,
completion, deployment, queue, OTIZ canonical compatibility, premium and visual
contracts. This evidence does not self-approve the harness change; a separately
tasked reviewer must confirm it is only wiring for the already approved v10
schema design.
