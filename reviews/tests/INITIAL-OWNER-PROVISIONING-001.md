# Independent Gate 3 review — INITIAL-OWNER-PROVISIONING-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Specification/test author: separately tasked agent `/root/runtime_plan`
- Verdict: **APPROVED (revised full matrix)**

## Exact reviewed artifacts

```text
52f78825d52a19fa30e9ef528be453632f83f5b577d4f19733459929f5bf736a  specs/INITIAL-OWNER-PROVISIONING-001.md
7c22dc7d30f441865dcf26065e68e3141a0226053a511f26bed2875560eaa866  openspec/changes/provision-initial-owner-admin/specs/operations/initial-owner-provisioning/spec.md
e084133a84c7a02521a1aac41fd5d4ca2835ba4f7d1634977c0ecd7a0a866ae2  tests/Runtime/initial_owner_provisioning_001_test.php
```

## Review

The executable uses the explicit CLI over a clean canonical-v22 database under a
DML-only principal. It requires mixed-case/trimmed corporate email normalization,
exactly one active user whose full name is the normalized email, an Argon2id hash
that verifies the injected password, exact bootstrap `user` and
`superadministrator` memberships with null actor, and the independently frozen
complete role/permission catalogue hash.

Exact replay and different-password conflict compare the full identity-family
snapshot. A separate invited-user fixture proves existing state is not promoted or
repaired. Missing/bad CLI arguments run with otherwise valid configuration, and
missing DB password is tested separately; all invalid outcomes must be stable,
secret-free and leave the full owner identity state unchanged.

Random databases/users and unconditional teardown isolate the test. Successful use
with SELECT/INSERT/UPDATE/DELETE privileges proves the reviewed path does not require
DDL.

## Demonstrated RED

```text
$ php tests/Runtime/initial_owner_provisioning_001_test.php

INTENTIONAL_RED: explicit initial-owner CLI exists
Expected: true
Actual: false
exit 255
```

The RED is the absent public seam, before database fixture setup. Syntax and diff
checks pass.

## Revised matrix and verdict

The revised reviewed test additionally covers partial role state with zero users,
missing schema without repair, an independently held provisioning lock, injected
late-grant failure with complete rollback and successful retry, and two concurrent
real CLI processes with bounded reaping. Its full nine-table identity snapshots
include invitations and all role/auth/status event families. The specification now
defines the exact replay user fields and empty auxiliary history.

**APPROVED.** Gate 4 may implement the full reviewed clean/replay/conflict/schema/
lock/rollback/concurrency matrix through the DML-only IdentityAccess owner and CLI.

---

## Gate 3 review — explicit local continuation (#185) — 2026-09-18

- Reviewer: independently tasked agent `/root/issue185_gate3`; author of none of
  the reviewed specification, OpenSpec artifacts, tests, production code, or RED
  evidence.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T224653Z-02b68b3817/package.json`
  (`54c5d0a19a5b3f7a5eb62127cd686e184bc4463a14ec60b826276410990a6c80`).
- Reviewed source: base `e419c2b5d1e4d6c9e46edda1adf7abac6883447a`,
  candidate source `f259eb45ac86098a846e2215a79473a02b10d449d346111307f009c641b31f27`,
  reconstructible snapshot `snapshot/source.patch`
  (`ee83a686c07ca1329f3e6695aaa67abe4af1cc2dec8ab5c5459d132e5f7e518b`).
- Contract: `specs/INITIAL-OWNER-PROVISIONING-001.md`
  (`6f59acf9dadd30d2ddac31fb38aaa21917fb0a1af0db915ea9f667463c20876d`).
- Planner decision: `CRITICAL`; required reviews `gate3`, `final`; required
  categories `governance`, `integration`, `unit`.
- Verdict: **CHANGES_REQUESTED**.

### Findings

1. **HIGH — the A3 rejection matrix is not complete enough to constrain the
   sensitive provenance/authority decision.** The MariaDB test covers blocked,
   deleted `superadministrator` grant, one deleted permission, a second complete
   bootstrap owner, and an email-only non-bootstrap user. It does not cover an
   absent owner credential, either required grant with non-bootstrap `origin` or
   non-null actor, an inactive required role definition, an expected email with no
   matching user, or the contract's revoked/inactive-grant representation. Those
   are distinct normative predicates; an implementation may ignore credential,
   actor, role status, or one required grant's provenance and still pass. Add
   isolated, zero-mutation cases for every predicate using deliberately different
   witnesses, including both required role codes where asymmetry is possible.

2. **HIGH — the accepted-state matrix does not prove the stated tolerance for
   owner-side non-bootstrap roles and all named history families.** The successful
   developed fixture gives a manual role only to the additional user, not to the
   owner, and inserts auth and role history but no owner status event. Thus an
   implementation that rejects any extra owner role, or rejects status history,
   can pass despite A2 explicitly allowing both. Give the expected owner an
   additional active non-bootstrap role and add a status-event witness before the
   read-only resume; retain the full pre/post snapshot equality.

3. **HIGH — A5's concurrent-resume/lock obligation is absent.** The held-lock
   assertion and two-process race call only the strict production command. No test
   starts two `--resume-existing-local` calls or holds the advisory lock while the
   local seam runs. A new resume implementation that bypasses the existing
   per-database/prefix lock, performs incoherent reads, or uses a different lock
   key will pass. Exercise the explicit local command against a developed owner
   under a held canonical lock and with concurrent resume processes, requiring
   only coherent read success or the specified busy/conflict outcome and an
   unchanged full identity snapshot.

4. **HIGH — the A4 verifier is a source-text token check, not a real Make-route
   proof.** `initial_owner_local_resume_route_001_test.php` searches the whole
   Makefile for one CLI occurrence and the substring `--resume-existing-local
   --email`; it neither parses/executes target `up`, proves the call occurs after
   canonical migrations, nor proves another Make target/startup route does not
   select local continuation. The CLI/owner assertions are similarly satisfiable
   by dead strings or unused methods. An implementation can put the flag in an
   unrelated target/comment and leave `make up` strict while passing. Add a
   bounded real Make seam (or an execution-sensitive fake-command harness) that
   observes ordering and exact argv for `up`, plus negative production/web/
   migration startup ownership checks.

5. **MEDIUM — package RED records omit the failure transcript.** Both records bind
   exact candidate/source and exit `255`, but retain neither stdout nor stderr, so
   the package alone cannot show that setup completed and the first failure was
   the intended missing local behavior. Independent reproduction during this
   review did confirm the architecture RED at the missing Make flag and the
   MariaDB RED at local CLI returning `CONFIGURATION_INVALID` after clean create,
   strict replay, and developed-state fixture setup. After completing the matrix,
   capture fresh exact-source records containing the bounded assertion output so
   the next reviewer need not infer the failure from an outcome label.

### Assessment

The normative separation between strict production provisioning and explicit
read-only local continuation is coherent, and the tests use independently fixed
outputs, an independently frozen role-catalogue hash, randomized disposable
MariaDB databases/users, a DML-only runtime principal, unconditional scoped
cleanup, and full nine-table value snapshots. The reproduced RED failures are
genuine missing behavior rather than MariaDB setup failures. These strengths do
not close the omitted sensitive branches, resume concurrency, or execution-level
Make route.

Gate 4 is not authorized. Return to Gate 2, preserve the existing matrix, close
findings 1–4, record fresh transcript-bearing exact-source RED evidence, prepare a
new package, and obtain a fresh independent Gate 3 verdict. CI, deployment, and
enforcement remain `UNKNOWN` and are not treated as approval or GREEN.

---

## Gate 3 correction rereview — 2026-09-18

- Reviewer independence is unchanged: `/root/issue185_gate3` authored none of
  the corrected contract, OpenSpec artifacts, tests, production code, or evidence.
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T225117Z-54f9acf6fc/package.json`
  (`eeb4b033ddf019c6a23ebfb43108a699b500c840cce7965e5f2240600ed400de`).
- Previous reviewed snapshot:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T224653Z-02b68b3817/snapshot`.
- Correction delta: `delta.patch`
  (`61cd3b0647c716194e93f7957b8089439bc89c7dbe831067433f6e778dcadc76`).
- Exact corrected source: base `e419c2b5d1e4d6c9e46edda1adf7abac6883447a`,
  candidate `f8963590b871adb63c407b8b7e58e851bf374ccf5ddc8bc701fd7f568b0a16e9`,
  executable source `0a0b01f60f732bcfa9975314a73f66bea74a76fce1a292f41c8dcf3e7ce64b3b`.
- Corrected snapshot: `snapshot/source.patch`
  (`c3b3eae668d0fde49bb237fe570e7e9cf880ed462ff6dc37e7ffdc3e8ac71554`).
- Verification plan: `verification-plan.json`
  (`f41ddb9dab27669e2836446ccea388828ff6abbc630ab424f51111dc8995a3f5`),
  still correctly selecting `CRITICAL`, Gate 3 plus final review, and governance,
  integration, and unit categories.
- Verdict: **CHANGES_REQUESTED**.

### Prior-finding disposition

1. **Partially resolved.** The corrected rejection matrix now covers missing
   credential; missing `user` and `superadministrator` grants independently;
   inactive definitions for both roles; absent requested email; blocked and
   ambiguous owners; and a missing required permission, all with complete
   pre/post snapshots. However, provenance remains tested asymmetrically: only
   `user.origin` is corrupted and only `superadministrator.assigned_by_user_id`
   is corrupted. An implementation that checks origin only for
   `superadministrator` and actor only for `user` passes both cases while accepting
   invalid states. The prior requirement to cover both role codes wherever
   asymmetric implementation is possible therefore remains open.

2. **Resolved.** The expected owner now receives an additional active manual
   `access_administrator` role, and the developed fixture contains status history
   in addition to invitation, auth, and role-event history. The successful local
   resume still requires complete nine-table value equality, so rejecting or
   rewriting those tolerated facts is observable.

3. **Partially resolved.** A held canonical lock now requires the explicit local
   command to return the complete `75/PROVISIONING_BUSY/empty-stderr` tuple, and
   two real local CLI processes exercise concurrent resume with complete snapshot
   preservation. The concurrent oracle, however, extracts only tuple element `1`
   (stdout). It ignores each process exit code and stderr. A resume implementation
   can print an allowed success/busy JSON document while exiting nonzero or leaking
   diagnostics and still pass. Require the exact complete triples for both
   children, allowing only `[0, already_provisioned, '']` and
   `[75, PROVISIONING_BUSY, '']` in the permitted pairings.

4. **Resolved.** The route test executes `make --dry-run --no-print-directory up`,
   requires one expanded canonical CLI call, requires its exact explicit local
   flag and its position after the migration/prepare recipe, and checks named
   production/web/console composition sources do not opt into the flag. Together
   with the runtime CLI test this is execution-sensitive to the actual `up` target
   without performing local startup mutations.

5. **Resolved.** Both exact-source records retain exit `255`, stdout/stderr paths,
   byte counts, candidate/executable digests, and no source drift. The referenced
   logs contain the exact first failing assertions: the expanded Make recipe lacks
   the local flag, and the local CLI returns `CONFIGURATION_INVALID` only after
   clean creation, strict replay, and developed-fixture setup. Independent reruns
   reproduced both failures at the same assertions.

### Remaining findings

1. **HIGH — complete bootstrap-provenance sensitivity is still missing.** Add the
   complementary `superadministrator.origin != bootstrap` and
   `user.assigned_by_user_id IS NOT NULL` cases (or a generated two-role by
   two-provenance-field matrix), each requiring `LOCAL_OWNER_NOT_RESUMABLE` and
   exact unchanged identity. Retain the corrected credential, grant, role-status,
   permission, absent-email, blocked, and ambiguity witnesses.

2. **MEDIUM — concurrent local outcomes do not constrain process status or
   stderr.** Compare the complete `iopFinish()` triples, not only JSON stdout, so
   every accepted concurrent result proves the specified exit code and redacted
   empty stderr as well as coherent read-only state.

The corrected fixture remains deterministic and strongly isolated: randomized
database/user names, canonical migrations, a DML-only runtime principal, fixed
domain facts and independent expected values, bounded child reaping, and scoped
unconditional cleanup. Expected output literals and the frozen role-catalogue hash
remain independent of production output. No production implementation is present,
and the fresh RED is appropriately at the missing local behavior.

Gate 4 remains unauthorized pending the two narrow sensitivity corrections and a
fresh exact-source package/RED record followed by independent rereview. CI,
deployment, and enforcement remain `UNKNOWN`, not GREEN.

---

## Gate 3 narrow correction rereview — 2026-09-18

- Reviewer independence remains unchanged; `/root/issue185_gate3` authored no
  reviewed contract, OpenSpec artifact, test, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T225440Z-47912e3c2b/package.json`
  (`df22422bdef3b1b3a126bdc2801c56a5c76000088e5b09bc324e64a0c04c66ec`).
- Previous snapshot:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T225117Z-54f9acf6fc/snapshot`.
- Narrow delta: `delta.patch`
  (`f09b5d01818edf960278be79ee0d3e5e5660af2a496b9f7b7b38f75166b0e9d8`).
- Exact source: base `e419c2b5d1e4d6c9e46edda1adf7abac6883447a`,
  candidate `96bc3ee980783481bb57bf5f19a7179891dcde02fb083ecfc1e10b8f462739d4`,
  executable source `08a7554c8896513158e50bb77b6a786ccd82328a82a89d34b76036e4a0e1e4a9`.
- Snapshot: `snapshot/source.patch`
  (`830a9ae7d09d3f44945fa5028be16a92b3d305b2651d23887bae5750c6ab39af`).
- Verification plan: `verification-plan.json`
  (`3eb147865397f93c561e4669420ed2b80712423e664228cf5ccfd6bed4dedb36`).
- Verdict: **APPROVED**.

### Remaining-finding disposition

1. **Resolved — complete mandatory-role provenance matrix.** The isolated
   rejection family now independently corrupts `origin` for `user` and
   `superadministrator`, and independently supplies a non-null actor for each
   role. Every case still requires exact exit `65`, reason
   `LOCAL_OWNER_NOT_RESUMABLE`, empty stderr, and complete unchanged nine-table
   identity state. This closes the asymmetric implementation escape while
   retaining the earlier credential, both missing grants, both inactive role
   definitions, permission, blocked, absent-email, email-only, and ambiguity
   witnesses.

2. **Resolved — complete concurrent process outcomes.** The two explicit local
   child results are now compared as complete `[exit, stdout, stderr]` triples.
   The only permitted pairs are two exact
   `[0, already_provisioned, '']` results or one such success plus exact
   `[75, PROVISIONING_BUSY, '']`; pair order is normalized without deriving an
   expected value from production output. The full developed identity snapshot
   must remain unchanged after both children.

### Regression check and RED

The narrow delta changes only those two runtime-test oracles plus the append-only
prior review. The execution-sensitive architecture test is byte-identical at
`e44d472c030bb53a5cd5b23f20a52190cab81a60b983b0787f0228d675d507fa`,
so the real `make --dry-run up` route/order and production isolation checks remain
intact. The developed-owner extra manual role, status/auth/role history, canonical
held-lock check, disposable MariaDB isolation, DML-only principal, exact snapshots,
bounded child lifecycle, strict production cases, and all expanded sensitive
rejections also remain present.

Fresh evidence records are bound without source drift to candidate
`96bc3ee980783481bb57bf5f19a7179891dcde02fb083ecfc1e10b8f462739d4`.
Their retained transcripts show exit `255` at the intended missing behaviors:
the expanded `make up` recipe lacks `--resume-existing-local`, and the runtime
local invocation returns `CONFIGURATION_INVALID` after successful clean create,
strict replay, and developed-fixture setup. Independent review found no setup,
isolation, determinism, expected-value-independence, or sensitivity regression.

**Gate 3 is APPROVED. Gate 4 may implement only the reviewed explicit local-resume
behavior against these expectations.** The planner-selected bounded local checks,
independent Gate 5, and exact-source CI remain mandatory; CI, deployment, and
enforcement are still `UNKNOWN` and are not treated as GREEN.

---

## Supplemental Gate 3 — post-implementation root test correction — 2026-09-18

- Scope: only the root-authored two-line fixture/oracle delta in
  `tests/Runtime/initial_owner_provisioning_001_test.php`; production code is
  explicitly outside this supplemental review.
- Reviewer: `/root/issue185_gate3`, independent of the test correction and
  implementation.
- Previously approved Gate 3 package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T225440Z-47912e3c2b/package.json`,
  candidate `96bc3ee980783481bb57bf5f19a7179891dcde02fb083ecfc1e10b8f462739d4`.
- Current root package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T230842Z-7615f3d2bc/package.json`
  (`94335d22b993046cabccada8ed54b2d0ca030d74f5faffa3525b48d057b08dd8`),
  exact source `e57aab526783c6a3b9eb36c2e69c95537b4c867ccfdf7dd028cd0af8a23a6cce`,
  executable source `b2b76fddc5fa53ebcf5c11be80c01e82b8782626e12062cdca4a9d6c438dc99f`.
- Corrected runtime test SHA-256:
  `ad320f6ef39282653b6f8b2ce60a1b0582ced7fc23106e9d4966898b317941f2`.
- Verdict: **APPROVED**.

### Delta assessment

1. Consuming `SELECT RELEASE_LOCK(?)` and requiring result `1` is a fixture
   lifecycle correction. Previously `execute()` sent the release query but left
   its result unread before the next snapshot and concurrent child activity. The
   new assertion proves that this exact connection owned and released the
   canonical lock. It strengthens setup determinism and cannot make an invalid
   production outcome pass.

2. Changing the post-development invalid-args comparison from `$ownerBefore` to
   `$developedBefore` corrects the temporal oracle. At that point the fixture has
   deliberately changed the owner profile/credential/session and added another
   user, owner role, invitation, auth/role/status history. `$ownerBefore` describes
   the earlier pristine state and would incorrectly require invalid CLI calls to
   erase all legitimate developed facts. `$developedBefore` is the exact immediate
   preimage for those calls, so equality now proves their actual zero-mutation
   obligation. No acceptance branch or expected CLI outcome is relaxed.

All previously approved sensitive rejection cases, exact concurrent triples,
developed-owner success, strict production isolation, full nine-table snapshots,
disposable MariaDB ownership, DML-only principal, and execution-sensitive Make
route remain unchanged by this two-line test delta.

### Exact GREEN evidence

Record
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789686527756759000-b79007a1be344de0b35bae7ea12b81af.json`
is applicable and bound without source drift to source
`e57aab526783c6a3b9eb36c2e69c95537b4c867ccfdf7dd028cd0af8a23a6cce`,
executable source `b2b76fddc5fa53ebcf5c11be80c01e82b8782626e12062cdca4a9d6c438dc99f`,
and command blob
`ad320f6ef39282653b6f8b2ce60a1b0582ced7fc23106e9d4966898b317941f2`,
which equals the corrected runtime test SHA-256. It records command
`php tests/Runtime/initial_owner_provisioning_001_test.php`, exit/raw child exit
`0`, verdict/outcome `GREEN`, stdout exactly
`PASS: INITIAL-OWNER-PROVISIONING-001 explicit clean owner CLI`, and empty stderr.

**Supplemental Gate 3 is APPROVED.** The two root-authored corrections are valid
fixture/oracle repairs and preserve the previously approved acceptance matrix.
This verdict does not review or approve production implementation; independent
Gate 5 and the remaining planner-selected verification/CI obligations still apply.
