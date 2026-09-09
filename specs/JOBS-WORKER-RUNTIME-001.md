# JOBS-WORKER-RUNTIME-001 — process worker, heartbeat и graceful stop

## Contract

`JobWorkerProcess::run()` is the production parent loop. It owns a fresh queue DB
connection, claims only registered jobs, and launches one handler CLI subprocess per
claimed job from the same image. Handler receives bounded job JSON through stdin,
opens its own DB connection, and returns one bounded JSON result. Parent and child
MUST NOT share an inherited mysqli socket; no shell command interpolation is used.
Parent writes stdin nonblocking with a 5-second bound, caps stdout and stderr at
65535 bytes while child is live, and accepts only one JSON object with an allowlisted
status shape. Invalid JSON, oversized output or child error becomes safe retryable
`JOB_HANDLER_FAILED`. A child that does not read stdin remains subject to stop grace
and is killed/reaped without guessed result.
Closed output shapes are exactly completed `{status,result}` with a bounded canonical
JSON object, or retryable/permanent `{status,failureCode}` with an uppercase safe code.
Lists, unknown/extra keys, invalid codes, list/secret result and every other shape map
to retryable `JOB_HANDLER_FAILED`. Run field `completed` counts accepted settlements
(completed/retry/dead), not only terminal-success jobs.

While child runs, parent polls it and updates lease/worker heartbeat whenever the
authoritative UTC clock advances at least 60 seconds. On SIGTERM/SIGQUIT parent stops
new claims, lets current child finish within configured runtime grace, persists its
result, reaps it, records final heartbeat and exits. If grace expires it terminates/
reaps child and leaves the lease for normal expiry/reclaim; it MUST NOT guess result
or immediately release an unknown effect.

Production handler registry is closed and no external handler service starts without
explicit configuration. Tests inject only local `test.blocking` registry/CLI and a
fixed file-backed clock; no Bitrix/email call occurs. Worker runs under DML-only
principal and never invokes migrations. Health reads DB heartbeat, not a ready-file.

## Acceptance

Two ready jobs exist. Parent claims first and a separately connected child blocks.
Advancing fixed clock by 60 seconds writes heartbeat and extends the lease while
child remains alive. SIGTERM stops further claim; after child release it completes
exactly first job, leaves second ready, reaps child and exits within a bounded test
deadline. A forced grace expiry leaves first leased until expiry; another worker can
reclaim afterward with no orphan process.
