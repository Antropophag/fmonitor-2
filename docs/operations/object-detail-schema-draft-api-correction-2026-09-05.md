# Object-detail schema draft v0.2 — API and runner correction

Date: 2026-09-05. Author: `/root`.
Inspected base: `670e19f6d86fe0172d71eaa36736ec6d857b936e`.

The first schema draft incorrectly referred to ledger publication. The actual
`CanonicalMigrationApplication` computes results from its contiguous registry
and does not persist a migration ledger. This correction removes that implied
new persistence requirement. No historical evidence was rewritten.

The draft now defines public migration/compatibility signatures, sorted result
lists, empty-prefix acceptance, metadata normalization, existing validated
collation lookup, and CLI result/exit mapping. Version 12 is a planning candidate
following inspected registry 1–11, not a reserved or registered version.

Remaining explicit Gate 1 work: independently review API/metadata policy,
define deterministic interruption and concurrent-run verification construction,
reconcile scheduling artifacts and obtain required approval. No runner lock is
assumed. Importer DML characterization remains separately gated.

Exact candidate SHA-256:
`64742a24f6c71c845e52bc0ffb582b2ea6dd800376287cdc05bcd357e87f57d3`
for `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md`.

`git diff --check`: exit 0. No production, tests, migrations or grants changed.
This remains DRAFT and does not authorize RED or implementation.
