# PRODUCTION-RUNTIME-RESTORE-001 — v22 Gate 5 review

- Reviewer: `/root/runtime_review`
- Fixed point: `839001b427ccff851dc0332cfe2d731e207f2bee`
- Verdict: **APPROVED FOR THE EXACT V22 CONTOUR**

Reviewed artifacts:

```text
b9c3902a481e9066e3f31b637376310ac9e06f7f6e2ba336eb88ccc87f3a40b3  specs/PRODUCTION-RUNTIME-RESTORE-001.md
3fce8bb3bf5d2a6ec826d85668a735b7a459a4501d15b778d0f69e33ba1854b1  app/RuntimeRestore/RuntimeRecovery.php
07d78c9341380923b350dd29c2c74077d98619bdb404967d23547d231f8e19d8  app/RuntimeRestore/RuntimeRecoverySchemaV22.php
de80afeb7affeb558e7d47fb26961e3645d288287837fe6bb37f80df69f36734  bin/fmonitor2-runtime-recovery.php
58ff5d716bdee51e4067f0b2467c0c5efda5e7da68b59356ffe627dd9d3023c1  tests/Runtime/runtime_recovery_001_test.php
ccea0553c16f88ee8a47ef9da1d8cb4e92ca34a81011af1d51a066b4d0f5997e  tests/Runtime/runtime_recovery_tar_fixture.php
7eb5616bc6b40c5e5965fc263d9cf0b738ceaee6d6c217a8b9758fa491b2a28e  tools/delivery/Dockerfile.runtime.in
f8396ad620c1fdfb7b31bad97b27b288396c36e9ef6c8eebb2c20063c4fc4ed1  deploy/runtime/Dockerfile
f8bb46ea3824ac506dee0f5d5ad9b6f362c173c91f74eb03bb215d6a2a1fac25  tools/architecture/check.py
```

The implementation uses the packaged standard MariaDB client for a data-only,
consistent logical snapshot. Restore recreates the exact reviewed canonical schema,
imports complete binary-safe rows, restores the exact AUTO_INCREMENT frontier and
then admits the private state tree through runtime readiness. No dump DDL rewrite or
readiness relaxation was introduced.

Preflight validates the exact v22 frontier, all 63 table identities and all 35
AUTO_INCREMENT table keys before target DDL. Corrupt metadata, bytes and tar shapes
leave an empty target. Source/image values are explicitly operator/orchestrator
attested and mismatch before mutation; the operational drill must retain independent
image-inspect evidence.

Backup and restore reject repository paths, symlinks, foreign types, unsafe modes or
ownership. Publication uses GNU no-clobber movement. A causal PATH-wrapper fixture
creates a foreign final directory at the actual publication boundary; the command
returns `DESTINATION_NOT_EMPTY`, preserves that tree exactly and removes only its
own task-owned partial.

The independently authored real-container test is GREEN for populated exact rows,
files, UID:GID, malicious tar/corruption/nonempty cases, source preservation,
unknown source-table refusal and a deleted-high-id AUTO_INCREMENT gap. PHP syntax,
Docker render, strict OpenSpec, architecture rules and `git diff --check` are GREEN.

## Mandatory integration follow-up

This approval deliberately fail-closes at canonical v22. Once #34 lands migration
23 and six Jobs tables, `RuntimeRecoverySchemaV23`, the manifest frontier/table and
AUTO_INCREMENT inventories, the deferred jobs/outbox statement and the complete
recovery test require fresh independent review. The v22 constants must not be
silently edited or treated as approval for v23 worker/outbox recovery.

## Operational v22 drill addendum

Private evidence `/tmp/fmonitor2-restore36-drill.x3XUZm` was reviewed with directory
mode 0700 and evidence files mode 0600. The final summary records exact source/image
identity, v22/63-table bundle hashes, `RESTORE_COMPLETED`, ready health, exact source
state preservation, owner-session progress 100/private PDF 327 bytes, engineer
session access to seven photos, and three successful append requests ending at
revision 61 with 41 items. All DB dumps remain identical except the three expected
append-only checklist tables.

An earlier PDF 503 was reproduced on both source and restore and traced to the
source browser fixture adding legacy `fm2_assignment_orders.id=81` after native
selection/registry order 81. The registered-composition reader correctly requires
the opposite source family to be empty. Removing only that invalid synthetic OTIZ
shim before the final bundle restored the same public PDF read on source and target;
the final PDF assertion is therefore not vacuous and the failure was not restore
drift.

Tasks 4.1 and 4.2 are supported by the final evidence. Restored OTIZ remains pending
a separate valid legacy case. The shared #33 fixture requires its own reviewed
correction and a post-OTIZ native original-read regression.
