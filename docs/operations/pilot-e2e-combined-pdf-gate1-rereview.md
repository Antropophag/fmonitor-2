# Independent joint Gate 1 rereview — PILOT-E2E-FLOW v0.5 / COMBINED-PDF

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/v9_fixture_alignment`  
Prior review: `cc80b31a…`, `CHANGES_REQUIRED`  
Verdict: **READY_FOR_OWNER_APPROVAL**

Reviewer не редактировал specs, OpenSpec artifacts, code или tests. Combined
OpenSpec package проходит strict validation.

## Closure of prior finding

`PILOT-E2E-FLOW-001 v0.5` section 12 теперь явно supersede-ит все
противоречащие artifact clauses v0.4:

```text
sections 1, 2, 3, 4, 5, 6,
section 8 steps 5–6 and postcondition,
section 9 artifact oracle,
section 10 PDF non-goal,
section 11 / прежнюю Gate 1 authorization
```

Поэтому historical section 2 `order|appendix` allowlist, section 3 two links,
section 4 HTML response, section 5 immutable artifacts wording, section 6
downloads, section 8 two-artifact journey, section 9 HTML oracle и section 10
no-PDF clause больше не являются current requirements. Единственный current
route contract находится в section 12.1: literal `order`; `appendix` и любой
иной type дают 404 до metadata/store read.

Повторный scan не нашёл противоречащего artifact clause вне полного
supersession inventory. Current marker `appendix` в section 12 означает часть
содержимого combined PDF, а не отдельный public artifact.

## Joint Gate 1 assessment

Current controlling specs согласованы и executable:

- один immutable versioned `order`, exact Unicode filename,
  `application/pdf`, `%PDF-`, persisted positive size/lowercase SHA-256/bytes;
- ровно одна card link `Скачать распоряжение`; appendix metadata/blob/link/type
  отсутствуют до и после registration/open/fresh reload;
- GET/HEAD exact URL parity; HEAD выполняет те же authorization, metadata и
  content-addressed integrity reads, сохраняет GET status/application headers/
  Content-Length и возвращает empty body;
- independent decoder ожидает ровно три page objects и fixed marker/page order,
  доказывая order pages перед appendix content и installer/engineer
  correlation без production-derived expected hash;
- actor without exact predecessor artifact-read authority получает exact 403
  до projection/store; unknown/invalid metadata/integrity — exact 404;
  found-metadata digest/shard I/O/EACCES/identity outage — redacted 503 с
  Retry-After/no-store/security headers и без partial bytes/leak;
- digest и shard faults используют отдельные task-owned fixtures, один failure
  request, mandatory restoration, fresh connection/service reload и full
  projection/artifact/event/counter/storage identity comparison;
- sequential repeat и two-process concurrent reads дают byte-identical PDF и
  не создают duplicate metadata/blob, audit/domain/read facts или temp residue;
- prerequisite RBAC/process admission проверяется до PDF assertion; setup/RBAC
  failure классифицируется отдельно;
- cleanup order stop/reap → restore → close → revoke/drop exact DB user/database
  → verified task session/artifact roots выполняется и при setup/assertion fault;
- renderer/storage schema/process commands/RBAC routes/signing/1C integration и
  production data остаются вне scope; legacy appendix compatibility не
  возвращается;
- v0.4 approval сохраняется только как superseded history. Joint owner approval
  exact v0.5/supporting hashes обязателен до Gate 2, затем сохраняются intended
  RED, independent test review, minimal GREEN, focused/full/fresh verification,
  architecture и independent code review.

Supporting `PILOT-E2E-COMBINED-PDF-001` правильно называет v0.5 section 12
controlling и требует joint review/approval; при расхождении full amendment
имеет приоритет.

## Gate decision

Оба Gate 1 drafts готовы к одному explicit joint owner approval exact hashes.
Этот rereview не является owner approval и не разрешает test edits, RED или
implementation. Любое normative изменение требует нового joint review.

## Reviewed hashes

```text
c792b7bd3c707b0b9bd4fe2e934c677d44235ce2da41839688383391d47f3ec5  specs/PILOT-E2E-FLOW-001.md
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  openspec/changes/pilot-e2e-combined-pdf/proposal.md
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  openspec/changes/pilot-e2e-combined-pdf/design.md
fd40dd591a4ee194a3c4d871a949398dec4cef1c05fcd309a38b2226f2756b07  openspec/changes/pilot-e2e-combined-pdf/tasks.md
db27c4fc023c9e10850fc056fc0fb9363aa985bf32e5a3016ebd2eada91d3c8f  openspec/changes/pilot-e2e-combined-pdf/specs/verification/pilot-e2e-combined-pdf/spec.md
cc80b31aab3a30347768d403a43e45c74d289b57d1040b40c0485548f7e79ee0  docs/operations/pilot-e2e-combined-pdf-gate1-review.md
```

## Verification

```text
openspec validate pilot-e2e-combined-pdf --strict
Change 'pilot-e2e-combined-pdf' is valid

rg -n "appendix|text/html|order\\|appendix|Скачать приложение|PDF/DOCX" \
  specs/PILOT-E2E-FLOW-001.md
# every old artifact contract match is covered by the explicit supersession list;
# current matches belong to section 12 one-PDF semantics/negative assertions
```
