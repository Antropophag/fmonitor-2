# Production runtime delivery — 2026-09-09 (#33)

Status: implementation and focused acceptance complete; exact-source full CI and persistent isolated deployment are pending. The main manual stand and its volumes have not been switched or removed.

Production code candidate: `d7bac8ddc4a1066476eb92a06464d84de5d4233b`. Subsequent changes before publication are review metadata and a screenshot wait in the browser verifier. Independent aggregate Gate3: `reviews/tests/PRODUCTION-HTTP-RUNTIME-001.md`; exact-code Gate5: `reviews/code/PRODUCTION-HTTP-RUNTIME-001.md` (APPROVED FOR CI).

## Delivered behavior

- One application image supplies nginx, PHP-FPM and deployment CLI; UID/GID10001, root-owned source, nginx without DB credentials/private volumes, direct MariaDB configuration.
- Explicit prepare/check/migrate commands; no demo manifest, generation lookup, development server or TCP adapter in production startup. Canonical v22 creates/upgrades the legacy object projection safely; incompatible types/keys/engine/charset fail before mutation.
- Whole-catalogue database lock, two real competing runners, stable busy outcome and lock release. DML-only runtime; full process/original/object/identity schema readiness without repair.
- Composite routes and existing UI retained. Explicit trusted scheme fixes native FPM checklist requests. Native session WOULD_BLOCK is distinguished from permanent failures; only reads may continue after transient contention, and mutation failures remain closed.
- Separate live/ready checks; native SIGQUIT drains active nginx/FPM work within the60-second container grace, rejecting new traffic.

## Focused evidence

`PRODUCTION-MIGRATION-RUNNER-001`, runtime schema/preflight/readiness/storage/lock/concurrency suites, full nginx/FPM Compose lifecycle, and the existing protected browser journey all PASS. The browser verifies41 checklist items,7 photos,85-to100 progress, original correction and exact download, distinct opener, exact restart history/session/private bytes, reused authenticated cookie and admin cookie flags. OTIZ seeded fixture build/replay/accept/XLSX also PASS. PHP lint, generated-file drift check, all47 architecture unit tests and actual7-rule architecture check/HTTP qualification PASS.

Durable root browser output: `/tmp/fmonitor-runtime-d7bac8dd-browser-visible.log`.
Retained private synthetic evidence directory:
`/private/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-runtime-browser-d27cfcefd490/browser/`.
Completed-card screenshot SHA256:
`68e5a9247de14da768911dcad27de714f923e837b9d13d7f72574e00eb447c28`.
Root and independent reviewer inspected the unobscured completed card. OTIZ accepted screenshot/result show one object, no console/page/request errors and a7380-byte XLSX. Runtime test images were task-owned and removed; the final retained deployment image digest will be recorded after publication/CI.

## Explicit limitations and following slices

OTIZ transport parity uses a dedicated synthetic registered-order fixture. A completed native selection/original/application journey currently has no legacy registered-order row and is omitted by the existing OTIZ input reader; canonical completion evidence is also required by the prepared successor contract. This existing #24 gap is not fixed or disguised here. Its public RED and approved test live in the separate native-OTIZ worktree. A02/A03 payment/rounding questions remain unchanged.

#27 completes a single clean-install/operator route and explicit initial-owner provisioning; that CLI is not part of #33. #34 jobs planning is isolated in its own worktree. No real external notifications, new production imports, main-stand switch, branch-protection bypass, SLA/RPO/RTO or retention promises occurred.

## First authoritative CI and correction

PR62: https://github.com/Antropophag/fmonitor-2/pull/62 . First exact-head run
34295367260 on3cedf5cb completed FAILURE. Plan/unit/e2e PASS; fast/governance and
both integration shards failed, with final verify correctly failing upstream.
Complete inventories were captured before correction publication in
/tmp/pr62-fast.log, /tmp/pr62-governance.log, /tmp/pr62-integration1.log and
/tmp/pr62-integration2.log.

Corrections preserve each individual migration's original version assertions;
only current canonical terminal/applied-version expectations advance to22.
Synthetic verifier corpora now contain the new runtime files/directories and
reviewed explicit inventory membership. Empty-prefix import uses the canonical
v22 object table. Fictional demo provisioning/ready markers now match v22 and
verify its seven-column legacy upgrade; production startup still never uses demo.
All18 affected integration executables and38 focused governance cases PASS.
Independent approval and exact file hashes:
reviews/code/PRODUCTION-RUNTIME-CI-CORRECTION-001.md. Corrected full CI remains
required; earlier e2e success is evidence, not a claim that the corrected head
has already passed full verification.
