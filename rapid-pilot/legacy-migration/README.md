# Legacy history snapshot importer

This first migration slice copies one completed legacy installation object and its dated checklist/installer evidence into immutable, source-labelled local pilot tables. It does not reconstruct or confirm history. The legacy connection executes three fixed, parameterised `SELECT` statements only. No credentials are stored in the repository.

Dry-run (opens no target connection and performs zero writes):

```bash
FMONITOR_SOURCE_USER='<read-only user>' FMONITOR_SOURCE_PASSWORD='<secret>' \
php rapid-pilot/import-legacy-history.php --object-id=123 --cutoff='2026-08-30 23:59:59'
```

Add `--apply` plus `FMONITOR_PILOT_ACTIVE_MANIFEST`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, and `FMONITOR_DB_NAME` to write only tables under the manifest's local `processPrefix`. Repeating an identical import returns the same hash and `created: false`. Reconciliation counts and quarantine diagnostics are printed as JSON. Treat any quarantine row as unresolved source evidence, not a domain fact.

Verify with `php rapid-pilot/verify-legacy-history-import.php`. To reverse this isolated local schema, drop only `<processPrefix>fm2_history_import_quarantine` and then `<processPrefix>fm2_history_source_snapshots` in the disposable pilot database; never run that operation against legacy/production.
