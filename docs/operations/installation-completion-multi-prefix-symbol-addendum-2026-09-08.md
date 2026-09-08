# Installation completion multi-prefix symbol addendum

Migration v10 remains the owner of the completion facts and append-only correction
tables; migration v17 remains its additive nullable `details` successor. No new
schema version or data rewrite is introduced by this correction.

MariaDB foreign-key symbols are database-wide. For tables newly created with a
non-empty valid process prefix, v10 names each completion foreign key as the exact
table prefix followed by its historical symbol:

- `<prefix>fk_completion_correction_root`
- `<prefix>fk_completion_correction_previous`

The empty-prefix schema keeps the historical names exactly. Existing prefixed
schemas that already contain the historical unscoped names remain compatible and
must not be renamed or rewritten. V10 and v17 readiness therefore accept exactly
either the complete historical-symbol manifest or the complete scoped-symbol
manifest. Columns, indexes, targets, column order, actions, checks, collation,
engine, and every other fingerprint field remain exact; mixed, missing, renamed,
or additional foreign-key forms remain conflicts.

The existing 25-byte ASCII prefix bound makes the longest new symbol 58 bytes,
within MariaDB's 64-byte identifier limit.
