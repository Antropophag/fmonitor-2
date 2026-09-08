# ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001 — independent Gate 1 review v01

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed repository HEAD: `3f5aa18cedc9bf71e43a16ed29a39da9ad7c1ca1`
- Candidate SHA-256: `4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332`
- Parent original-upload SHA-256: `de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52`
- Scope: technical command acquisition, cleanup, observer, lease and response-boundary lifecycle
- Verdict: **APPROVED**

The reviewer authored none of the candidate, parent or OpenSpec artifacts. No
lifecycle test or implementation was reviewed or executed. This append-only
review is the only authored artifact.

## Determination

The candidate is ready for Gate 2 at the exact reviewed bytes. It resolves the
two independent source audits with one public application lifecycle, without
changing product policy, authorization, immutable evidence, selection, routes or
maintenance behavior.

The parent specification explicitly makes COMMAND-LIFECYCLE-001 normative for
the affected sections, and all four OpenSpec artifacts carry the same ownership,
replay, observer and response-loss boundary. No competing active requirement was
found. The former empty-fingerprint availability probe is removed coherently as
an extra implementation query; the real post-stream lookup and its independently
approved unavailable behavior remain mandatory.

## Constructibility assessment

### One bounded owner and exact failure families

Application ownership begins with the supplied stream at invocation admission.
Read begins only after shape, authorization, terminal request, composition,
validated clock, confirmation/date and correction-preflight gates. Every
stream/stage close is attempted at most once, and no such operation occurs after
commit.

The matrix fixes Result classification by the failing public port rather than a
concrete fault-injector type:

- read failure, Throwable or malformed status/payload is retryable
  `FAILED/STREAM_FAILURE`;
- begin/write/completed-bytes/inspector/finalize failure or Throwable is
  retryable `FAILED/STORAGE_FAILURE`;
- accepted-candidate stage-close failure is storage failure and stream-close
  failure is stream failure;
- cleanup failures cannot replace an already selected rejection, conflict,
  replay or failure.

`BYTES` must contain 1–65536 bytes and `EOF|FAILED` must contain none, preventing
empty progress loops and discarded payload. The 20 MiB received-byte contract,
MIME/PDF results and existing business reasons remain unchanged.

### Finalize, lease and resource ownership

Finalize outcome, lease and content are each captured once. Success requires
`OK|ALREADY_PRESENT_VERIFIED`, a non-null status-OK lease and one content object
with valid opaque identity, exact acquired digest and exact positive received
size. Non-success carrying a lease and success carrying malformed lease/content
are storage failures, but any lease actually returned is still owned for one
release attempt. A Throwable before a lease is returned correctly remains the
adapter's resource responsibility.

For a valid candidate, stage then stream close once while the lease remains held.
Only both successes permit commit. Exceptional close unwind does not repeat the
failed close: stage-close failure proceeds to abort, remaining stream close and
release; stream-close failure after stage close proceeds to abort and release.
Finalized content is never deleted by the command. Finalize/validation failure
uses ordered cleanup then releases any returned lease with phase `rolled_back`.

Every post-lease repository/getter/callback failure has a phase-aware one-release
path. No outer catch may skip release, retry allocation/commit or repeat closed
resources. This closes the concrete leak and double-close gaps in the acquisition
audit.

### Storage and lifecycle observations

The causal table independently fixes every command storage event:
`STAGE_BEGIN` before begin, successful `STAGE_WRITE`, `STAGE_DONE` after EOF,
`FINALIZE_BEGIN/DONE`, `ABORT_BEGIN/DONE`, and `STAGE_CLOSE` before the sole close.
BEGIN may exist without a primitive/DONE when an acquisition callback fails.

Cleanup callback failure is an explicit narrower rule: ABORT/STAGE_CLOSE
callbacks cannot prevent their cleanup primitives or later event attempts. On an
accepted candidate, STAGE_CLOSE callback failure still invokes close once,
cancels acceptance as storage failure and unwinds remaining resources. On an
already selected non-accepted result, cleanup callback failure neither replaces
the result nor fabricates a primitive diagnostic. These rules resolve the
otherwise apparent BEGIN-callback ambiguity.

Lifecycle `AFTER_REQUEST_MISS_BEFORE_STREAM` occurs once immediately before
stage acquisition; fingerprint miss and post-finalize phases retain their exact
causal points. `AFTER_COMMIT_BEFORE_RETURN` is distinct from delivery and occurs
only for this invocation's known/fresh-recovered accepted commit, after release
attempt and before delivery. Replay emits neither post-commit lifecycle nor
delivery.

### Replay and response boundaries

Terminal-request replay now explicitly owns one close of the supplied unread
stream. Close Throwable cannot replace the stored outcome and produces the
existing isolated diagnostic. It performs no composition/clock/ID/read/storage/
lifecycle/commit/audit/delivery work. This resolves the earlier two-line
transcript ambiguity without changing replay product semantics.

Post-stream accepted-fingerprint replay uses the same attempt-always abort,
stage-close and stream-close owner as rejection. Each primitive and event occurs
at most once; failure cannot skip later cleanup or replace replay. No IDs,
finalize, lease, commit, audit or delivery follow.

A generic commit Throwable is correctly treated as unknown rather than assumed
rollback: one recovery lookup selects stored accepted, persistence failure or
outcome unknown. The candidate explicitly preserves actual fresh-connection and
stored-data validation as a mandatory separate data-integrity dependency; pure
application tests cannot claim that evidence.

After a known or freshly proven accepted commit, release precedes post-commit
lifecycle and delivery. Either callback Throwable throws the fixed redacted
`AssignmentOrderOriginalResponseDeliveryLost`, returns no false domain Result,
and never repeats resource/commit/observer operations. Durable facts remain for
ordinary same-request replay. Production observers remain inert and no runtime
fault selector is introduced.

## Evidence requirements confirmed

The mandatory matrix is sufficient and independently observable at the public
application seam. It covers successful acceptance, all primitive and callback
failures, malformed stream/finalize values, cleanup combinations, both replay
forms, lifecycle failures before/after finalize, typed and thrown commit paths,
conflict rereads, response loss and subsequent replay. Each row fixes Result or
exception, call counts, ordering, audit/evidence absence or preservation, and
lease release.

Existing tests that asserted the removed empty-fingerprint probe must receive a
separately reviewed exact expectation patch with demonstrated failure evidence;
the lifecycle RED cannot silently edit them. Real worker/storage regressions
remain necessary for physical persistence, lease and response-loss claims.

No new owner approval is required. Actual data-integrity/fresh-connection,
declaration and storage-maintenance corrections remain separate combined-review
dependencies and are not waived by this approval.

## Verification

```text
$ git rev-parse HEAD
3f5aa18cedc9bf71e43a16ed29a39da9ad7c1ca1

$ shasum -a 256 specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 3f5aa18^ 3f5aa18
PASS (no output)
```

## Exact reviewed hashes

```text
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
467e33522cec71b7e92ebbce8ea9d4e400244f27cf59547f516f83f6fec7dca3  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
db302a5d13b7cd714c590c7d7ce381c5073bb8d9dc27bb8dd5ddf6ea64f3f0fd  openspec/changes/replace-pilot-registration-with-original-upload/design.md
4eded82b867f54153c2a033697c16ad8e60cbbd225fe947feab304ab7ea2a6ef  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
f48d9c022c2cee4f832516f4042b3247ae2070e9a7dccebd9817c30276600539  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c983d91aef79a0ccaaa02b681589da5a271a8a885159c10714bde1f7f082d3af  docs/operations/original-command-observer-contract-audit-2026-09-06.md
aa2c1e196d39b988519eb9302d271446ae0dae695046558f4bb8c9de39a1ab8c  docs/operations/original-command-acquisition-contract-audit-2026-09-06.md
```

This review omits its own circular hash. It authorizes Gate 2 only for the exact
lifecycle candidate. It is not test approval, implementation approval, combined
command Gate 5, full verification, deployment or launch readiness.
