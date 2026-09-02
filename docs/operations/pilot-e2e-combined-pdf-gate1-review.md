# Independent joint Gate 1 review — PILOT-E2E-FLOW-001 v0.5 / COMBINED-PDF-001

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/v9_fixture_alignment`  
Verdict: **CHANGES_REQUIRED**

Reviewer не редактировал specs, OpenSpec artifacts, code или tests. Combined
OpenSpec package проходит strict validation.

## Positive assessment

Supporting `PILOT-E2E-COMBINED-PDF-001` и amendment section 12 задают
исполняемый one-artifact contract:

- ровно один versioned `order`, Unicode filename, `application/pdf`, `%PDF-`,
  persisted positive size/lowercase SHA-256/exact bytes;
- prepared/registered card имеет одну `Скачать распоряжение` link и не имеет
  appendix link/metadata/blob;
- real GET и HEAD используют exact object/version/order seam; HEAD выполняет те
  же authorization/metadata/store reads, возвращает GET status/application
  headers/Content-Length и empty body;
- independent decoder проверяет три page objects, fixed semantic markers,
  order-before-appendix и installer/engineer correlation, не копируя expected
  hash из production output; hash фиксирован только внутри immutable prepared
  version;
- authorization-first 403, metadata/integrity 404 и found-metadata storage
  I/O/EACCES/identity 503 имеют exact bodies, Retry-After/no-store/security
  headers и redaction без partial bytes;
- digest и shard EACCES изолированы, fault восстанавливается до fresh
  connection/service reload и полного projection/artifact/event/counter/
  storage snapshot comparison;
- sequential repeat и two-process concurrent reads доказывают byte identity и
  отсутствие новых metadata/blobs/audit/domain/read facts;
- cleanup order и task-owned DB/user/server/session/artifact inventory точны;
  RBAC/setup failure классифицируется prerequisite до artifact RED;
- v0.4 approval явно не переносится; joint exact owner approval должен
  предшествовать Gate 2, затем сохраняются RED, independent test review,
  minimal GREEN, focused/full/fresh verification и independent code review.

Эти clauses согласуются с owner decision и `ARTIFACT-STORE-001 v0.3`; renderer,
storage schema, process commands, RBAC route mappings и legacy appendix
compatibility не расширяются.

## Blocking finding — incomplete v0.4 route supersession

`PILOT-E2E-FLOW-001 v0.5` section 12 говорит, что полностью supersede-ит
противоречащие clauses sections:

```text
1, 3, 4, 6, 8 step 5–6/postcondition, 9, 10 и Gate 1 authorization
```

Section **2** в этом списке отсутствует. Но section 2 остаётся нормативным и
утверждает:

```text
artifact route type = order|appendix
Artifact type допускает order и appendix
```

Section 12.1 одновременно утверждает, что route допускает только literal
`order`, а `appendix` даёт 404 до metadata/store read. Поскольку section 2 не
помечен superseded, один controlling hash требует двух взаимоисключающих public
outcomes для exact appendix URL. Strict OpenSpec validation этого semantic
конфликта не видит.

Это не исторический комментарий: route/type allowlist определяет transport
admission, authorization/store observability и Gate 2 expected status.

## Required correction

Сделать supersession однозначным одним из способов:

1. предпочтительно переписать v0.5 как цельный current spec без нормативных
   two-HTML/order|appendix clauses, сохранив v0.4 history в review/VCS; или
2. как минимум явно включить section 2 route/type clauses в supersession list и
   пометить старую route table/allowlist non-controlling historical text так,
   чтобы единственным current contract был `type=order`, appendix → 404.

Следует повторно просканировать весь current v0.5 на remaining normative
`text/html`, two-link, appendix artifact/card/type clauses и либо удалить их,
либо однозначно перечислить как superseded. После изменения нужен fresh joint
review новых exact hashes.

Других blocking findings в supporting combined-PDF spec/amendment inventory не
обнаружено. Этот verdict не разрешает owner approval, Gate 2 test edits или
implementation.

## Reviewed hashes

```text
1dc900e304d84c20c72e2a9e88b7c1088eea9783deaf83b23b874e3b6ef082b8  specs/PILOT-E2E-FLOW-001.md
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  openspec/changes/pilot-e2e-combined-pdf/proposal.md
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  openspec/changes/pilot-e2e-combined-pdf/design.md
fd40dd591a4ee194a3c4d871a949398dec4cef1c05fcd309a38b2226f2756b07  openspec/changes/pilot-e2e-combined-pdf/tasks.md
db27c4fc023c9e10850fc056fc0fb9363aa985bf32e5a3016ebd2eada91d3c8f  openspec/changes/pilot-e2e-combined-pdf/specs/verification/pilot-e2e-combined-pdf/spec.md
c895577284369a33dbb58b12b3aba1fc1761d2ffacf3b1d32d8dc9d2db3fa3b5  docs/operations/pilot-e2e-rbac-combined-pdf-planning-rereview-v2.md
9498bfca1001360f64d6a31dc5588956d8cb4021feb8515d1b017aff913cf1bc  specs/ARTIFACT-STORE-001.md
048615871f93d6232648659f6bce0f50b1bdd907194d31d1cd5dc8a1257fdf91  docs/operations/security-artifact-contract-owner-decision.md
```

## Verification

```text
openspec validate pilot-e2e-combined-pdf --strict
Change 'pilot-e2e-combined-pdf' is valid

rg -n "appendix|text/html|order\\|appendix|Скачать приложение" \
  specs/PILOT-E2E-FLOW-001.md
# finds still-normative v0.4 section 2 route/type clauses plus other historical clauses
```
