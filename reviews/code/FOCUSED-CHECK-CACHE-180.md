# Gate 5 review: FOCUSED-CHECK-CACHE-180

- Reviewer: independent Gate 5 agent `/root/issue180_gate5`, `gpt-5.6-sol / low`;
  authored none of the scope, specification, tests, implementation, Gate 3
  disposition or delivery evidence.
- Reviewed source: base `266e01f78d86493acbd531466afe67aec23f775c`
  plus prepared snapshot
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T151958Z-2dac049a0b/snapshot/source.patch`,
  patch SHA-256
  `2d8cc726f71c7c93ffd7ee4bd3790c879e13fc5c1a6e72020150264abdf68ad6`;
  exact candidate source
  `28aaf730cbf94620163f7d4c00cbab9492f3dddb8a374e315327db8b15768ac5`.
- Prepared reviewer package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T151958Z-2dac049a0b/package.json`;
  verification plan SHA-256
  `c80e209efac7b034cd0bb0ad56ea26e918d79c28c6ae5a20293a4f91d9b4059a`;
  context manifest SHA-256
  `15e4f1fac926e245377a2f5ad31f3138c2728d33bf8fd3928ed255a217e7c26c`;
  required-context SHA-256
  `b142f6b27f2301acb7e68030264a20299ee78c615e3fd06d043bd50f89573bad`.
- Contract: `specs/FOCUSED-CHECK-CACHE-180.md`, package-bound SHA-256
  `0dfcad61618b8d72fd1ce67e1993629cc4e1c07534abc7427198e508320e0235`.
- Planner: `CRITICAL`; required reviews `gate3`, `final`.
- Prior independent Gate 3: `reviews/tests/FOCUSED-CHECK-CACHE-180.md`,
  `APPROVED` for pre-implementation exact source
  `4caf7a84727bbb7ebb4cb1402106790e0b393d765d577fdcb606177e9fc0823f`.
- Exact-source GREEN records reviewed:
  `1789658342075696000-687352f9e0ce409a8921cffaf5fafdc5.json`
  (`focused_check_cache_180_test.py`) and
  `1789658351818144000-eacd8ffdb1424683b3a62adedf2d5d63.json`
  (`change_verification_001_test.py`), both bound to candidate source
  `28aaf730…`, executable source `d09ee45c…`, environment `a9002577…`.
- External evidence reviewed read-only under
  `/Users/antropophag/.local/share/fmonitor-2/issue-180/`: baseline A/B,
  corrected A/B, lock-change, cold `--no-cache`, and public-route logs.
  No full local `make test` or `make verify` was run.

## Findings

1. **HIGH — retained real-build evidence does not prove the required B/lock
   identity or stale-image rejection.** Locations:
   `docs/operations/issue-180-focused-check-cache-delivery.md:33-49`,
   `specs/FOCUSED-CHECK-CACHE-180.md:18-28`, and the external
   `corrected-b-build.log`, `lock-change-build.log`, `public-route-build.log`.
   The corrected-B log does prove the named dependency steps are `CACHED` and
   records image `sha256:2c0d7f54…`; the lock-change log proves Composer steps
   rerun and records image `sha256:4fc643fb…`. However, plain BuildKit progress
   does not retain the supplied `EXECUTABLE_SOURCE` or
   `COMPOSER_LOCK_SHA256` values and contains no post-build label inspection.
   The only retained public-route execution identifies A (`c6e9f20a…`), not B
   (`234220e1…`), and no retained witness attempts to use an A/mismatched image
   for B or a stale lock image and observes the existing fail-closed rejection.
   Consequently the delivery summary's claims that B has honest B identity and
   the lock label changed to `bbd601f3…` are not verifiable from the named
   evidence, while FCC180-01 explicitly requires the resulting label and
   executed source to identify B and FCC180-02 requires stale dependency images
   not to be accepted. The static GREEN only proves the relevant guards remain
   as strings; Gate 3 explicitly deferred the real identity/rejection witness to
   this evidence. **Correction:** without changing production, tests, spec, or
   shared caches, retain a bounded public-seam A→B witness that records B's
   expected digest, inspected source/lock labels, executed B source and image
   ID, plus a deliberate stale/mismatched A (and lock identity where applicable)
   attempt rejected by the existing guard. Append exact commands/results or an
   equivalent reconstructible journal to the external evidence, update the
   delivery summary with its location and digests, then prepare a fresh exact
   source and return for final review. If the existing images are still present,
   read-only inspection and bounded runs are sufficient; no cache pruning or
   repeated dependency build is requested.

## Conformance assessment

- The production diff is minimal and mechanically correct: only the two
  stage-local metadata `ARG` declarations move from before dependency work to
  immediately before their consuming `LABEL`; global build-argument
  declarations, canonical lockfile copies, dependency pins, profile stages and
  launcher identity checks remain unchanged.
- The recorded BuildKit decisions support the cache-semantics portion:
  baseline B reruns apt/PHP, Composer and uv; corrected B serves all named
  dependency steps from cache; the Composer-lock byte change leaves apt cached
  and reruns Composer; the `--no-cache` common-stage build completes with all
  dependency installers. Wall times are reported as bounded observations, not
  stable percentages.
- The structural test is sensitive to early/duplicate/missing stage-local
  metadata arguments, displaced canonical lockfile copies, removed labels/build
  arguments/inspection guard, and result-schema growth. The two exact-source
  focused records are GREEN and preserve start/end source identity.
- Scope remains limited to issue #180. No application code, dependency version,
  CI composition, FAST/Gate policy, harness architecture,
  `RUN_IN_PROFILE_RESULT` schema, stand data, cache ownership, foreign worktree
  or volume change appears in the reviewed snapshot.

## Verdict

`CHANGES_REQUESTED`

The Dockerfile correction and focused regressions are acceptable, but Gate 5
cannot approve FCC180-01/02 until the retained real evidence demonstrates the
normative B/lock identity and stale-image rejection instead of asserting them
only in the delivery summary.
