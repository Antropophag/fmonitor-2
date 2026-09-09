# DURABLE-JOBS-WORKER-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed production artifacts:

```text
f82126e920620f07c0b6ff6161ae4491b06e91caa9a147836516fae9cd9e9ab3  app/Jobs/JobWorkerProcess.php
b7bedd75521537c3a768bda5dd8ab1d417739a97778e1b0e0e94dfae90ec7521  app/Jobs/MariaDbWorkerHeartbeat.php
```

The parent uses a separate argv-only handler process, bounded nonblocking stdin and
live stdout/stderr caps. It accepts only the reviewed closed output objects; all
syntax and semantic protocol failures become safe retryable outcomes. Heartbeats
advance only on the exact 60-second UTC boundary. Lost lease or heartbeat failure
kills and reaps an active child and never records a guessed result. Settlement
counts only queue-accepted completion/retry/dead outcomes.

Independent verification passed the graceful signal, forced grace, late stale
settlement, live-child lease loss and protocol matrices. Architecture rules and
`git diff --check` are clean. Production registry/service wiring remains a separate
integration task.
