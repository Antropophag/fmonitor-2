# DURABLE-BACKGROUND-JOBS-001 — aggregate bounded Gate 3 record

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED FOR THE BOUNDED GROUPS BELOW**

Current normative hashes:

```text
5f5b089dd9f421006fba52d35131fcc988255580efb8cb286fa735c2dd914dea  specs/DURABLE-BACKGROUND-JOBS-001.md
3db503af23cb98e17b3dc70c677252360ee91cdb7559cbd9096db0be2ef423e5  specs/DURABLE-JOBS-SCHEMA-001.md
b02adcd3f643b228fa273ce2e93f3856e789472d2a46b74bc84fdd468c583022  specs/DURABLE-JOBS-EXTENSIONS-SCHEMA-001.md
b1fa3ea1a9f5be8b7055fc76b3ecbbbf8774058e06996155f08786ce2a338101  specs/WORKFORCE-JOB-IDEMPOTENCY-001.md
73df41a5ae6249a1f392f422c5380c8134401c5e3df6a89003b5cb8104250e2d  specs/JOBS-WORKER-RUNTIME-001.md
```

## Queue and schema

```text
3385026d5bbe44a6b34bea141859b88b351e89797af7169ae62c98161fc0baef  tests/Jobs/durable_queue_001_test.php
e94738945edb76deb9fad927e4734e42f52239526ec8ec1b0674aaa059a36772  tests/Jobs/durable_queue_concurrency_001_test.php
729ac494b90b5b28e8a40533a06e9adb79480aebdc01690d1ab704b263097322  tests/Jobs/jobs_schema_001_test.php
35e82e0d84a95714c0af46286af82a7f369a1eaa26fcbc94b1bf9d55d212fc86  tests/Jobs/jobs_extensions_schema_001_test.php
9461cf8d3ab273ad2e4edfa792aeebe47682d7051d2e17d1bd937f0a498af765  tests/Jobs/jobs_schema_check_literals_001_test.php
```

The original queue RED failed at the absent public queue/schema seam. It was
strengthened before implementation with a parent-held row-lock race, distinct lease
tokens/stale outcomes, validation and heartbeat boundaries. Schema REDs separately
proved the missing two-table foundation, four-table extension, both whole-family
preflight directions, DML-only execution and quoted CHECK-literal sensitivity.
Dedicated schema reviews remain in this directory.

## Transactional outbox and recovery

```text
54918aefbd7c7d2d2864edcc20eec4b3c0d2db98828e96839980e3c193aab65f  tests/Jobs/transactional_outbox_001_test.php
e84c6260278677ad1c0c439b8adf2454eb7299c6ce0c1ace3bb75b63873a8374  tests/Jobs/outbox_delivery_lifecycle_001_test.php
```

The ambiguity foundation first failed at absent outbox seams and was reviewed before
implementation. Later terminal assertions are an approved extension: generic queue
attempts 1–5, crash-gap replay, permanent failure, intent-wide provider identity,
unlinked dead-intent denial and verified operator-linked recovery. The attempt schema
was superseded before implementation from `(intent,attempt)` to
`(intent,job,attempt)` so a linked job can restart at attempt 1.

## Workforce schedule and native job identity

```text
2f952e5cb5a7528413cbeeb4b7822650687c746b36caafa15b2130f03b190a20  tests/Jobs/workforce_scheduler_001_test.php
39f1246359b1066f5ff63af576c3ce3a5e60fb26e87fba13e9289abf960128f1  tests/Jobs/workforce_scheduler_concurrency_001_test.php
18899eb71591b60233c17005b33808aa2eedc6c2ce3ca8f13e06f57369b5baa8  tests/Support/jobs_scheduler_worker.php
61edf4c6dfc461730d0114188b4ad608cd0565211d1afff66ae3d46b9f3d2507  tests/Jobs/workforce_job_idempotency_001_test.php
1030b33d8c7f3adc49a27aacb74d5d63aacc2195804388cb4063cd9f1183fdf1  tests/Jobs/workforce_job_handler_001_test.php
```

Scheduler RED failed at the absent public scheduler. Its initial start barrier was
superseded by a parent-held latest-slot row and bounded two-process test. Exact Moscow
slots, deterministic UUID/payload and latest-only catch-up remain. Native workforce
RED failed at absent `runForJob`; the final test fixes literal attempt identities,
durable failed/completed replay and zero-mutation lock contention.

## Worker process lifecycle and protocol

```text
fe9763bb5422979be3252671b75ff6b2a078575552f56a60267dad368d38d183  tests/Jobs/worker_signal_runtime_001_test.php
d62397e77c9f51f4aa1b62e12c80292101954ed0348f96cc06fb025135a23144  tests/Jobs/worker_grace_expiry_001_test.php
9efb66afd1c4681cb72610ddcb9704a8ac4836eb98f5f2378cfd950f13572104  tests/Jobs/worker_stale_settlement_001_test.php
4b13d5c29cafb9a6b31a47d3138dc2f794b09eb29be6bbd11bed18334eb98f3c  tests/Jobs/worker_lease_loss_process_001_test.php
28551d973d5a23214d4ebc58a67d2957f786d39a64dbdb7d7515f7cd0cc8a83a  tests/Jobs/worker_protocol_001_test.php
```

The original absent-process RED gained separate graceful and forced-grace branches,
then stale settlement, live-child lease-loss and closed protocol matrices before
final approval. Current evidence covers exact heartbeat cadence, child reap, no
guessed result and bounded invalid/oversized/non-reading handler behavior.

## Explicit exclusions

At the time of the initial aggregate, operator failed-job listing and Jobs health
were pending; the addendum below now records their bounded Gate 3 approval. This
aggregate does **not** approve their production Gate 5, deployment
CLI/service/Compose wiring, production handler enablement or live external
transport. `MariaDbOperatorJobs::retry` production code is approved only as used by
the reviewed outbox recovery contract. Remaining production surfaces require
independent Gate 5 evidence.

Historical candidate hashes discussed during review were superseded by the exact
current hashes above; this record does not claim those earlier candidates were
GREEN. Current focused GREEN results are recorded in the corresponding code reviews
and agent logs.

## Operator listing and DB health Gate 3 addendum

```text
b92593ee34d1ef1af82db79bd9098cfc770fe9304721ab28338a1f9a84f4fd88  tests/Jobs/operator_health_001_test.php
```

The corrected RED asserts exact bounded page/item allowlists, unauthorized
list/retry with zero mutation, stable linked retry without inline execution, and
full Jobs/events/heartbeat/slot snapshots around health reads. It distinguishes
dead, expired, overdue and stale process counters, proves a configured legacy marker
irrelevant, and includes a separate healthy process-heartbeat control.

**Gate 3 verdict: APPROVED.** Production listing/health Gate 5 is recorded in
`reviews/code/DURABLE-JOBS-OPERATOR-HEALTH-001.md`; deployment runtime wiring
remains pending.
