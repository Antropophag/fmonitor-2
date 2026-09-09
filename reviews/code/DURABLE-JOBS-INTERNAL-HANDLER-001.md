# DURABLE-JOBS-INTERNAL-HANDLER-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed artifacts:

```text
95772b94c2a2149c9c164bcbf1775a88e028c5c8cd721dd77d74601bf061a06b  bin/fmonitor2-job-handler.php
f1ae95b5c74f8b4f5dcbf20bc3ed759a76c4f532bca3b13dfe854c6726d7f5e4  app/Jobs/JobHandlerClaim.php
a48a0bde8137da2b719272eb994770b2c5e966bfee8830efa69059ccb0f2c9f2  app/Jobs/JobHandlerRuntime.php
056e13037153780f6eedb57d8162b43a5d175efbe66b68c4224e79922f37edc2  app/Jobs/JobsRuntimeConfiguration.php
babaa3981df2cf8e72ab4852047427d14217cb8aa71bc27489e351b71735f50d  tests/Jobs/jobs_runtime_handler_validation_001_test.php
d4df49f5d048f38c665909361b3594d0fd250ac7f87b8d4917a29bcc56525b25  tests/Jobs/jobs_runtime_workforce_cli_001_test.php
```

The handler parses the complete bounded claim and exact registered payload before
configuration, DB or transport access. Runtime configuration is captured once;
optional CA lookup no longer reads mutable ambient process state. Missing CA is
supported, while invalid delivery configuration is classified as configuration
failure before DB/transport.

Workforce dispatch uses the existing verified delivery factory and native
synchronization owner on its own DML connection. Output is reduced to the closed
worker protocol. Unconfigured outbox dispatch is permanent and performs no network
or intent access.

Malformed/type/version/shape, invalid/captured/missing CA and verified local HTTPS
51-row publication tests pass. Architecture remains seven clean rules and
`git diff --check` passes. Public Jobs CLI and Compose production wiring remain a
separate Gate 5 review.

## Linked Workforce retry addendum

Operator-linked retry creates a new execution `jobIdentity` while retaining the
immutable scheduler payload and its original `runIdentity`. The initial parser
incorrectly required those identities to be equal. The corrected parser validates
both UUIDs independently and the native owner continues to receive the current
claimed `jobIdentity`.

```text
838148721aa38a7f6e12c82903ed4d35b2ade4b2ec993965a5ab93bd52d2f09f  app/Jobs/JobHandlerClaim.php
31c92ed7f9293eb34c8d215fccb454817e078e9fba664d6c6d070fcf839655f3  tests/Jobs/jobs_runtime_workforce_retry_cli_001_test.php
82256aa8c5d52b8719bcc289b75992c7a93fd1e87f79c0a6913cbd7850690505  specs/JOBS-RUNTIME-WIRING-001.md
```

The public queue→fail→operator retry→claim→real internal HTTPS handler regression,
handler validation and direct workforce handler tests are GREEN. **Addendum verdict:
APPROVED.**
