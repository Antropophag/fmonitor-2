# Code review: PILOT-SESSION-STORAGE-001 v10 payload handoff — GREEN v1

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/session_payload_code_review`
- Independence: reviewer did not author or edit the specification, approved test, RED evidence, or reviewed production implementation
- Reviewed commit: `4fc21a47781251851a4694a816c297c4dae16871`
- Parent commit: `b49fecc762728d195b1b8363be3472c8ef67d75b`
- Production diff: `git diff b49fecc762728d195b1b8363be3472c8ef67d75b..4fc21a47781251851a4694a816c297c4dae16871 -- app/IdentityAccess/FilesystemPilotSessionStorage.php app/IdentityAccess/PilotSessionStorageTypes.php`
- Specification: `PILOT-SESSION-STORAGE-001` v10, approved package recorded in `docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md`
- Approved Gate 3 record: `reviews/tests/PILOT-SESSION-STORAGE-001-payload-handoff-v1.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

### CR-1 — `ownerStarted` does not enforce the approved session-ID invariant

Severity: **MEDIUM / blocking specification conformance**.

The exact public result surface in specification section 8 requires
`PilotSessionOperationResult::ownerStarted(string $currentSessionId, string
$sessionPayload)` to validate the ID before returning `OK`. At the reviewed
commit, the method passes every string directly to the private constructor.
Consequently the public owner-only creation seam can represent an impossible
successful start such as ID `bad`, contrary to the DTO invariant that a
successful start ID is owner-generated or the validated supplied ID accepted
by `start`.

The real `FilesystemPilotSessionStorage` call sites currently pass either a
generated 64-hex ID or an ID already accepted by `validId`, so this does not
create an immediate HTTP exploit. It is nevertheless an invalid state admitted
by the exact public seam and must be fixed before Gate 5 approval. Apply the
same normative session-ID grammar validation at `ownerStarted`; verify that an
invalid factory argument is rejected without changing the approved payload
expectations. If a production-test expectation is added or changed, restart at
Gate 2 and obtain fresh independent Gate 3 approval as required by the delivery
process.

## Conforming behavior

- `start(null)` passes empty bytes to `ownerStarted`, so a new anonymous session
  has non-null payload `''`.
- Successful `start(valid committed id)` passes the exact string returned by
  the owner's bounded read; no decode, re-encode, second owner, or bypass read
  was added.
- `ownerWriteCommitted`, `ownerRegenerated`, `ownerDestroyed`, `ownerClosed`,
  `ownerNotFound`, `ownerInvalid`, and `ownerUnavailable` all construct results
  with null payload. Thus payload is non-null only for successful start.
- `PAYLOAD_INVALID = 'payload_invalid'` is present in the closed unavailable
  enum. Codec rejection and HTTP mapping remain later slices and are not
  falsely claimed by this GREEN.
- The payload is stored only in the immutable DTO and exposed by
  `sessionPayload()`. The diff adds no log, observer, filesystem-event,
  correlation, inspection, response, or exception interpolation and therefore
  adds no payload leakage path.
- The implementation change is limited to the concrete owner and result DTO.
  No HTTP consumer or architecture baseline was modified.

The reviewed positive test is sensitive to a missing accessor and null, empty,
altered, or re-encoded committed bytes. As explicitly recorded by Gate 3, it
does not independently ratchet empty-new, null-on-other-outcomes, malformed
codec behavior, HTTP mapping, or the enum member. Those invariants were checked
against every production factory/call site in this review; their executable
coverage remains deferred to their approved later slices.

## Standards and maintainability

No new documented-standard violation, security leak, boundary violation, or
blocking Fowler smell was found beyond CR-1. The dense one-line form of these
classes is difficult to review, but it is pre-existing local form rather than a
new defect introduced by this two-file minimal diff.

## Architecture ratchet classification

`make architecture-check` exits nonzero with exactly the known predecessor set
of **13** `session_storage_ownership` consumer fingerprints:

- 2 in `app/PilotHttp/PilotE2ECoordinator.php`;
- 6 in `rapid-pilot/LocalAuth.php`;
- 5 in `rapid-pilot/UserAccessView.php`.

The reviewed diff adds, removes, or changes none of those files or fingerprints
and does not change the ratchet/baseline. This is a known predecessor RED for
the subsequent consumer-removal slice, not a new finding caused by commit
`4fc21a4`; it does not override CR-1 or make this Gate 5 approval eligible.

## Verification evidence

Commands executed against reviewed `HEAD = 4fc21a4`:

```text
git diff --check b49fecc..4fc21a4
```

Result: exit `0`.

```text
php -l app/IdentityAccess/PilotSessionStorageTypes.php
php -l app/IdentityAccess/FilesystemPilotSessionStorage.php
php tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
```

Result: all exit `0`; focused output:

```text
PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff
```

All 15 `tests/InstallationProcess/*_test.php` files referring to
`PILOT-SESSION-STORAGE-001` or `PilotSessionStorage` were run individually.
Result: **15/15 PASS**.

```text
make architecture-check
```

Result: exit `2`, only the exact 13 predecessor fingerprints classified above.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
038b86052ae63063c393f0ced6f228a790f4fc28deac5ff5383701e6ece88ae7  reviews/tests/PILOT-SESSION-STORAGE-001-payload-handoff-v1.md
288ba764ab60fd7f0abd767c6181c56efcb840a622c668f97d752a0bcc9c41b4  tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
5176afef22ef5aed401df0374705dce1f334f65ba96d805149f12a4fe1d63496  docs/operations/pilot-session-storage-payload-red-evidence-2026-09-03.md
7f29a289c6b0558e8077afb44e6f4984b0573501e160eb354dbe2649b47a8f46  app/IdentityAccess/FilesystemPilotSessionStorage.php
e53e34e865e1481a494f8b746e75bfcdd5f2d343d7eaaee4b3c7dd4964200507  app/IdentityAccess/PilotSessionStorageTypes.php
```

Gate 5 is **CHANGES_REQUESTED** until CR-1 is corrected and independently
re-reviewed at a new immutable commit.
