# Code review: ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation commit: `73c3c22999e379d7b250d170ce9a2a262c9ca815`
- Current HEAD during review: `30b98d5e46c8465691fd7f3a56a9e0076ae308b4` (review/evidence documentation only after implementation; six reviewed source files byte-identical)
- Approved specification SHA-256: `482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54`
- Approved test SHA-256: `5c0cbb4ff6527184c0effabe435556fb7e0c7fd42b5e31fd57a150841d17885b`
- Scope: opened safe-log owner, policy/path validation, compatibility facade and production factory binding only
- Verdict: **APPROVED**

The reviewer authored neither the approved test nor the implementation. No
production source, test or specification was edited during this review. This
review record is the only authored artifact.

## Findings

No blocking finding remains in the scoped owner implementation. The approved
behavioral test is GREEN, and the mandatory source inspection proves the parts
that stable-file black-box tests deliberately cannot establish.

### Retained descriptor and policy data flow

`AssignmentOrderOriginalOpenedSafeLog::open` first obtains a final non-following
pathname observation through `AssignmentOrderOriginalSafeLogPath::observe`.
That path component validates canonical identity, regular type, native current
EUID and exact special-plus-access mode `0600`, returning the canonical path and
the final pathname device/inode.

The owner then performs the global native `fopen($canonical, 'r+b')`. This mode
requires an existing file and does not create or truncate it. It immediately
calls the global native `fstat` on the returned handle. The actual
`mode`, `uid`, `dev` and `ino` fields from that `fstat` array are passed directly
to `AssignmentOrderOriginalSafeLogAttributePolicy::accepts`; expected device and
inode come from the final pathname observation, and effective UID is freshly
read through native `posix_geteuid`. Path metadata is not substituted for opened
descriptor attributes.

The policy requires regular-file type, `(mode & 07777) === 0600`, exact UID/EUID
and exact descriptor/path device and inode. Missing or non-integer native stat
fields fail before owner construction. This implements the approved setuid,
setgid and sticky-bit exclusions as well as ordinary permission/type/identity
checks.

Only after all descriptor checks and initial line counting succeed does the
private constructor receive the handle. No public constructor, raw stream/FD
accessor, arbitrary adoption method, opener callback, metadata provider or
mutable public handle exists. Clone is private and serialization/unserialization
throw the fixed error. Reflection is outside the application API.

### One owner for count, append and close

The private owner stores no pathname. Initial line count takes a shared lock,
rewinds and reads from the retained handle, then attempts unlock. Record uses the
same handle for exclusive lock, seek-to-end, one write, flush and attempt-always
unlock. There is no pathname reopen for counting or append.

`AssignmentOrderOriginalFileSafeLog` now contains only a typed owner. Its
constructor delegates acquisition; canonical, request selection and record
delegate to the owner; destruction delegates close while suppressing failure.
It has no file path, raw resource, stat, open, read, write or lock implementation.
The production factory binds `AssignmentOrderOriginalOpenedSafeLog` directly,
so the compatibility facade is not a second writer.

### Failure closure and cached lifetime

Every acquisition failure after open checks the local handle and calls the same
private close helper once before emitting a new fixed
`RuntimeException('safe log unavailable')` with code `0` and no previous
exception. A close failure does not publish the handle or alter the fixed
acquisition result.

Explicit close marks the owner closed, copies the handle locally and clears the
owner field before the sole native close attempt. Success is cached as closed
success. False, warning or Throwable is converted to closed failure and the same
fixed exception. A repeated close performs no native I/O: it returns after
success or repeats the cached fixed failure. Record and request changes reject a
closed owner without reopen. The destructor invokes this idempotent path and
suppresses all errors/output.

The close helper temporarily installs a PHP warning handler only around the
native `fclose`, records whether that real operation warned, restores the prior
handler in `finally`, and treats either non-true return or warning as failure.
It substitutes neither an operation nor metadata and exposes no test hook,
observer, timing boundary or fault selector. The final correction therefore
closes the warning-classification omission without recreating either rejected
mechanism.

### Factory and direct-import ordering

Runtime eagerly requires the compatibility facade; the facade requires the
opened owner; the owner requires the path/policy definitions. The circular
Runtime require is protected by `require_once` after the existing safe-log
interfaces have been declared. Consequently the existing direct Runtime and
FileStorage imports expose both new classes without the general autoloader.
The approved test checks `class_exists(..., false)` and completes a valid owner
control before invalid cases, so a missing dependency cannot masquerade as a
configuration denial.

`ProductionAssignmentOrderOriginalFactory::create` obtains the opened owner
directly from the unchanged `config.safeLogFile` before validating the private
root, validating the table prefix, loading database adapters or constructing any
database-dependent service. Acquisition Throwable maps to the existing exact
`AssignmentOrderOriginalProductionConfigurationUnavailable`. No new config,
environment/request/CLI/global selector or alternate factory path was added.

## Verification evidence

The reviewer independently ran the focused approved test against current source
bytes identical to the implementation commit:

```text
$ php tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SAFE_LOG_OWNER_001_OK
```

Additional checks:

```text
$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ php -l <each of six affected production files>
PASS

$ git diff --check db4d908c 73c3c229 -- <six affected source files>
PASS (no output)

$ git diff --exit-code 73c3c229 HEAD -- <six affected source files>
PASS (no output)
```

Primary GREEN evidence hashes match the recorded archive:

```text
bf7d04426524f8e2b90357e8294df87e7db2dc9a5ee1d96383e4ab9eeb942a7c  evidence.json
d6a7101c4b2611eec3b6311f2082f624ec428d51617d19d48ae570d8c5e90a64  close-warning-evidence.json
```

The implementation record also reports final-SHA GREEN for the unchanged
production-boundary, worker-transport and upload-remaining-contract scripts.
Those broader scripts support regression safety but do not replace the source
proof above.

## Exact reviewed hashes

```text
baaad874c32b8409f59bd996474832a5ad27a2af0e3769c0f0684aa890c0a1f3  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileSafeLog.php
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
688703c656aa9d7ebb808e682c1322000c696f3fd35b47dbc7b0ba893b5dc373  app/AssignmentOrderOriginal/AssignmentOrderOriginalOpenedSafeLog.php
569c792d7d48ba7842281881362693d0b3bf40e83cf66a045fa925705ba1279f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7008bc0cf2c5bbe81865c4018b0259e027e580246c84ebf54951cd53108e9b8b  app/AssignmentOrderOriginal/AssignmentOrderOriginalSafeLogAttributePolicy.php
ae4d62bf6d14cc07f6de88ea236b44c9465c2e10e326e5b3cc1b270d5cf11f86  app/AssignmentOrderOriginal/AssignmentOrderOriginalSafeLogPath.php
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
5c0cbb4ff6527184c0effabe435556fb7e0c7fd42b5e31fd57a150841d17885b  tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php
53689704e5b21faf4907f975ae0dab350a863a0defca7aeb4700a5c32b9d13f0  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001-v1.md
a6f4ceb0b3e943d23bdcbc9af101dc08940ad53bb57f889659ae4a398899acf1  docs/operations/safe-log-shared-owner-green-v1-2026-09-06.md
f2af8fc92141a10bd8f0cc82e0c5bc6dbcc0b4949937e55c48970846a80cd675  docs/operations/original-safe-log-best-effort-audit-2026-09-06.md
```

## Scope boundary

This verdict approves only the shared opened-file owner at exact implementation
SHA `73c3c22999e379d7b250d170ce9a2a262c9ca815`. The separately confirmed command
best-effort gap remains open: a throwing diagnostic observer can still replace a
selected command Result or interrupt required sequencing. This owner review does
not approve that command behavior, close `G5-SAFELOG-2` end-to-end, supply the
combined original-command Gate 5, establish full `VERIFY_OK`, or claim launch
readiness.

This review record omits its own circular hash.
