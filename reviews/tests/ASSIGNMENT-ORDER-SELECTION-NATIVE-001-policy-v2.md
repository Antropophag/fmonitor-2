# Independent Gate 3 rereview — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 policy v2

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `329647fa2c700e0a929839a7eecc0ea8c75a14a0`
- Reviewed test SHA-256: `b40294617fb13ec8dae757e0f9c3046929fbf82df6f36f0ef3307eec1cc72234`
- Gate 1 spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-policy-red-v2-qfr8zuf_`
- RED log SHA-256: `3fe96c3df6e7e815c724af9024af72697cbdf281891bbd8f8fbd436020a0bf25`
- Review date: 2026-09-06

## Delta review

The v1 blocker is closed. The isolated public-CLI helper now copies only `check.py` and writes a literal empty architecture-debt baseline. It never reads or copies the repository baseline. Adding synthetic selection findings to the real baseline can therefore no longer satisfy the positive cases; the three DML fixtures can become green only when the scanner recognizes `app/AssignmentOrderComposition/MariaDb*.php` as a SQL owner.

The correction adds no mutation, poisoning, private scanner call, or production-policy implementation. The prior assertions remain unchanged: `SELECT`, `INSERT`, and `UPDATE` must be accepted only in the narrow MariaDB binding path; a non-MariaDB application class must still fail `sql_ownership`; and DDL in the MariaDB binding must still fail `ddl_ownership`, preserving `InstallationProcess/*SchemaMigration.php` ownership. The empty baseline also makes those prohibitions independent of existing debt.

The captured v2 run executed three tests and exited `1`. Both prohibition tests passed. The three DML subcases failed with their exact `sql_ownership: new violation` findings for `MariaDbSelectionExample.php`, demonstrating the missing narrow owner rule after valid isolated setup.

## Gate decision

Gate 3 is **APPROVED** for the exact v2 test commit and hash above. Gate 4 may make the minimal `check.py` owner-policy change without changing the reviewed expectations or growing the architecture baseline. This review does not assess the separate native tracer implementation or reopen core, schema, construction, or tracer approvals.
