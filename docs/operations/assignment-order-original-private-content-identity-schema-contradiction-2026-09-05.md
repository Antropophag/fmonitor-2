# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private content identity schema contradiction

Date: `2026-09-05`

Discovered during Gate 4 implementation of tasks 5.1/5.2.

Status: **BLOCKED — requires Gate 1 contract resolution**.

The approved v52 contract requires content-addressed reuse: two revisions with
the same PDF bytes both expose the exact immutable identity
`content-sha256-<pdfSha256>`. Canonical identical/different race oracles
therefore require revision 1 and revision 2 (and later revision 3) to contain
the same `privateContentIdentity`.

The already Gate-5-approved version-1 schema simultaneously declares
`fm2_assignment_order_original_revisions.private_content_identity` as a
single-column `UNIQUE` key. MariaDB consequently rejects the first same-PDF
correction before request/event/audit commit. Focused worker transport reaches
this exact failure at the approved `COMMIT_UNKNOWN_FOUND` correction case and
returns `FAILED/PERSISTENCE_FAILURE` instead of the required accepted revision
2.

These requirements cannot both hold. Appending a revision suffix in the DB and
normalizing it away in evidence would violate the exact stored content identity,
repository reference lookup and maintenance exclusion contract. Dropping or
weakening the unique key would alter the approved migration/schema contract.

Owner/Gate 1 must select an additive schema relationship that permits many
revisions to reference one immutable content identity (or explicitly change
the content identity/evidence contract). Existing production facts are absent,
but the migration contract and its approved setup tests still require formal
amendment and fresh gates rather than an implementation workaround.

```text
app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php:88
UNIQUE private_content_identity

specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:288
identical digest reuses the same verified content identity/lease

specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1180
private_content_identity varchar(160) UNIQUE

Focused observed result:
expected correction revision-0002 ACCEPTED
actual FAILED/PERSISTENCE_FAILURE after MariaDB unique-key rejection

Post-run t_aoou_% schemas: 0
Post-run t_aoou_% connections: 0
```
