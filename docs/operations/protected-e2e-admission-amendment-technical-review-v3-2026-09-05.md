# Protected E2E admission amendment — independent technical rereview v3

- Recorded: `2026-09-05`
- Reviewer: `/root/protected_e2e_gate1_scope_audit`
- Candidate: `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`
- Candidate SHA-256: `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Verdict: **READY_FOR_OWNER_APPROVAL**
- Scope: planning/technical Gate 1 readiness only; no owner approval claim and
  no protected specification, test or production change.

Candidate revision 3 resolves the two concrete findings from rereview v2.

The fact projection is now exact and markup-independent: descendant text nodes
inside the identified object `li` are normalized in document order, empty runs
are dropped, and runs are joined with an explicit ASCII space. The contract
therefore accepts both the independently specified split-element positive and
the approved configured composition where address and `Подъезд 2` share one
text container. It does not depend on renderer methods, CSS classes or
fixture-only element boundaries.

The Unicode letter/number neighbor rule prevents accidental substring matches
for the fixed literals. The added literal negatives exercise `Подъезд 22`,
`Подъезд 20` and `д. 100`, while the earlier matrix continues to cover missing,
duplicate and wrong object links, wrong placement, each other inherited fact,
duplicate main, native table and `.shlz-table-wrap`. Expected data remains
independently fixed rather than derived from production output.

The independently executable public test-support oracle retains an explicit
missing-oracle assertion RED, separate independent Gate 3, focused GREEN and
Gate 5. The later protected assertion-only patch has its own unapplied-patch
Gate 3. The complete protected E2E remains unskipped and cannot convert a
downstream failure into success. No gate waiver, production change,
manual-registration target approval or broader E2E authority is introduced.

No remaining technical Gate 1 ambiguity was found within this bounded
admission-fixture amendment. Owner approval must pin the exact candidate hash
above; later byte changes require rereview.

## Exact reviewed bytes

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```
