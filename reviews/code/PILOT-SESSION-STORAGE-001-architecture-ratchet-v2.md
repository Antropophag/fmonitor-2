# Independent Gate 5 code rereview v2 — PILOT-SESSION-STORAGE-001 architecture ratchet

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_ratchet_gate5_v2`
- Implementation author: not this reviewer
- Reviewed implementation commit: `00e301315274f6fce2cce67604cad6998f9456b9`
- Evidence-bearing repository HEAD: `cecb098e1a403177c0a714c9e9d17a5e2b35e530`
- Approved Gate 3: `reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v5.md`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` sections 7–8,
  OpenSpec task 3.3, and immutable prior Gate 5 return record
  `reviews/code/PILOT-SESSION-STORAGE-001-architecture-ratchet-v1.md`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, tests, checker
implementation, or GREEN evidence. This append-only review record is the
reviewer's only change.

## Standards

No blocking standards finding remains in the ratchet scope. The implementation
is confined to the canonical architecture collector and adds no baseline debt,
product behavior, ownership exception, or production-session implementation.
The factory allowlist now compares the normalized repository-relative `rel`
against the two exact canonical owner paths. It therefore closes prior finding
S1 without granting authority to a matching basename elsewhere.

The regex collector deliberately reports direct native primitives, the fixed
compatibility root (including unsafe repair on that root), and internal result/
event factories outside their exact owners. The tests exercise all categories,
both canonical owners, and basename impersonation through the public
`collect()` seam. No maintainability, security-boundary, or smell finding is
material at this narrow enforcement seam.

## Specification and sensitivity

The complete ratchet diff from the pre-collector base was reviewed, including
the original collector implementation, all return/review records, the corrected
negative fixture, Gate 3 v5, and the exact-path production fix. The test pair
approved at Gate 3 remains unchanged after approval: canonical production files
at the two exact paths yield no ownership finding, while the same basenames
under `rapid-pilot/` yield exactly three independently countable forbidden
factory findings.

The correction is minimal and matches the authorized GREEN: only two basename
comparisons became exact repository-relative path comparisons. It neither
suppresses nor baselines the remaining native consumers. The full collector at
the reviewed state exposes exactly 13 production findings, all and only in:

- `app/PilotHttp/PilotE2ECoordinator.php` — 2;
- `rapid-pilot/LocalAuth.php` — 6;
- `rapid-pilot/UserAccessView.php` — 5.

Those findings are the explicit follow-on consumer-migration scope. Their
presence correctly keeps whole-repository `make architecture-check` red; it is
not a defect in this ratchet sub-slice and this approval does not approve or
claim completion of that migration.

## Verification evidence

At repository HEAD `cecb098e1a403177c0a714c9e9d17a5e2b35e530`, whose latest
production change is exact commit `00e301315274f6fce2cce67604cad6998f9456b9`:

```text
python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 22 tests in 2.940s
OK

python3 collector inspection
COUNT 13
# 2 PilotE2ECoordinator.php, 6 LocalAuth.php, 5 UserAccessView.php;
# no session_storage_ownership finding elsewhere

make architecture-check
ARCHITECTURE CHECK FAILED
# exactly the same 13 expected follow-on session_storage_ownership findings

git diff --check 58efd36...00e3013
# exit 0, no output
```

Reviewed hashes:

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
c720c7040f1618e870e0a7ad4fe036a5ac802e17e5c9d8cd12d8a2dbbe53f155  tools/architecture/tests/test_debt_fingerprint.py
8da87f0ec6f9ebdd770d9de53a0c82478d824cf184a39f07538fd312db88a103  tools/architecture/check.py
ea4f7bf47e99768743702c004b044dd7487588167b036aad254fff568f97c506  docs/operations/pilot-session-storage-architecture-ratchet-impersonation-green.md
```

## Gate consequence

**APPROVED** for the architecture-ratchet sub-slice at implementation commit
`00e3013`. Prior Gate 5 finding S1 is resolved, the approved test expectation is
unchanged, and the narrow ratchet has passed Gates 1–5. This verdict does not
approve session-consumer migration, remove the 13 findings, or establish full
session-storage / repository GREEN.
