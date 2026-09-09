# Independent aggregate Gate 5 review — PRODUCTION-HTTP-RUNTIME-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Implementation author: orchestrating agent `/root` with separately tasked
  planning/test agents
- Reviewed commit: `d7bac8dd` (`Run production HTTP with explicit configuration and locked migrations`)
- Baseline: `eb88ed2f`
- Verdict: **APPROVED FOR CI**

## Outcome

No blocking code finding remains in the frozen #33 candidate. The implementation
conforms to the normative production-runtime contract and the owner-authorized
architecture order. This verdict authorizes normal CI/integration progression; it
does not declare production readiness or authorize deployment of the working stand.

## Conformance review

The candidate adds one explicit production composition: direct validated MariaDB
configuration, private storage preparation/readiness CLI, root-owned application
source, non-root UID/GID 10001, separate nginx and PHP-FPM processes from one image,
and a front controller delegating the complete rapid composite router. Nginx has no
DB credentials or private-volume mounts. Trusted Host/scheme are configuration-owned;
client forwarded values do not become authority.

Canonical migrations remain a separate CLI under DDL authority. One deterministic
MariaDB lock covers preflight and the complete catalogue, returns a stable busy
outcome, and releases after success/failure. Canonical v22 registers the legacy
object projection with conflict-before-mutation validation of supported columns,
InnoDB/utf8mb4 storage, and the id primary key.

Runtime readiness uses read-only validators for the complete process family,
object-detail/original/identity/selection/application/checklist/completion/OTIZ and
workforce families. Missing or malformed route-critical schema returns not-ready
without repair. The architecture checker now prevents migration or demo startup
from being reintroduced under Runtime/public composition, with no new baseline or
rule category.

Transient native flock contention is distinguished from permanent failure. Only
`start(existingId)` may wait and then read fresh committed bytes; writes,
regeneration and destruction remain fail-closed after contention, so stale payloads
are not published. PHP-FPM's process-control timeout fits inside Compose's stop grace;
real nginx and FPM tests prove active-response drain, refusal of new nginx traffic,
bounded exit and no retained process.

## Verification evidence reviewed

- Configuration/package, storage, v22 frontier/preflight, full-process and
  route-family readiness, migration lock/two-runner, and session contention focused
  tests: GREEN.
- Real production Compose lifecycle: GREEN, including DML-only principal,
  missing-schema no-repair, health/outage recovery, Host rejection, nginx/FPM drain.
- Full headless production browser: GREEN through nginx/FPM for multi-user login,
  selection, PDF template, original upload/correction/download, distinct-user
  opening, 41 checklist items, seven photos, 85%→100% completion, restart, exact
  DB/session/artifact preservation, reused authenticated cookie, admin HTTP cookie
  flags, and seeded OTIZ build/replay/accept/XLSX.
- Architecture checker: clean with seven rules; complete architecture unittest
  inventory `47/47` GREEN; HTTP global-call qualification GREEN.
- `git diff --check eb88ed2f..d7bac8dd`: GREEN; OpenSpec strict validation and
  generated Dockerfile drift checks: GREEN.

Detailed commands, RED lineage, identities and bounded independent decisions remain
in the associated `reviews/tests/` and `reviews/code/` records. Supplemental
immediately-GREEN persistence/admin/OTIZ assertions are correctly treated as
regression/acceptance evidence rather than fabricated RED history.

## Known limits and remaining gates

The OTIZ browser uses a separately seeded synthetic registered order/installer to
establish financial eligibility. It proves #33 HTTP/runtime parity, but the completed
native UI application journey still does not feed `MariaDbNativePremiumInputs`
without that legacy-shaped row. `NATIVE-OTIZ-INPUTS-001` records a genuine RED for
the next #24 slice; no #33 code hides or claims that integration.

Initial production administrator provisioning remains the documented #27 operation.
The current runtime intentionally performs no admin/demo bootstrap. A real isolated
persistent contour, retained final screenshot/log, image digest, one authoritative
full CI `make test`, PR integration, and any deployment remain pending. The manual
stand and its volumes were not changed.

## Verdict

**APPROVED FOR CI.** Commit `d7bac8dd` is suitable for the authoritative full CI and
normal integration workflow. Production-ready/Done may be claimed only after the
remaining exact-source evidence, CI and isolated persistent deployment gates pass.
