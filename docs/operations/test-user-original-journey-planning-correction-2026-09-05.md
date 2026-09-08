# TEST-USER original journey — planning correction

Date: 2026-09-05. Author: `/root`.
Exact base: `165569e82d9392426e06bacc0807f98cc4a23063`.

The existing seed planning still specified prepare → register → open. This
contradicts the owner-approved original-upload workflow in PRODUCT, CONTEXT,
pilot spec and original owner decision. This correction updates only the
existing four OpenSpec artifacts: optional template, initial upload after
template/directly, immutable original and separate opening, with no manual
registration gate. It also names the missing delivery dependencies explicitly.

No executable spec, test, production, grant or historical evidence is changed.
The amendment does not approve original read grants or make TEST-USER ready.
Seed implementation remains blocked on exact predecessor and Gate 1 evidence.
No prior checked task is reset or represented as new approval.

Exact candidate SHA-256:

```text
d67415fd56e174aae7a3e5d95846346cf548f21b82ba5e9bf15c663c57f65295  openspec/changes/seed-test-user-fixtures/proposal.md
1c412fdd512edabc505b8a3e3245228a0e5965cdcf9e30bacf8bf663ef9413e4  openspec/changes/seed-test-user-fixtures/design.md
d1213d923c1d618ea133ddbddf50c19ac5b1c03382a8731766cc634f1dfe1a63  openspec/changes/seed-test-user-fixtures/tasks.md
1275225f00122a10d69a83f579217a23774a44b00c591cf31753b3f674ee3d9a  openspec/changes/seed-test-user-fixtures/specs/operations/test-user-fixture-seed/spec.md
```

`openspec validate seed-test-user-fixtures --strict`: valid.
`git diff --check`: exit 0. Independent planning review is still required.
