# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 architecture ratchet

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_arch_test_review`
- Test author: not this reviewer
- Reviewed commit: `9f856935c94fd86f4347ca51b98825779c943744`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` v9,
  sections 7–8, and OpenSpec task 3.3
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the reviewed tests or production checker.
This review record is the reviewer's only change.

## Findings

### 1. Task 3.3's unsafe-repair prohibition is not exercised

The first fixture contains the five listed native session lifecycle calls and
one hardcoded compatibility root, so its independently derived expected
cardinality is exactly six. It contains no unsafe repair primitive such as
`chmod` or `chown`. An implementation that rejects all six fixture lines but
allows a session owner to repair unsafe uid/mode state would make the reviewed
tests GREEN while violating section 1 and the explicit `unsafe repair` clause
of task 3.3. Add an isolated production-shaped unsafe-repair fixture and an
independently derived expectation.

### 2. Exact allowed owners are not protected against over-broad matching

The second fixture correctly has three unauthorized calls: one operation-result
factory, one event factory, and one inspection-result factory. Its expected
cardinality is therefore exactly three. However, neither test proves that those
same restricted factories remain allowed in only `FilesystemPilotSessionStorage`
and `PilotSessionStorageInspector`, respectively. Because `files()` is patched
to only the violating fixture, a blanket matcher that rejects every restricted
factory call would satisfy the test. Add isolated owner and inspector fixtures
that expect zero `session_storage_ownership` findings, including both event
phases and the inspection success/unavailable factories (or an equivalently
complete exact-owner matrix).

These are mutation-sensitivity gaps in the claimed architectural boundary, not
production implementation findings. Isolation itself is sound: both temporary
files live under production roots, the collector's discovery port is patched to
the exact fixture, and `NamedTemporaryFile` removes each fixture after use.

## RED replay

Command:

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers
```

Observed at the reviewed commit: two assertion failures, `expected 6, actual 0`
and `expected 3, actual 0`. This is the intended missing-ratchet RED, not setup
failure.

## Exact reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
f945be78dd92834fad9bc5ece1642a76f0716d6e2cc16a8c6991edb493493abd  tools/architecture/tests/test_debt_fingerprint.py
0d7484a20984b27520d5e6ef632fa3235f63b8a1c6fc63de6eba7f7b2118c681  docs/operations/pilot-session-storage-architecture-ratchet-red-evidence.md
```
