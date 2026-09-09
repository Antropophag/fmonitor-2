# Independent Gate 3 review — PRODUCTION-RUNTIME-BROWSER-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Verdict: **APPROVED (BOUNDED)**

This approval covers the existing protected current-flow journey through the real
production nginx/PHP-FPM seam and its configured HTTP Origin handling. Restart
persistence and the remaining full-runtime acceptance items stay pending.

## Exact reviewed artifacts

```text
85202c9993e4178abd3bcd0ecb76bec2001ae2a73d8dd2f0580e2ed78151ef87  specs/PRODUCTION-HTTP-RUNTIME-001.md
8c83f6b24ae7313e587f6901aec202a1e3d6801b6628241a7348be2c0f2c9ade  tests/Runtime/production_runtime_browser_001_test.php
e5a6ddda89353a04172812b4e9bd20bff7b78843be01b18483c2c6440822214d  tests/Support/pilot_current_flow_browser.cjs
```

## Seam and fixture validity

The test builds a uniquely tagged exact-source image, starts isolated Compose DB,
prepare, migrate, PHP-FPM and nginx services on random loopback ports, and provisions
a DML-only runtime user. `SelectedOriginalFixture` is used only to create and seed
the selected-original domain state through a direct connection to that isolated DB;
its HTTP fixture is never constructed or started. The unchanged browser driver
targets only the published nginx port.

The headless browser performs real local-auth login and session cookies, selection,
template PDF generation, initial original upload, correction, download, distinct-user
opening, checklist mutations/photos, and completion. It retains the existing exact
fact/progress/result assertions and browser console/page/request/HTTP error inventory.
Cleanup removes containers, volumes, task image, fixture database/files, and the
private override tree.

## Demonstrated RED

```text
$ php tests/Runtime/production_runtime_browser_001_test.php
# build, DB seed, prepare/migrate, nginx/FPM, three real logins,
# selection, PDF, original initial/correction/download and opening succeed

INTENTIONAL_RED: protected journey through production nginx/FPM
POST /pilot/objects/4512/checklist/operations
Actual: 403, "Сеанс проверки истёк"
Browser error inventory: only that 403
exit 255
```

The first checklist mutation is rejected because current
`PilotE2ECoordinator` derives production mutation Origin/secure-cookie behavior as
HTTPS unless it sees the CLI-server demo marker, despite explicit
`FMONITOR_TRUSTED_REQUEST_SCHEME=http`. All preceding public production actions pass,
so this is the intended scheme-composition defect rather than broken authentication,
schema, storage, or browser setup.

## Gate decision

**APPROVED (BOUNDED).** Gate 4 may make the coordinator/session admission consume
the explicit validated trusted scheme for production HTTP while retaining demo and
legacy defaults. The reviewed browser expectations and driver must not change
without rereview. The Reflection-based private-method test is not approved and is
not part of this decision.

## Supplemental full-runtime regression evidence

The later expanded browser harness is not a replacement Gate 2 RED. It reuses the
approved production-browser seam after the trusted-scheme and session-contention
REDs were corrected, and adds regression/acceptance barriers:

```text
716300076cfda350c08d52e41c2238eb8b6f95eb82579a50e21483afe5ab8074  tests/Runtime/production_runtime_browser_001_test.php
d37786e11392f628cfbdc5a44bba5b9f8dfc1b0353cfd77c4629fb8b1b6f26d0  tests/Runtime/production_runtime_restart_browser.mjs
ff39308e04cae6a5d4efa75a2f96b0215c613ade258d6777b3eef774f7bd4d30  tests/Runtime/production_runtime_admin_browser.mjs
bc140203a54fb29e080b59c0cb55d0051e23362f90f55bcb286cc2aa9c806b9a  tests/Otiz/snapshot_publication_browser_001_test.mjs
```

The strengthened test snapshots exact ordered rows for original roots/revisions/
events, checklist operations/attribution/photos, and completion facts. It compares
the exact owner session hash before restart and after an authorized reused-cookie
read, and compares private artifact hashes. It also checks production HTTP admin
cookie flags and runs the existing OTIZ build/replay/accept/XLSX browser through
nginx/FPM.

```text
$ php tests/Runtime/production_runtime_browser_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 protected production browser journey
exit 0
```

The OTIZ segment is transport/UI parity over a separately seeded eligible financial
fixture: after the protected journey, the test inserts a synthetic registered order
and installer row required by the OTIZ input model. It does not prove that the
preceding native assignment journey itself creates a seamless OTIZ financial input.
That distinction must remain in any readiness claim.

An opt-in `FMONITOR_TEST_KEEP_RUNTIME_EVIDENCE=1` preserves only the already-private
0700 synthetic artifact directory after containers, volumes and task image are still
removed. Default cleanup is unchanged. The restart driver saves a full-page synthetic
completion screenshot after verifying 100% and authorized PDF access. Syntax and
diff checks pass; these evidence-only changes do not alter assertions or production.

The exact-source retained run passed and the corrected post-restart screenshot was
visually inspected after waiting for the preloader to detach. It clearly shows the
synthetic completed object card, accepted documents, and 100% progress without an
overlay. Screenshot SHA-256:
`68e5a9247de14da768911dcad27de714f923e837b9d13d7f72574e00eb447c28`.
