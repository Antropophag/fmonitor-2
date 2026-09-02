# OTIZ snapshot acceptance — fresh independent Gate 1 rereview

- Reviewer: Codex subagent `/root/otiz_gate1_rereview_0901d`
- Date: 2026-09-01
- Independence: fresh reviewer; did not author or edit the executable
  specification, evidence, OpenSpec artifacts or production source
- Reviewed specification:
  `specs/CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001.md` v0.1
- Prior review used only as a blocker checklist:
  `docs/operations/otiz-snapshot-acceptance-gate1-review.md`

## Findings

1. **Gate-blocking — the LocalAuth/RBAC prerequisite rows are neither exact nor
   coherent with the cited exact manifest.** The specification says each user
   row contains `source=gate1_fixture` and nullable legacy/last-login fields,
   but `IDENTITY-ACCESS-SCHEMA-001` defines no such columns. Conversely, the
   exact user manifest requires `phone` and `session_version`, for which the
   fixture gives no literals. The role row omits exact `description` and
   `source_updated_at`; the two user-role rows omit exact `origin`,
   `assigned_at` and `assigned_by_user_id`. Thus the instruction to fingerprint
   full ordered prerequisite rows cannot have one independently determined
   expected value, and a RED author cannot seed the claimed exact fixture
   without inventing values. Replace the nonexistent fields with literals for
   every column in the cited six-table subset, including all role and
   assignment columns. The staged GET/email/password logins, regenerated
   independent sessions and authenticated denial for actor `8802` are otherwise
   exact and source-aligned.

The other three prior blockers are closed:

- every object monetary/progress field now has a coherent literal, and the
  evidence label/locator plus issue null/resolution variants are fixed; full
  typed pre/post fingerprints make child mutation observable;
- the verification-only prepend logger binds nonce, exact parsed path and PHP
  PID without modifying the production seam, while an external record lock and
  MariaDB lock-wait observation require both request transactions to be
  simultaneously waiting before release; distinct sessions, workers and the
  winner-neutral response/event oracle prevent a single-worker pseudo-race;
- the deterministic cleanup-fault probe preserves primary `REGRESSION`, emits
  a separate sensitive `CLEANUP_FAILURE`, continues best-effort cleanup through
  fallback, proves zero owned artifacts and preserves both ambient decoys.

The complete behavior matrix remains strictly `PILOT_ONLY` and does not promote
target financial semantics. It covers the real router/LocalAuth path; admission
→ broad `otiz.manage` → constructor DDL → CSRF → row lock; twelve tables and
conditional `unique_reversal`; literal draft data independent of premium code;
exact HTTP status, redirect, cache and plain-body contracts; the three accepted
snapshot fields and single append-only event; independent live Moscow clocks;
unchanged children; missing, immutable, blocker, warning and resolved-blocker
cases; sequential replay; real two-worker concurrency; unique ownership tokens
and disabled session GC; exact session cleanup and ambient preservation;
separate setup/regression/cleanup outcomes; repeatable normalized transcripts;
and cleanup on success and intentional failures. Expected business values are
specified independently of the future verifier. RED and implementation remain
explicitly prohibited before owner approval.

## Verification evidence

- `openspec status --change characterize-otiz-snapshot-acceptance --json` — all
  four planning artifacts reported `done`.
- `openspec validate characterize-otiz-snapshot-acceptance --strict` — passed.
- `git diff --check` — passed after this review was written.
- `make architecture-check` — passed after this review was written.

## Verdict

CHANGES_REQUESTED
