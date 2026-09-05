# Assignment-order original production safe-log descriptor-integrity audit

- Date: `2026-09-05`
- Investigator: separately tasked read-only agent `/root/safe_log_contract_audit`
- Scope: bounded investigation of remaining `G5-SAFELOG-2`; no production or test edits
- Repository HEAD observed: `43472a41645a6cffeadda2c20d603aee785dc223`
- Status: **FINDING CONFIRMED; TECHNICAL GATE 1 CLARIFICATION REQUIRED; NO NEW OWNER DECISION REQUIRED**

## Conclusion

`G5-SAFELOG-2` remains open. `AssignmentOrderOriginalFileSafeLog` validates
regular-file type, effective-user ownership and exact `0600` on a pathname
observation, then takes another pathname observation, opens with `ab`, and
checks only device/inode equality between that second observation and `fstat`.
A replacement after the attribute-bearing observation and before the second
observation can therefore make an unsafe replacement inode both `$before` and
`$opened`; the opened descriptor's type, owner and mode are never checked.

The owner decision is already sufficient for product behavior. It approves a
mandatory pre-existing canonical non-symlink regular effective-user-owned exact
`0600` log, no create/repair, validation before database/private storage, real
append diagnostics and a fixed redacted construction error. Those properties
must hold for the file descriptor that production retains and writes; accepting
an opened descriptor with different security attributes would not satisfy the
approved outcome. No exception, alternative behavior, privilege policy or new
user-visible result is being introduced, so this is not a new owner decision.

The executable specification should nevertheless receive a narrow technical
Gate 1 clarification before Gate 2. The current phrase "binds ... to the
validated canonical file identity" states the intended result but does not say
explicitly which attributes must be rechecked on the opened descriptor or how
failure closes the handle. Adding that exact acceptance language removes the
test-author ambiguity recorded in the prior blocked audit without weakening or
expanding the owner decision.

## Exact minimal contract proposal

Append the following paragraph immediately after the production safe-log
construction paragraph in `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md`:

> The append descriptor actually retained by
> `AssignmentOrderOriginalFileSafeLog` MUST be revalidated with `fstat` before
> any database/private-storage access or diagnostic write. Its opened identity
> MUST match the final non-following pathname observation by exact device and
> inode, and its `fstat` type, effective-user owner and permission bits MUST be,
> respectively, regular file, current effective UID and exact `0600`. Any open,
> `fstat`, identity or attribute mismatch MUST close the opened descriptor when
> one exists and fail construction through the existing fixed redacted
> `AssignmentOrderOriginalProductionConfigurationUnavailable` boundary. It
> MUST NOT create, repair, truncate, append to or otherwise change the file on
> this failure.

This is a technical clarification of the already approved invariant. It needs
fresh independent Gate 1 review, then a demonstrated RED, fresh independent
Gate 3, minimal GREEN and fresh Gate 5. It does not require owner
reconfirmation unless review proposes a different policy or externally visible
outcome.

## Safe deterministic regression observability

The production public seam remains
`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)`. No
production observer, environment selector, privilege change, external target,
real document or probabilistic replacement loop is needed.

A task-owned child process can load a test-only native interposer, following the
repository's existing cross-platform `lstat` interposition pattern. For the one
exact task-owned safe-log path, the interposer returns a controlled pathname
metadata snapshot whose device/inode match the real file but whose type/UID/mode
are the approved regular/current-EUID/`0600` values, while the real opened
descriptor retains a deliberately non-`0600` synthetic file's attributes.
All unrelated paths and calls pass through unchanged. A separate unchanged
control run proves the loader and public factory work normally.

This creates a deterministic pathname/descriptor disagreement at the public
factory seam. On current production bytes, device/inode equality passes and
construction is accepted, giving the intended RED. After the minimal fix,
descriptor `fstat` mode (and independently type/UID where the platform-owned
fixture can establish them without privilege changes) causes the fixed redacted
configuration failure, with zero database calls, no private-root touch, no log
append and no descriptor leak. Exact setup assertions must fail as
`SETUP_FAILURE`, not count as RED. The test helper is verification-only and
cannot be selected by production configuration or runtime.

Mode is the portable mandatory sensitivity case because it requires no
privilege change. Type and effective UID remain production assertions and
should be covered by deterministic synthetic cases only where the test process
can establish them safely; absence of such a fixture does not permit removing
those opened-descriptor checks. The regression should also pin the fixed
exception shape at the factory boundary and inspect the controlled file bytes
and metadata before and after.

This approach resolves the earlier observability impasse: it does not need a
pause in the unobservable interval and it does not add a production
coordination seam. It makes the omitted `fstat` attribute validation itself
observable while exercising the real production factory and logger.

## Minimal implementation consequence (not implemented here)

After `fopen`, validate `$opened['mode'] & 0170000 === 0100000`, exact
`$opened['mode'] & 0777 === 0600`, and exact `$opened['uid'] === effective UID`,
in addition to the existing device/inode equality. On any mismatch, close and
throw the logger's fixed internal unavailable error; the production factory
continues translating it to the already specified fixed
`AssignmentOrderOriginalProductionConfigurationUnavailable`. Sequence counting
must occur only after all descriptor checks succeed. No broader logger or
factory seam is justified by this finding.

## Exact evidence identities observed

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
d9178b78bb08463e2e0ce1c89587ca9a413509125a40d8f5f44f93ece7088f8c  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-v55.md
98f99e8cc7201725225e5c4d2bc41c61905e606bbb776287782f66fee1e4c693  docs/operations/assignment-order-original-production-safe-log-descriptor-race-gate1-gap-2026-09-05.md
3da95e342c0f49d96cfd2d91aca12ba6eecdba3fb75777333fab04e0b1ae5ec6  docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md
375f7b21d0a8035bb6e5d2284b914386d5352dc4d674f0e56eb6ba0bae8b4f99  docs/operations/autonomous-restart-handoff-2026-09-05-1309Z.md
4e26ef38bcb9672464a52b59d71da065f39a9af74386ea89a7b4ffe40e632abb  docs/operations/assignment-order-original-production-safe-log-parent-symlink-gate4-green-2026-09-05.md
eda63c2ca641a0eb141a6198e63137c1a2419233a53c48797b2be8886c60be19  tests/Support/css_lstat_swap_preload.c
c0b5b86646e654f52907c391309f879a0364af91f7f8da70c75a21e1b3f55b9a  tests/Support/css_lstat_swap_preload_probe.php
```

No production or test file was changed, no test helper was created, no
privileged action was attempted, no external system was accessed and no Gate
verdict was issued by this audit.
