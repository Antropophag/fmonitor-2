# Independent Gate 1 review — PILOT-PREPARE-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/v9_fixture_alignment`  
Verdict: **CHANGES_REQUIRED**

Reviewer не редактировал executable spec, OpenSpec artifacts, code или tests.
OpenSpec package проходит strict validation.

## Contract assessment

Большая часть Gate 1 contract точна и implementable:

- production scope честно ограничен migration exact
  `GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare` в PilotHttp
  composition; POST command/domain, card mapping и other routes не присваиваются
  этому slice;
- local permission `assignment_order.prepare` и existing process capability с
  тем же literal являются двумя различными facts/gates;
- exact порядок local snapshot перед CSS/legacy/process/object/catalog reads
  позволяет различить local denial и downstream capability denial sentinels;
- positive actor имеет active/activated local identity, active assigned role,
  exact local permission, active descriptive predecessor identity и exact
  process capability;
- one-sided cases, near-match/inactive/legacy fallback, 401/403/404/409/503,
  safe correlation, repeat и committed revoke выражены отдельно;
- HEAD выполняет оба authorization gates и inherited integrity reads, имеет GET
  status/application headers/Content-Length и empty body;
- success и все rejection cases требуют full DB/filesystem/artifact/counter
  snapshots и zero session/audit/task/process mutation;
- explicit actor/unset environment исключает ambient positive authority;
  task-owned DB/user/prefix/CSS/artifact roots и exact finally cleanup покрывают
  setup/assertion/fault failures;
- tasks сохраняют Gate 1 owner approval → RED → independent test review →
  minimal GREEN → verification → independent code review.

Это согласуется с authoritative local RBAC, current-snapshot revoke semantics,
approved prepare-form read behavior и отдельным process-capability owner.

## Blocking finding — excluded POST ошибочно объявлен 405

Executable spec одновременно утверждает:

1. POST/CSRF/state-changing command seam вне scope и должен остаться untouched;
2. section 3: `POST|PUT|PATCH|DELETE` exact prepare route возвращают inherited
   `405 Allow: GET, HEAD` до authorization gates.

После successor `PILOT-E2E-FLOW-001` exact path имеет действующий POST prepare
command. Его media/CSRF/process admission принадлежит predecessor command
contract и не может быть отменён GET|HEAD RBAC fixture slice. Наследование
`PILOT-PREPARE-FORM-001 v0.1` допустимо только с учётом его successors, а не как
возврат к историческому pre-command 405.

OpenSpec delta правильно использует только PUT в wrong-method scenario, design,
proposal и tasks правильно исключают POST. Противоречие находится в controlling
executable `specs/PILOT-PREPARE-RBAC-FIXTURES-001.md`, поэтому Gate 1 owner
approval текущего hash небезопасен: Gate 2 мог бы либо изменить command
behavior, либо игнорировать normative 405.

## Required correction

В section 3 заменить wrong-method row на exact current split:

- `PUT|PATCH|DELETE` и действительно unsupported methods сохраняют inherited
  405/method precedence;
- `POST` явно наследует существующий POST/media/CSRF/process command contract и
  не изменяется/не тестируется как часть этого GET|HEAD migration slice.

После этой нормативной правки нужен fresh independent Gate 1 rereview и новый
owner-facing hash. Других blocking findings нет. Этот verdict не разрешает
Gate 2 tests или implementation.

## Reviewed hashes

```text
948d4108f20066f49c9533a13a9ee9ce87692c918e72bcb2a02f51a6508508d9  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
62409b6a18bba29992c464fbbe60ff69744b3f8eeb5a4d1187dbbb2cfcb7cd4f  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
85189e1f36123b119806b3af55ca23312dd2787eeebcbe81f27164c52a034d95  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6eff0d0b595268a2d735197c642a081c99cbd1aacf02605576f47d7398c3db0f  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
4c5423ca15dadc2d6bfd18ec683b3bafc5f9178d9a54ddc220d3139aab2b6f00  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
35759ce4856d703e197c1e70e00a14ec316b3e94104ca959a5d4abf19c50c669  specs/PILOT-PREPARE-FORM-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
```

## Verification

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid
```
