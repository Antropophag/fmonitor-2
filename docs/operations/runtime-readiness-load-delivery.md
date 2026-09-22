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

After: canonical migration v33 owns one database-identity marker. A one-shot
startup-check follows migration and precedes PHP; the existing runtime-check
invalidates stale evidence, performs full schema compatibility and atomically
publishes a private DB/schema/build/prefix-bound result. Each steady probe checks
storage, a current connection, SELECT 1, the singleton marker and that result.
No ERP, Bitrix or SMTP call was added.

## Focused evidence

GREEN: readiness-load, packaging, schema-frontier, schema-family readiness,
process readiness, architecture, verification planner/inventory, canonical
integration runtime, generated dependency check, PHP syntax and diff whitespace.
Exact commands remain in retained harness evidence.

Recovery tests that start private Docker image builds were interrupted when that
unexpected heavy work became visible; full exact-source execution is delegated to
CI under the owner laptop-load rule. An isolated UI smoke attempt was not
applicable locally because this worktree intentionally has no vendor dependencies;
the resulting setup failures are not GREEN.

## Isolated measurement

Contour: Compose project fm2_readiness_measure, MariaDB 11.4.7, unique port
24336/database prefix/volume, base and candidate source worktrees. The application
image was not built because another live runtime and a high-CPU foreign process
were present. The measured seam reproduced request bootstrap order:
storage check → DB availability connection → runtime readiness. It is not a site
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

These short measurements confirm the owner's approximate 550-command observation
on this contour without treating readiness as user request capacity. The
measurement container, network, volume, state directories and detached base
worktree were removed afterward.

## Remaining gates and limitations

- Final independent review, exact-source GitHub CI, PR creation and reconciliation
  with then-current main remain pending.
- Arbitrary unsupported DDL drift after a successful startup is not continuously
  fingerprinted; marker loss/change, update/restart startup check and existing
  operation guards fail closed at their owned seams.
- Live image startup and requested UI smokes await exact-source CI because local
  dependencies are absent and concurrent laptop load made another build unsafe.
