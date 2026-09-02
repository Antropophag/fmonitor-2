# OTIZ snapshot acceptance — third fresh independent Gate 1 rereview

- Reviewer: Codex subagent `/root/otiz_gate1_rereview_0901e`
- Date: 2026-09-01
- Independence: fresh reviewer; did not author or edit the executable
  specification, evidence, planning artifacts or production source
- Reviewed specification:
  `specs/CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001.md` v0.1
- Prior reviews used only as blocker checklists:
  `docs/operations/otiz-snapshot-acceptance-gate1-review.md` and
  `docs/operations/otiz-snapshot-acceptance-gate1-rereview.md`

## Findings

No gate-blocking findings remain.

The remaining LocalAuth/RBAC fixture blocker is closed exactly. The three user
rows now assign literals to every column of the cited current manifest, in
particular `phone`, `status`, `activation_state`, `session_version` and
`source_updated_at`, without the nonexistent fields noted by the prior review.
The single role fixes `code`, `name`, `description`, `status` and
`source_updated_at`; its permission tuple is exact; both assignments fix all
five `user_id, role_id, origin, assigned_at, assigned_by_user_id` values; and
actor `8802` has no assignment. Credential rows fix every manifest field except
the deliberately setup-generated Argon2id salt/bytes, whose plaintext,
algorithm, successful `password_verify` result and transcript normalization are
independently constrained. The empty auth-attempt baseline and exact staged
email/password exchange agree with `RapidPilotLocalAuth`; actor `8802` completes
that exchange before the OTIZ `403`, so the denial is RBAC evidence rather than
an authentication failure.

The complete contract was rechecked against the behavior evidence, OpenSpec
package, product/process constraints, identity manifest and current
`LocalAuth.php`, `IdentityBootstrap.php`, `router.php` and `Otiz.php` sources.
It remains internally coherent and strictly `PILOT_ONLY`: it exercises the real
LocalAuth/router seam; preserves admission → broad `otiz.manage` → ordered
constructor DDL and conditional `unique_reversal` → CSRF → row lock; seeds
literal snapshot and child data independently of premium formulas; fixes exact
HTTP status, `Location`, cache and plain-body outcomes; observes only the three
accepted snapshot fields and one append-only event; bounds the two live Moscow
timestamps independently; and fingerprints unchanged children and rejected
facts. Missing, immutable, blocker, warning, resolved blocker and sequential
replay outcomes are exact.

The concurrency proof requires two correlated serving PIDs and both request
transactions simultaneously waiting on an externally held snapshot record lock
before release, with winner-neutral responses and facts. Port, cookie, session,
nonce, SQL and process ownership is unique; session GC is disabled; deletion is
limited to exact verifier-owned files; ambient DB/session decoys survive; setup,
regression and cleanup faults remain distinct; and success/failure cleanup plus
repeatable normalized transcripts are executable and sensitive. The spec does
not promote target authority, financial meaning, separation of duties,
idempotency, persistence or payment consequences, and explicitly prohibits RED
or implementation before owner approval.

## Verification evidence

- `openspec status --change characterize-otiz-snapshot-acceptance --json` — all
  four planning artifacts reported `done`.
- `openspec validate characterize-otiz-snapshot-acceptance --strict` — passed.
- `git diff --check` — passed after this review was written.
- `make architecture-check` — passed after this review was written.

## Verdict

READY_FOR_OWNER_REVIEW
