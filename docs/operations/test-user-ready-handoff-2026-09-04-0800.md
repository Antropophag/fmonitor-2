# TEST-USER-READY handoff — 2026-09-04 08:00 MSK

Status: **NO-GO / release RED**. This record does not claim `VERIFY_OK`, Gate 5
approval, CI success or TEST-USER readiness.

## Exact pushed refs at freeze

- Quality Graph: `codex/quality-graph-governance-lineage` — `f07548135fe930e7a8fb9bb97271c9f05a8ebfc1`.
- Original upload: `codex/original-assignment-upload` — `7244be343d650cb72ec83ff01019bb8adbb467dc`.
- Prepare RBAC planning: `codex/pilot-prepare-rbac-green` — `f897285b4a6e168df1fab04273b377b92331a015`.
- Navigation: `codex/remove-pilot-work-navigation-v2` — `15e545d8c4f8aa63915ec97af0ba111f3160d560`.
- Object list: `codex/object-list-rbac-green` — `3b93f650fc6910b69848798ede036e8dc097908a`.
- Session route/architecture/consumers: `3ae214f75b898d171c68bb127dec10f17e03117a`, `03d3720ed79d27308745987ad9e5b639e72f4c75`, `1ec876e7fbb12ee1738da178974ab8f0b55e87a0`.
- Integration worktree: `codex/integrate-quality-graph-governance` — `c2e729d582c0b665936fdf7fdf74b6662275cfbd`.

All listed tracked worktrees were clean and tracked refs matched origin at the
freeze audit. Completed temporary original-upload worktrees were removed only
after clean-state checks; their branches/commits remain preserved. The
temporary empty Docker config used to bypass a broken local credential helper
was removed; only public pinned image cache remains.

## GREEN

- Original-PDF v12 schema, typed application, passive structural parser,
  private storage/leases, maintenance and real MariaDB five-FD worker have
  focused GREEN evidence. Structural-parser production correction:
  `442af7b5cbb9b090ec8e2360adeb1a7378aaa711`; private orphan/reuse correction:
  `93ba7ccc7a558ff34848c3aa09e012cbb8619b87`.
- Anonymous session repeated-write correction production commit
  `5e00338411e03031a01b84cb8acf403853ce3821` makes the public session regression
  and checklist endpoint/UI-client suite GREEN. Rapid auth-hot-path, TCPDF
  renderer, artifact store and production composition are focused GREEN.
- On original-upload verification, reset, migration v12, architecture 7/7,
  lint, characterization and diff-check passed. With isolated public Docker
  config, `make unit-test` reduced to the single intended navigation RED.
- Quality Graph focused governance/publisher/toolchain tests and graph
  validation pass with digest
  `a6d37d59715b355c8e717ad6f06a71f50f09806dbd6a57dcfcdea7a0f0a8dbdf`.
  New approved corrections cover exact post-review allowlist, 17 metadata
  bindings, offline publisher provenance/missing artifacts and generic
  untrusted-runner security. Latest evidence commits include `e97218e`,
  `533ebca` and `f075481`.
- Prepare upload-first planning reached independent
  `READY_FOR_OWNER_APPROVAL` at reviewed commit `80a399b391cbf2b4e52079dfae95dcbaf5cb65ac`,
  review commit `f897285b4a6e168df1fab04273b377b92331a015`.

## RED

- Latest exact recorded full verification on original upload remains
  `FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test` at
  `a55565dbd72e3112fd9f133dc3e4c77bfaf3ed94`; later focused work removed the
  checklist/auth/PDF/frontier classes, but no later exact-HEAD full
  `VERIFY_OK` exists.
- Navigation removal remains intended RED; object-card shared-shell/CSP fixture
  corrections are independently reviewed, but current card presentation and
  prepare/UI-shell/E2E contracts remain unresolved.
- Original-upload Gate 5 preflight is `CHANGES_REQUESTED` at review commit
  `8fa0ed7d1b13ef8ef131c59509df8d479a6d91c4`. Structural parser and private
  storage findings were corrected afterward. Totality/replay RED v6 remains
  Gate 3 `CHANGES_REQUESTED` at current head `7244be3`; missing assertions are
  exact per-outcome result tuples and per-position cleanup/lease/repository
  traces. Production factory/worker same-application identity also needs an
  approved observable composition receipt/seam.
- Quality Graph Gate 5 remains `CHANGES_REQUESTED`; there is still no actual
  valid `delivery/evidence` receipt, representative same-head positive/full
  parity, phase-B publisher proof or repository-wide GREEN.

## BLOCKED

- Prepare Gate 1 awaits explicit owner approval of the exact v15 hash batch;
  no replacement tests or production edits are authorized before it.
- CI bootstrap is blocked by the missing fresh Quality Graph Gate 5 and
  repository-wide GREEN. No gate was bypassed to put workflows into `main`.
- Compose restart proof remains blocked by the absent approved fictional login
  fixture; no unrelated or real credential was reused.

## PR and CI

Exactly one PR is open: draft PR #10, `codex/session-route-admission` at
`3ae214f75b898d171c68bb127dec10f17e03117a`, base `main`:
https://github.com/Antropophag/fmonitor-2/pull/10 . It has no reported checks
because `main` still has no workflow. It was not marked ready or merged.

Historical Quality Graph runs remain terminal failures, including
https://github.com/Antropophag/fmonitor-2/actions/runs/33780511678 and
https://github.com/Antropophag/fmonitor-2/actions/runs/33791635526. No CI URL
exists for the current Quality Graph or PR #10 exact heads.

## Next safe action

1. Obtain the owner's exact-hash approval for prepare v15.
2. Complete and re-review original-upload totality/replay RED; add the small
   Gate 1 composition receipt needed to prove worker/application identity.
3. Finish original-upload GREEN, exact full verification and fresh Gate 5.
4. Complete navigation/prepare/UI-shell/E2E predecessors, then return Quality
   Graph to actual receipt, repository-wide GREEN, fresh Gate 5 and bootstrap
   PR. Only after exact-SHA CI GREEN should PR #10 be made ready and merged.
