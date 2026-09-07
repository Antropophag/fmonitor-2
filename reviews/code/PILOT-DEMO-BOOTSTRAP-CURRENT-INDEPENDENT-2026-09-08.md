# Independent code review — current pilot demo bootstrap

Verdict: **APPROVED** for the stable bounded candidate.

Reviewer: `/root/bootstrap_review`, independent from the production authors.
Review date: 2026-09-08 Europe/Moscow. Source HEAD at final review:
`f3e62399776ad77b9de51359bea5a5292e8046b4`.

Reviewed exact production hashes:

- `bin/fmonitor2-pilot-demo.php`: `49889b725ab570c6cd2bda99c823bc6a180160bcc9401b1e1a44931648cd9e06`.
- `app/demo/PilotDemoDatabase.php`: `6cf288a7f585cd8b329c1a95c00b9344090b8358e48abaa1c23f056dc3abef98`.
- `app/demo/PilotDemoProcess.php`: `88a9efefb973d27691b88b7e7c947f824a59e1c5c5dca81e9047b412a081a80d`.
- `app/demo/router.php`: `1331088af0287d4a5fcd34b63eac926a65a715e43b46fa07e1d78994cd54ab61`.

The CLI now provisions the public canonical v19 catalogue through one explicit
fictional-generation database owner and uses the shared process/source namespace
required by current application factories. Synthetic local users, roles, grants,
workforce provenance and object detail data are bounded to disposable demo
generations. Configured actor resolution is exact, case-sensitive, active-only and
fails closed for missing or duplicate identities.

The demo router copies only validated per-server actor and random CSRF values into
the request. Client headers cannot replace either value. Every POST first requires
the exact configured loopback Host, `Sec-Fetch-Site: same-origin`, and the matching
HTTP Origin or the inherited literal-null demo case. Noncanonical hosts receive an
exact 403 before the public router. Session state is generation-scoped.

Process ownership checks use Linux `/proc` or argv-form macOS `/bin/ps` and fail
closed on unavailable or nonmatching command data. Cleanup DDL belongs to the demo
database owner. It accepts only a strict synthetic shared prefix and marker binding
the same fingerprint, generation and nonce, independently validates both anchor
comments, and then drops only that namespace. Historical split-prefix generations
are neither rewritten nor mistaken for current generations.

`tools/architecture/check` passed all seven rules on the reviewed worktree without
changing the baseline. `git diff --check` and PHP lint also passed. The full caller
evidence and hashes are recorded in the independent test review.

No blocking findings.
