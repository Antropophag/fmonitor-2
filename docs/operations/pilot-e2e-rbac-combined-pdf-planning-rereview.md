# Independent planning rereview — E2E RBAC fixtures and combined PDF

Date: 2026-09-02  
Reviewer: fresh independent planning agent `/root/v9_fixture_alignment`  
Supersedes review input: `8b01ef7b…`  
Verdict: **CHANGES_REQUESTED**

Reviewer не редактировал planning artifacts, code или tests. Оба changes
проходят strict OpenSpec validation.

## Closure of prior findings

### Combined PDF

`pilot-e2e-combined-pdf` теперь закрывает прежние planning blockers:

- tasks требуют полный executable amendment `PILOT-E2E-FLOW-001 v0.5`, который
  заменяет route/card/two-HTML/happy-journey/oracle/non-goal clauses v0.4;
  approval v0.4 явно не переносится;
- public cardinality равна одному versioned type `order`, media
  `application/pdf`; appendix metadata/blob/link запрещены;
- GET/HEAD фиксируют 200, filename, `%PDF-`, persisted length/hash/bytes, три
  page objects и independently decoded order/appendix semantic markers/order;
- authorization-first 403, metadata/integrity 404 и EACCES/shard 503 имеют
  exact bodies/headers/redaction; HEAD выполняет те же reads и возвращает empty
  body с GET parity;
- repeat, fresh reload и two-process concurrent reads сравнивают process
  projection, artifact list, events, DB counters и storage identity/count;
- dependency order и isolated cleanup inventory/order выражены явно.

Это сохраняет owning seams `AssignmentOrderArtifactService.download(...)` и
content-addressed store, не возвращает legacy appendix и не смешивает PDF с
RBAC implementation.

### RBAC scope/revoke

RBAC draft правильно сузил migration до уже migrated
`GET /pilot/objects → objects.read`; prepare/register/open остаются predecessor
process-capability/route contracts и не получают неутверждённые local mappings.
Revoke теперь exact committed test-admin DELETE между invocations; spec явно
говорит, что authorization audit не создаётся, а fixture-admin audit вне scope.
Denial предшествует list handler read, combined-PDF boundary остаётся
downstream dependency.

## Remaining blocking contradiction

`design.md`, Decision 1, утверждает:

```text
Fixture seed задаёт local role objects_reader с единственным objects.read
actor-ам 18/19.
```

Но executable spec требует actor `19` быть **legacy-only без local grant** и
получить generic 403. Это также exact current object-list matrix:

- actor `18` — canonical active/activated local user с exact `objects.read`;
- actor `19` — active legacy reader, но без local user/role/permission authority;
  legacy `REMOTE_USER` не спасает denial.

Если общий seed выдаёт role actor 19, negative scenario становится 200 и не
доказывает запрет fallback. Если предполагаются разные isolated seed variants,
это должно быть сказано нормативно: positive/main journey seed grants только
18; legacy-denial branch создаёт 19 только в legacy namespace и явно проверяет
отсутствие local rows. Revoke branch после positive GET удаляет grant 18 и
должна быть изолирована/reset до downstream main journey, иначе PDF boundary
становится недостижимой.

`tasks.md` 1.1 также формулирует actors 18/19 рядом с exact `objects.read` и
должен исключить двусмысленность тем же exact matrix.

Это Gate 1 blocker, а не implementation detail: он определяет, какой actor
авторизован, и меняет expected public HTTP outcome.

## Required correction

1. Исправить design/tasks на exact actor matrix: grant `objects.read` actor 18;
   actor 19 legacy-only/no local authority в denial fixture.
2. Явно разделить main/revoke/legacy-denial fixture states и восстановление
   main grant до downstream artifact assertions.
3. После изменения получить fresh rereview и только затем owner approval exact
   hashes. Combined-PDF artifacts в текущем виде дополнительных findings не
   имеют.

Этот verdict не разрешает Gate 2 RED или implementation.

## Reviewed hashes

```text
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
aa8850bb2c410ec560b8e9853955f45e78da0e0d1257a97bdec2c52f468a9f4b  openspec/changes/pilot-e2e-rbac-fixtures/design.md
1072dfef52a435fe92e991c88476d14b6c1d729f73871b871a609c40586d6914  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
9357b57f680c70f173418b3f4d80b3058f915afb7248d2637e11411682640d11  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  openspec/changes/pilot-e2e-combined-pdf/proposal.md
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  openspec/changes/pilot-e2e-combined-pdf/design.md
fd40dd591a4ee194a3c4d871a949398dec4cef1c05fcd309a38b2226f2756b07  openspec/changes/pilot-e2e-combined-pdf/tasks.md
db27c4fc023c9e10850fc056fc0fb9363aa985bf32e5a3016ebd2eada91d3c8f  openspec/changes/pilot-e2e-combined-pdf/specs/verification/pilot-e2e-combined-pdf/spec.md
8b01ef7b797476dafec2a1c16882fbcd586ef74729c9e0cc0327aefc7f3bd000  docs/operations/pilot-e2e-rbac-combined-pdf-planning-review.md
```

## Verification

```text
openspec validate pilot-e2e-rbac-fixtures --strict
Change 'pilot-e2e-rbac-fixtures' is valid

openspec validate pilot-e2e-combined-pdf --strict
Change 'pilot-e2e-combined-pdf' is valid
```
