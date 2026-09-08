# Independent Gate 3 test rereview v4 — PILOT-SESSION-STORAGE-001 exact-owner impersonation

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_ratchet_impersonation_gate3`
- Test author: not this reviewer
- Reviewed commit: `779d45b066fd6488f635e14bf925657fe4aa388c`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` v9,
  sections 7–8, OpenSpec task 3.3, and the immutable Gate 5 return record
  `reviews/code/PILOT-SESSION-STORAGE-001-architecture-ratchet-v1.md`
- Public seam: `tools.architecture.check.collect()` through the repository
  architecture unittest harness
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, tests or production
checker. This append-only review record is the reviewer's only change.

## Review

The new negative fixture is traceable to the exact Gate 5 defect and is a
deterministic, isolated RED at the public collector seam. It creates two
production-shaped PHP files below `rapid-pilot/` whose basenames impersonate
the operation/event owner and inspector. The source contains exactly three
independent factory invocations: one `PilotSessionOperationResult` factory,
one `PilotSessionFilesystemEvent` factory and one
`PilotSessionInspectionResult` factory. Therefore the independently derived
expected `session_storage_ownership` cardinality is exactly three.

Setup and cleanup are sound. `TemporaryDirectory(dir=rapid-pilot)` fails
separately on setup errors, both fixture writes occur before collection, and
the context manager removes the directory before the cardinality assertion.
The reproduction left no temporary child behind. The observed zero findings
come from the current basename-only allowlist, not from failed discovery,
fixture setup or cleanup.

One blocking oracle inconsistency prevents approval.

### T1 — the existing positive fixture still grants ownership by basename in a non-owner path

`test_session_storage_ownership_allows_exact_internal_factory_owners` creates
its two alleged allowed owners at paths shaped as:

```text
app/IdentityAccess/<random-temporary-directory>/FilesystemPilotSessionStorage.php
app/IdentityAccess/<random-temporary-directory>/PilotSessionStorageInspector.php
```

Those are not either exact owner required by the contract and Gate 5 finding:

```text
app/IdentityAccess/FilesystemPilotSessionStorage.php
app/IdentityAccess/PilotSessionStorageInspector.php
```

Consequently a correct minimal implementation that compares normalized exact
repository paths will make the new negative test GREEN but must make the old
positive test RED. Keeping that positive test GREEN requires accepting an
arbitrary nested path with an approved basename, which preserves the same
impersonation authority under another directory. The reviewed suite therefore
cannot simultaneously enforce the exact-owner rule and retain all approved
expectations.

Return to Gate 2. Correct the positive oracle so its zero-finding matrix is
presented to the collector as the two exact canonical owner paths without
modifying those production files, while retaining the new outside-owner
negative fixture. Then capture a fresh RED for the combined exact-owner matrix
and request a new independent Gate 3 review. Do not implement a temporary-path
exception in the production checker.

## RED reproduction

At exact reviewed SHA `779d45b066fd6488f635e14bf925657fe4aa388c`:

```text
python3 -m unittest tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_owner_basename_impersonation
F
AssertionError: 3 != 0
Ran 1 test in 0.069s
FAILED (failures=1)
exit=1
```

Temporary-directory inventory before and after the command was empty.

## Exact reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
5ecfc8f947b43cdae0b3625a45d564940605077d2a57d332c3e95412db340a89  tools/architecture/tests/test_debt_fingerprint.py
3262cbbfc4a39bdcf268326df4e390c020f45be269c293c123764a2fad366ce6  docs/operations/pilot-session-storage-architecture-ratchet-impersonation-red.md
81b3ebc2d11be03c6be221cec54ad7cc43e1c0335025b41cc0b8e5207c4e68c4  tools/architecture/check.py
```

## Gate consequence

Gate 3 is **CHANGES_REQUESTED**. No production GREEN is authorized from this
review. The prior Gate 5 record remains immutable and unresolved.
