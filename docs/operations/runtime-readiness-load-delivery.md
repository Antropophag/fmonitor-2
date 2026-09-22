# RUNTIME-READINESS-LOAD-001 delivery

## Scope and authorship

- Owner assignment: 2026-09-22, bounded readiness-load fix before user testing.
- Base: 3c242f34e8f30986f1b8354c4ef947a4c63936dc (merged PR #226).
- Worktree: fmonitor-2-readiness-load.
- Root /root authored scope, contract, OpenSpec artifacts, verification policy
  registration and tests. No autonomous test/spec delegation was authorized.
- /root/implement_readiness (gpt-5.6-sol, low) authored production code.
- /root/gate3_readiness (gpt-5.6-sol, low) independently returned one
  CHANGES_REQUESTED, then APPROVED; the retained record lists dispositions.

No merge, deployment, import, working-stand mutation or external send was
authorized or performed.

## Call chain and implementation

Before: HTTP bootstrap checked storage/DB availability; controller called
RuntimeReadiness, which performed the full schema-fingerprint walk. PHP and web
healthchecks independently hit the endpoint every five seconds.

After: existing v32 catalogue and canonical table metadata provide database identity. A one-shot
startup-check follows migration and precedes PHP; the existing runtime-check
invalidates stale evidence, performs full schema compatibility and atomically
publishes a private DB/schema/build/prefix-bound result. Each steady probe checks
storage, a current connection, SELECT 1, one identity lookup and that result.
No ERP, Bitrix or SMTP call was added.

## Focused evidence

GREEN: readiness-load, packaging, schema-frontier, schema-family readiness,
process readiness, architecture, verification planner/inventory, canonical
integration runtime, generated dependency check, PHP syntax and diff whitespace.
Exact commands remain in retained harness evidence.

Earlier recovery builds were interrupted when unexpected concurrent laptop load
became visible; those attempts were not GREEN. The final exact-source CI matrix is
GREEN, and the final focused exact Compose lifecycle/measurement below is GREEN.

## Isolated measurement

Baseline contour: Compose project fm2_readiness_measure, MariaDB 11.4.7, unique
port/database prefix/volume and base/candidate worktrees. Final candidate contour:
the exact committed application image and isolated production Compose project from
production_runtime_compose_001_test.php, with unique DB/state/secrets/port. It
exercised real nginx → PHP-FPM → public readiness. Neither result is a site
throughput or p95 claim.

Global counters were sampled immediately before/after on the otherwise isolated
MariaDB. The MariaDB healthcheck and sampling query itself are background noise;
deltas are upper bounds, not attribution of every command to the probe. A
one-second control observation showed counters advancing without a probe.

| Observation | Base | Candidate |
|---|---:|---:|
| SQL Questions, one probe delta | 517 | 10 |
| Created_tmp_tables, one probe delta | 5,523 | 1 |
| Created_tmp_disk_tables, one probe delta | 922 | 0 |
| 10 sequential probes wall time | 26.62 s | 0.10 s |
| 4 concurrent probes wall time | 3.167 s | 0.077 s |
| candidate initial full startup check | — | 2.08 s |

Final exact-candidate HTTP/Compose observation: control interval +2 Questions,
+1 temporary table and +0 disk temporary tables; one measured readiness +8
Questions, +2 temporary tables and +0 disk temporary tables in 5.8 ms; ten
sequential probes +62 Questions, +11 temporary tables and +0 disk temporary
tables in 38 ms; four concurrent probes +26 Questions, +5 temporary tables and
+0 disk temporary tables in 13 ms. All returned HTTP 200. Sampling queries and
background healthchecks remain included upper-bound noise. The focused test
removed its Compose containers, network and volumes.

These short measurements confirm the owner's approximate 550-command observation
on this contour without treating readiness as user request capacity. The
measurement container, network, volume, state directories and detached base
worktree were removed afterward.

## Remaining gates and limitations

- Final independent rereview remains pending. PR #234, current-main reconciliation
  and exact-source Quality Graph run 35694634455 are GREEN.
- Arbitrary unsupported DDL drift after a successful startup is not continuously
  fingerprinted; identity change, update/restart startup check and existing
  operation guards fail closed at their owned seams.
- Exact image startup, login/card-editor/construction-control/OTIZ smoke and full
  active verification matrix are GREEN in exact-source CI.
