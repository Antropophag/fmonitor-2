# ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 v0.2 — independent Gate 1 rereview

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed repository HEAD: `db4d908ca43a31ae4c7d76b4e3d2cc37bf5a2d16`
- Reviewed owner specification SHA-256: `482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54`
- Parent original-upload SHA-256: `d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3`
- Scope: shared opaque opened-file owner and pure attribute policy; no implementation or tests reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification or OpenSpec artifacts.
This append-only rereview is the only authored artifact. No safe-log test,
filesystem fixture, database operation, native probe or rejected mechanism was
executed.

## Determination

The v0.2 candidate is ready for Gate 2 at the exact reviewed bytes. All three
blocking findings from v0.1 are closed, and the corrections do not add a product
decision or weaken the owner-approved safe-log policy.

The design remains materially separate from the rejected native-interposition
and interval-observer approaches. It uses an ordinary stable configured file,
a normal non-creating/non-truncating `r+b` open, the real retained handle's
`fstat`, a pure literal-input attribute policy, and an opaque owner that performs
append and close. No metadata substitution, permission transition, timing loop,
privilege manipulation, observer hook or production selector is introduced.

## Closure of v0.1 findings

### G1-SLO-01 — closed

The policy now requires `(mode & 07777) === 0600` in addition to regular-file
type. Setuid, setgid and sticky additions are therefore rejected rather than
silently passing the narrower access-bit mask. The exact literal matrix includes
`0104600`, `0102600` and `0101600` negatives without changing OS permissions.
The same special-plus-access-bit rule governs the final pathname observation and
the opened descriptor.

### G1-SLO-02 — closed

Explicit close is now one exact state machine. Before the sole native close
attempt, the owner becomes permanently unusable. Native success is cached and a
repeat is I/O-free success. False, warning or Throwable is normalized to the
fixed `RuntimeException('safe log unavailable')`, code `0`, previous `null`, and
cached; a repeat is I/O-free and repeats that fixed failure. `isClosed()` reports
usability state after either outcome and does not claim kernel-close success.
The destructor uses the same idempotent path while suppressing every error and
output. Acquisition failure after open attempts close once, never publishes the
handle and preserves the fixed acquisition error even if close also fails.

Stable public tests correctly do not claim to induce native-close failure. The
exact-SHA Gate 5 structural proof must inspect the close-failure/cache/no-repeat
branches, just as it must inspect the retained-handle metadata flow.

### G1-SLO-03 — closed

The owner and policy must be eagerly and idempotently available after the
existing direct `require_once` Runtime/FileStorage loading path, without the
general autoloader or a test-only include. Before invalid factory assertions,
Gate 2 must prove `class_exists(..., false)` for both new classes and complete a
valid owner/factory control. A missing class therefore cannot be translated by
the broad production factory catch and mistaken for the required invalid-file
denial.

The existing approved controlled wrong-owner regression is preserved without a
new protocol or extension. It remains an inherited stable control and is not
used to manufacture the rejected pathname/descriptor disagreement.

## Complete technical assessment

The public declarations are syntax-constructible. The owner has a private
constructor, no raw stream/FD accessor, arbitrary descriptor adoption,
metadata/opener callback, mutable public property, cloning or serialization
escape. Production metadata fields must come from native observations: current
EUID from `posix_geteuid`, expected identity from the final non-following
pathname observation, and actual mode/UID/device/inode from real `fstat` on the
new retained handle. The pure policy has no I/O and accepts exactly the fixed
field relation.

Acquisition preserves the existing canonical-path and Darwin system-alias rules,
rejects entry/parent aliases, absence, wrong type/owner/mode and malformed paths,
and does not create, replace, truncate, chmod, chown or repair the file. Every
failure maps to the fixed internal error after attempting closure of any opened
handle. The production factory maps that error to the existing redacted
configuration exception before database or private-root access.

Initial line count, locked seek-to-end append, single write, flush and
attempt-always unlock all use the same retained handle. The exact ordered JSON,
request correlation, per-owner sequence and prior-byte preservation contracts
remain unchanged. Partial/failed writes are not retried or repaired. A closed
owner cannot record, change request identity, reopen or be re-adopted.

The compatibility `AssignmentOrderOriginalFileSafeLog` is a facade over the
typed owner and is forbidden from retaining a path/raw handle or implementing a
second open/read/write/stat path. Existing worker and evidence-reader config
identities and resource ordering remain distinct; the reader remains read-only.

The independently fixed correlation values remain correct:

```text
SHA-256(request ...0001), first 12 lowercase hex = 11e594f48195
SHA-256(request ...0002), first 12 lowercase hex = e79acd97ac88
```

## Mandatory proof boundary

Gate 2 may now demonstrate the missing public owner/policy seam and exercise the
literal policy matrix, initially valid/invalid stable task-owned files, exact
append/correlation/sequence/close behavior, class availability, compatibility
facade and existing production factory regressions. Test setup must not add a
permission transition, interval hook, native substitution or privilege protocol.

Gate 3 must preserve the explicit evidence limit: stable-file black-box tests do
not prove that production used `fstat` instead of another pathname observation,
and do not prove native-FD closure or its failure branch.

Gate 5 must therefore independently verify on the exact implementation SHA that
the real retained handle supplies all descriptor fields to the policy, the same
handle alone serves count/append/close, no raw/adoption/reopen escape exists,
every post-open failure attempts one close, close results are cached without
repeat I/O, the compatibility facade has no alternate implementation, and the
production factory acquires the owner before database/private-root work. Missing
any part of this structural proof prohibits approval even with behavioral GREEN.

This proof split is explicit, bounded and sufficient under the repository's
mandatory test and code review gates. It does not evade either automatic
rejection.

## Verification

```text
$ git rev-parse HEAD
db4d908ca43a31ae4c7d76b4e3d2cc37bf5a2d16

$ shasum -a 256 specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ extracted PHP declarations with inherited safe-log interface stub | php -l
No syntax errors detected in Standard input code
```

## Exact reviewed hashes

```text
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4021c5ddf17b3ac6431b527208995fc2dec8f186490154337a370bc5d226929d  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
bd77708ddbafa3fc3bb6358003cf31b689a4b5dfa44806ce32d37fd0636f02f4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f74e510627e3651da58ddae606619806d4a376baad48c2f3d71d4942785a6091  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b9b3107b377d10ffdf44484dfec16b58c5aa0597124536565e6d64253c04cfe8  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
253aafaf2e7654f152d8af97653fcd67d63173acd0698815dcbe589489e2d011  docs/operations/safe-log-shared-owner-amendment-2026-09-06.md
e5eba523ea22514020338678fa206dc817bdc98c8042ba757527af644542feeb  docs/operations/safe-log-shared-owner-feasibility-review-2026-09-05.md
cb9784ebd2b62ed434fdb444f87308831baa9608817a398d9b90d21313561f00  docs/operations/safe-log-shared-owner-gate1-review-v01-2026-09-06.md
a41098f4ebd041d33d528f708369f1f416c6889b25e5bff342ead52dcb8fc64f  docs/operations/safe-log-native-verification-automatic-rejection-2026-09-05.md
7b25691025431a048faa49f6175b8c81c296d360aa72d8af9be0135b32dc0289  docs/operations/safe-log-observer-planning-automatic-rejection-2026-09-05.md
```

This review omits its own circular hash. Approval is limited to the exact v0.2
technical owner contract. It is not Gate 3, implementation approval, combined
original-command Gate 5, closure of `G5-SAFELOG-2`, full verification or launch
readiness.
