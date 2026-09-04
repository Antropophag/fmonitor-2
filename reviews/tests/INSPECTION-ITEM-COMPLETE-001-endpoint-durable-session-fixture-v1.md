# Independent Gate 3 review — durable endpoint session fixture v1

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/checklist_session_fixture_gate3`
- Test/correction author: not this reviewer
- Reviewed commit: `2ae633c4db26608a7b6d05a82c5de933f7befe81`
- Base: `181f8e7dc1a9ab3da1b9c592c47360f69971e809`
- Public seam: raw HTTP through `public/router.php`, the real checklist route,
  and the configured durable session owner
- Verdict: **CHANGES_REQUESTED**

The reviewer did not edit the reviewed test, specification, evidence, or
production code. This append-only review record is the reviewer's only change.

## Confirmed progression

The correction honestly clears the setup failure. On the exact base, the RED
wrapper reproduced:

```text
PRIMARY: TestFailure: Unassigned engineer obtains checklist CSRF page.
Expected: 200
Actual: 503
RED_ASSERTION: expected failing behavior observed
```

On exact `2ae633c`, the same command instead reached the next existing
admission mismatch:

```text
PRIMARY: TestFailure: Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

PHP syntax passed. In both runs the guarded cleanup preserved the primary
diagnostic. Independent post-run probes found no `t_iea_%` database and no
task-owned `.test-artifacts/iea-*` root. No production or foreign state was
used as a cleanup target.

The fixture's configured paths are task-owned and unique: it creates
`.test-artifacts/iea-<token>/sessions` as `FMONITOR_SESSION_STATE_ROOT` and
passes `FMONITOR_SESSION_INSTANCE=iea-<token>`. Under the approved session
contract, the exact managed instance is therefore
`<owned-root>/sessions/sessions/iea-<token>`.

## Findings

### DSF-01 — session mutation allowance is broader than the owned instance

`ieaSnapshot()` classifies every regular file whose path begins with
`<owned-root>/sessions/` as allowed session mutation. It does not require the
file to be inside the exact managed instance
`<owned-root>/sessions/sessions/iea-<token>`, nor does it require the session
contract's exact filename grammar.

Consequently, an implementation that creates or mutates an arbitrary regular
file directly under `<owned-root>/sessions`, under another instance, or under
another invented descendant can still satisfy the reviewed assertions. The
test therefore does not prove its evidence claim that durable state is created
"only in the owned session subtree" narrowly enough for the approved exact
root/instance contract.

### DSF-02 — non-file and symlink entries are invisible

The iterator records an entry only inside `if ($f->isFile())`. A symlink to a
directory, a dangling symlink, an unexpected directory, FIFO, socket, or other
non-regular entry is omitted from both `artifacts` and `sessions`. The
`$f->isLink()` assertion is reached only for entries already classified as
files, so it does not establish that the session subtree contains no symlinks.

An unexpected invisible entry may also be removed successfully by the generic
recursive cleanup, so the final absence probe does not close this observation
gap. The evidence statement that the subtree contains only regular
non-symlink files is therefore not executable.

## Required changes

1. Derive the exact managed instance path from the configured state root and
   instance, then reject every mutation outside that instance while preserving
   exact zero mutation for the four domain tables and all non-session
   artifacts.
2. Enumerate every filesystem entry in the state/session tree with `lstat`-like
   semantics. Fail on every symlink and unexpected non-regular entry, including
   symlinked directories and dangling links. Allow only the contract-required
   real directories and current-euid regular session files with the exact
   managed filename grammar (and preferably the required modes).
3. Reproduce the base 503 and corrected next-stage 403/422 RED after the oracle
   is strengthened, update append-only evidence and hashes, and request a fresh
   independent Gate 3 review.

## Commands and exact hashes

```text
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
git diff --check 181f8e7..2ae633c

054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
e53ff400bcf25a4ebc53292ed376841a81e8bc9923e74625ccdb7766d69d7e38  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
780e53ed85a35c02e7f028692e1d682b94cebcee7a5fe19d0fd658d199cc55f9  docs/operations/inspection-item-endpoint-durable-session-fixture-red-correction-2026-09-04.md
```

The setup correction is directionally correct and the intended next RED is
reachable, but DSF-01 and DSF-02 leave the new mutation-safety claims
insufficiently sensitive. Gate 4 must not rely on this exact fixture until a
fresh Gate 3 review approves the strengthened oracle.
