# Independent Gate 1 rereview — PILOT-PREPARE-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/v9_fixture_alignment`  
Prior review: `e4884c53…`, `CHANGES_REQUIRED`  
Verdict: **APPROVED / READY_FOR_OWNER_APPROVAL**

Reviewer не редактировал executable spec, OpenSpec artifacts, code или tests.
OpenSpec package проходит strict validation.

## Closure of prior finding

Controlling executable spec hash `56580471…` теперь сохраняет current method
composition без противоречия:

- POST exact prepare path наследует действующий
  `PILOT-E2E-FLOW-001` media/CSRF/process command contract и полностью вне
  GET|HEAD migration scope;
- unsupported `PUT|PATCH|DELETE` сохраняют 405 до authorization reads;
- exact `Allow` равен `GET, HEAD, POST`, поэтому response не скрывает
  существующий command method.

Это закрывает единственный blocker предыдущего review. OpenSpec wrong-method
scenario по-прежнему использует PUT; proposal/design/tasks неизменно исключают
изменение POST command.

## Gate 1 assessment

Spec является точным и исполнимым vertical security contract:

- public migration seam ограничен exact GET|HEAD prepare-form path;
- static local route permission `assignment_order.prepare` проверяется через
  stable local-RBAC seam до CSS/process/object/catalog reads;
- downstream process capability с тем же literal является отдельным fact/gate;
  ни один gate, `objects.read`, legacy identity или near-match не подменяет другой;
- positive actor 18 имеет exact local active/activation/role/permission,
  descriptive predecessor identity и process capability;
- one-sided local/process denials и absent-both case имеют generic 403, но
  отдельные sentinels доказывают exact order; 401 и local 503 сохраняют stable
  local-auth meanings;
- unknown object 404 следует только после обоих gates; coherent wrong state 409
  и downstream integrity/catalog 503 сохраняют predecessor redaction;
- HEAD выполняет оба gates и inherited reads, возвращает GET status/headers/
  Content-Length и empty body;
- full DB/filesystem/artifact/counter snapshots доказывают read-only success и
  zero mutation для 401/403/404/405/409/503; authorization не создаёт audit;
- isolated local-permission revoke и process-capability revoke различают оба
  current-snapshot gates, не мутируя main positive fixture;
- explicit actor/unset/replacement env исключает ambient authority, а legacy
  `REMOTE_USER` остаётся только descriptive input и не fallback;
- task-owned DB/user/prefix/CSS/artifact roots, foreign decoys и finally
  stop/reap/close/drop/verified-root cleanup покрывают setup/assertion/fault
  failures;
- Gates 1–5 идут в обязательном порядке, test change возвращает Gate 2 на
  независимый review, architecture baseline не расширяется.

Production ownership честно находится в PilotHttp route composition; existing
InstallationProcess command/domain behavior не меняется. Contract не расширяет
card/other route mappings, session protocol или PDF semantics.

## Gate decision

Executable Gate 1 draft готов к explicit owner approval exact reviewed hash.
Этот rereview не является owner approval и не разрешает Gate 2 RED, test edits
или implementation. После любого normative изменения требуется новый review.

## Reviewed hashes

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
62409b6a18bba29992c464fbbe60ff69744b3f8eeb5a4d1187dbbb2cfcb7cd4f  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
85189e1f36123b119806b3af55ca23312dd2787eeebcbe81f27164c52a034d95  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6eff0d0b595268a2d735197c642a081c99cbd1aacf02605576f47d7398c3db0f  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
4c5423ca15dadc2d6bfd18ec683b3bafc5f9178d9a54ddc220d3139aab2b6f00  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
e4884c53ff3a19c904f2a678f7d3af5196187e6a4e4dbb6cbb94f0d34f0a5459  docs/operations/pilot-prepare-rbac-fixtures-gate1-review.md
```

## Verification

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid
```
