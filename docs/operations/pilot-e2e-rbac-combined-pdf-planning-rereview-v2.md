# Independent planning rereview v2 — E2E RBAC fixtures and combined PDF

Date: 2026-09-02  
Reviewer: fresh independent planning agent `/root/v9_fixture_alignment`  
Prior verdict: `CHANGES_REQUESTED`, review `02b3bdc5…`  
Verdict: **APPROVED_FOR_GATE1_DRAFT**

Reviewer не редактировал planning artifacts, code или tests. Оба changes
проходят strict OpenSpec validation.

## RBAC fixture assessment

Последнее противоречие закрыто exact matrix:

- main actor `18` — fictional active/activated local user, active assigned role
  `objects_reader`, единственный local permission `objects.read`;
- actor `19` в negative branch существует только в legacy users/roles и не
  имеет local user/role/grant; `GET /pilot/objects` возвращает generic 403 до
  list handler read;
- trusted actor ID поступает из local authentication boundary; direct
  `REMOTE_USER`, email/name и legacy role не становятся authority;
- prepare/register/open/card/control не получают новых local-RBAC mappings:
  существующие process capabilities и predecessor route contracts остаются
  отдельными facts/slices.

Revoke больше не повреждает main journey. Отдельная isolated DB fixture сначала
доказывает 200 actor 18, затем committed test-admin DELETE exact
`role → objects.read` и 403 следующего invocation. Authorization audit не
создаётся; fixture-admin audit явно вне scope. Main fixture остаётся
byte-equivalent и повторно доказывает actor-18 admission 200 перед downstream
artifact boundary. Tasks, spec и design описывают один и тот же порядок.

Cleanup требует task-owned credentials, sessions, DB и artifact roots в
`finally`; primary result фиксируется до cleanup. RBAC slice не исправляет и не
ослабляет PDF assertions, поэтому artifact failure остаётся наблюдаемой
dependency, а не authorization/setup result.

## Combined-PDF assessment

Combined artifacts не менялись после rereview `02b3…`; его положительная
оценка сохраняется. План требует отдельный owner-approved executable amendment
`PILOT-E2E-FLOW-001 v0.5`, полностью superseding v0.4 two-HTML route/card/
journey/oracle/non-goal clauses. Approval v0.4 не переносится автоматически.

Supporting contract точен и falsifiable:

- ровно один versioned artifact type `order`, media `application/pdf`, exact
  filename, persisted size/SHA-256/bytes и отсутствие appendix metadata/blob/link;
- GET/HEAD 200 parity, `%PDF-`, три page objects и independent decoded order +
  appendix semantic marker/order oracle;
- authorization-first exact 403, metadata/integrity exact 404, digest/shard
  access exact redacted 503 с `Retry-After: 60`;
- repeat/fresh reload/two-process concurrent reads сравнивают full process
  projection, artifact list, events, counters и storage identity/count;
- main journey доказывает RBAC/process prerequisites до PDF assertion;
  isolated fault/concurrency fixtures имеют отдельные DB/user/server/session/
  artifact ownership и точный restore/reap/drop/verified-root cleanup order.

Новый legacy appendix contract, broad role, production fault hook, renderer или
storage redesign не разрешены.

## Gate decision

Оба planning packages готовы к отдельному explicit owner approval exact hashes.
Этот verdict подтверждает только Gate 1 draft quality. Он не утверждает product
spec автоматически и не разрешает Gate 2 RED, test edits или implementation до
owner approval каждого controlling artifact/amendment.

## Reviewed hashes

```text
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  openspec/changes/pilot-e2e-combined-pdf/proposal.md
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  openspec/changes/pilot-e2e-combined-pdf/design.md
fd40dd591a4ee194a3c4d871a949398dec4cef1c05fcd309a38b2226f2756b07  openspec/changes/pilot-e2e-combined-pdf/tasks.md
db27c4fc023c9e10850fc056fc0fb9363aa985bf32e5a3016ebd2eada91d3c8f  openspec/changes/pilot-e2e-combined-pdf/specs/verification/pilot-e2e-combined-pdf/spec.md
02b3bdc59120d8c94d921ee7ab7202f870d4a9cec6931fbd6edbb64743d24bad  docs/operations/pilot-e2e-rbac-combined-pdf-planning-rereview.md
```

## Verification

```text
openspec validate pilot-e2e-rbac-fixtures --strict
Change 'pilot-e2e-rbac-fixtures' is valid

openspec validate pilot-e2e-combined-pdf --strict
Change 'pilot-e2e-combined-pdf' is valid
```
