# DURABLE-JOBS-OUTBOX-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed production hashes:

```text
e80d84275cfcf935ff64efdc4722123ef1f88588819d1d8e1cd29d0e1b5e1c4d  app/Jobs/MariaDbOutbox.php
02a1013a60441e0ae30dc9503bcb18e840728bf9c4eed268f3d953c44fb63cb8  app/Jobs/OutboxDeliveryHandler.php
6b9c1eb66d305f1e8df7863a38657ef87affdd10b43dcedd67b458344306e698  app/Jobs/OutboxDispatchScheduler.php
c9b7ce53ed2f3b8e70804f4ac345a404d6a452931853acdcba773b40134738d1  app/Jobs/OutboxDeliveryOutcome.php
7f5fb50305573221a3df13d1659422ee1e36eea8e2e28120dbee244933fb863c  app/Jobs/MariaDbOperatorJobs.php
01e21a542ee515bfc979d09dbd5fc6fcea9a6f50b2a383c6884c346a7ba2a8cb  app/Jobs/MariaDbJobEnqueue.php
7598e846a6ef2797dc624f5bb3300e18962d99b3a902623fb561cc5232a9a6d1  app/Jobs/MariaDbJobQueue.php
0f304c6f94b0d2ebe55ab57333778e320384435dacb56bd3c17ebcd7fc3225b7  app/InstallationProcess/JobsDeliveryDefinitionSchemaMigration.php
```

Append stays on the caller-owned transaction. The post-commit sweep creates a
generic queue job from immutable intent time/identity and never invokes transport.
Delivery runs outside DB transactions, persists allowlisted outcomes, replays exact
`intent/job/attempt` results across queue-transition gaps, and retains one
intent-wide provider idempotency reference.

Attempt history now carries a Jobs FK and unique `(intent_id,job_id,attempt)`, so a
linked operator retry may restart at attempt 1 without colliding with the terminal
job. An unlinked job cannot revive a dead intent. `MariaDbOperatorJobs::retry`
requires the exact closed command including `nowUtc`, authorizes before mutation,
uses a per-operation advisory lock, and atomically inserts the linked job and event.
The shared enqueue extraction preserves the public queue behavior.

Independent focused verification:

```text
transactional_outbox_001_test.php: PASS
outbox_delivery_lifecycle_001_test.php: PASS
durable_queue_001_test.php: PASS
durable_queue_concurrency_001_test.php: PASS
jobs_extensions_schema_001_test.php: PASS
architecture check: ok=true, rules=7, errors=[]
syntax and git diff --check: PASS
```

Operator listing, Jobs health and production runtime wiring remain outside this
approval.
