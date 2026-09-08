# Inspection photo content index v19 — independent code review

Reviewer: `/root/photo_review`; implementation author: `/root/auth_review`.
Verdict: **APPROVED**. Reviewed worktree base: `8fe291bfec0fbb5ddda2445b34267570d0b5ba8b`.

## Standards

No finding. The change keeps canonical DDL in the installation migration module and
runtime commands DML-only. The migration validates the exact complete v8 predecessor
before one atomic index replacement, recognizes only the exact final family, validates
prefixes, and fails closed for malformed/intermediate shapes. The catalogue preserves
literal v8 as the historical oracle while allowing a final-v19 repeat to traverse v8
as a successor no-op. No architecture baseline change or new application seam appears.

## Spec

No finding. The only schema change is UNIQUE→non-unique for ordered
`(installation_case_id, section_id, sha256)`; upload-operation uniqueness and the
case/section lookup remain intact. Populated photo/operation rows and allocator are
preserved, the opposite prefix is untouched, and runtime accepts exact v8 or v19.
The public upload→revoke→identical-upload characterization proves revision 3, two
photo identities, immutable revoked evidence, three ordered operations, one active
photo, one blob, and active-identical idempotency. Existing transaction serialization
and authorization owners are unchanged and focused regressions are green.

Exact production hashes:

```text
f987eda25f5701e5a6c68a862fbadb22252c242f55a015c54971d4d02f722551  app/InstallationProcess/InspectionPhotoContentIndexSchemaMigration.php
70e924bb0b9ce7a44b28397ac6e4d022ae8e4047b71a257588e5e4e8e9146f61  app/InstallationProcess/ProductionPilotMigrationCatalogue.php
232cdf869152114ac294332f9b47d56410429752036e234c6646a09850c8a506  app/PilotHttp/ChecklistSync.php
56fc03edd187d60f729c60c386274a69b8ad1280821dbd606c2b9607b2790ef2  app/InspectionEvidence/MariaDbInspectionAuthorization.php
```

Verification: PHP lint for all scoped production/test/verifier files PASS;
`git diff --check` PASS; independent photo plus current-frontier run 18/18 PASS.
The initial harness setup failure and successful rerun after v19 precondition are
preserved in private evidence SHA-256
`02425ce7c746370d6846d973ea6dff25a894a8578b6402b3faee942bf0cd06e6`.
Root separately reported `tools/architecture/check` PASS (7 rules) on unchanged
production hashes. Full `make verify`, deployment, stand migration, and global
production readiness remain outside this approval.
