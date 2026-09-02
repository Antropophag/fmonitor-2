# Independent planning review — E2E RBAC fixtures and combined PDF

Date: 2026-09-02  
Reviewer: independent planning agent `/root/v9_fixture_alignment`  
Scope: `pilot-e2e-rbac-fixtures`, `pilot-e2e-combined-pdf`  
Verdict: **CHANGES_REQUESTED**

Reviewer не редактировал planning artifacts, code или tests. Оба changes
проходят `openspec validate --strict`; structural validity не закрывает
нормативные findings ниже.

## Controlling evidence

Owner decision однозначно утверждает authoritative local RBAC без legacy
fallback и один versioned combined PDF без public appendix. Existing
`LOCAL-RBAC-AUTH-CONTRACT-001` задаёт current-snapshot authorization seam и
четыре точных результата. `ARTIFACT-STORE-001 v0.3` задаёт authorization-first
download, content-addressed integrity, bounded reads, immutable metadata и
redacted filesystem failures.

Текущий executable `PILOT-E2E-FLOW-001 v0.4`, однако, всё ещё нормативно
разрешает artifact types `order|appendix`, два HTML download, два metadata
records и два blobs. Current E2E run уже достигает artifact seam и падает на
`ArtifactNotFoundException` при попытке скачать отсутствующий appendix в
isolated artifact-fault setup. Это artifact-contract failure, не доказательство
RBAC failure.

## Required findings

### 1. RBAC change не содержит executable actor/route/permission matrix

Spec говорит, что FKR выполняет read/prepare/registration/open с exact grants,
но не фиксирует отдельными literals и expected outcomes полную матрицу:

- queue/card read → `objects.read`;
- prepare GET/POST и artifact download → exact утверждённое permission;
- registration → `assignment_order.confirm_registration`;
- opening → `installation.open`;
- construction-control actor → точный набор его reached routes;
- каждый cross-actor/cross-route denial до handler/store/process access.

Фраза `named roles per actor capability group` допускает несколько существенно
разных seeds. Нужно зафиксировать fictional IDs, active/activation state,
role assignments, exact permission rows, trusted local actor propagation и
табличную последовательность admission/denial для каждого reached public HTTP
seam. Нельзя выводить эту матрицу из будущей реализации или legacy capability
rows.

### 2. Revoke/audit contract RBAC fixture неоднозначен

Draft требует committed revoke, сохранения audit/history и cleanup, но не
определяет, является revoke test-owned fixture mutation или вызовом public
administrative seam. Текущий approved local-RBAC slice read-only и не утверждает
grant/revoke command. Gate 1 должен явно разрешить bounded test-admin mutation
между invocations либо назвать approved public seam и ожидаемый audit fact.
Прямой DELETE не может одновременно считаться доказательством несуществующей
административной audit semantics. Также нужны exact repeat snapshot и порядок
cleanup, при котором cleanup failure не скрывает primary verdict.

### 3. Combined-PDF change не устраняет нормативный конфликт с PILOT-E2E-FLOW

Proposal заявляет синхронизацию E2E/product/pilot contracts, но `Modified
Capabilities` содержит `Нет`, tasks не включают revision основного executable
`PILOT-E2E-FLOW-001`, а delta spec добавляет только новую verification
capability. Поэтому после предполагаемого Gate 1 одновременно действовали бы:

- owner/ARTIFACT-STORE/new delta: один `order`, `application/pdf`, appendix нет;
- approved PILOT-E2E-FLOW v0.4: `order|appendix`, два HTML artifacts/bytes/links.

Change должен явно supersede/update affected E2E capability и перечислить
синхронизируемые product/pilot executable contracts. Gate ordering: сначала
RBAC journey должен доказуемо достигнуть artifact boundary, затем combined-PDF
RED; PDF slice не может переписать authorization fixture.

### 4. Combined artifact acceptance недостаточно exact

Нужно зафиксировать public seam и однозначные observable contracts:

- exact artifact type (`order`), version/object correlation, filename policy,
  `application/pdf`, size/SHA-256/bytes cardinality `1`;
- independent decoded PDF oracle для обеих частей: обязательные markers/order,
  page/section boundary или иной falsifiable semantic transcript;
- отсутствие metadata/blob/link/type `appendix`, включая fresh reload;
- authorization literal/result и доказательство, что denial предшествует
  projection/store reads;
- exact distinction missing metadata/blob, integrity mismatch, EACCES/shard
  fault и HTTP/application mappings вместо `generic unavailable/not-found`;
  они должны согласоваться с ARTIFACT-STORE redaction rules;
- full pre/post process projection, event/artifact catalog and filesystem
  snapshot for fault, repeat, fresh connection and concurrent reads.

Concurrent downloads сами read-only: test должен доказывать zero new artifact
metadata, blobs, audit/domain events и отсутствие temporary files, а не только
успешные одинаковые responses.

### 5. Cleanup и dependency separation должны стать executable

Оба designs правильно требуют task-owned DB/users/sessions/artifact roots и
`finally`, но specs не фиксируют exact owned inventory и post-cleanup
observations. Нужны отдельные roots/prefixes для RBAC journey и PDF fault cases,
capture primary result до cleanup, restoration fault до reload, no-follow
filesystem cleanup и повторный residue check. RBAC change обязан закончиться
на явном downstream marker без изменения artifact assertions; PDF change
стартует только после RBAC GREEN/evidence.

## Required correction before owner approval

1. Добавить exact RBAC actor/route/permission/denial/revoke fixture contract.
2. Определить допустимый revoke mechanism и не заявлять несуществующий audit.
3. Явно изменить/supersede `PILOT-E2E-FLOW-001 v0.4` и связанные pilot/product
   artifact claims; отразить modified capability и tasks.
4. Сделать combined PDF oracle и failure mappings точными, включая cardinality,
   decoded semantics, reload/concurrency snapshots и отсутствие appendix.
5. Зафиксировать dependency order и executable cleanup inventory.

После исправлений требуется fresh independent Gate 1 rereview и только затем
отдельное owner approval exact hashes. Этот review не разрешает RED или
implementation ни одного change.

## Reviewed hashes

```text
cdb1dabf2dff353d2b708eb651af00f40ea22271f8900c93afa40b9885229c58  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
4f68930386dfb6c01ae6535c44c9dda4a5ba5f41db6333f9fd952e1b2521e934  openspec/changes/pilot-e2e-rbac-fixtures/design.md
f361d04d29a910f58b20f5990497e9e3697301c226cbf300a291d1e79e2a4f1d  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
c4d9dbadad2c99e1d9583a419ff2fe436dbbfe37461a0ffa78eabaaa82c9d919  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
072d35c93622974d5e029cd94898097a947582959170e973aa25c4ac5816edab  openspec/changes/pilot-e2e-combined-pdf/proposal.md
3d0901ff905be94d5f31c4d4235f550d43e9ce0606aef4f2a1a16dd23ba241cd  openspec/changes/pilot-e2e-combined-pdf/design.md
e9b81cb5a035ea84ec3d2839742f016fab3f4a5a0560c5497581e8cb158a31ff  openspec/changes/pilot-e2e-combined-pdf/tasks.md
bacac083d108bcadafea282eeb0c7acf73bbc37252c02661030178048364a24a  openspec/changes/pilot-e2e-combined-pdf/specs/verification/pilot-e2e-combined-pdf/spec.md
048615871f93d6232648659f6bce0f50b1bdd907194d31d1cd5dc8a1257fdf91  docs/operations/security-artifact-contract-owner-decision.md
9498bfca1001360f64d6a31dc5588956d8cb4021feb8515d1b017aff913cf1bc  specs/ARTIFACT-STORE-001.md
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
```

## Verification

```text
openspec validate pilot-e2e-rbac-fixtures --strict
Change 'pilot-e2e-rbac-fixtures' is valid

openspec validate pilot-e2e-combined-pdf --strict
Change 'pilot-e2e-combined-pdf' is valid

FMONITOR_TEST_DB_ADMIN_PASSWORD=<REDACTED> \
  php tests/InstallationProcess/pilot_e2e_flow_001_test.php
exit 255: ArtifactNotFoundException while stale fixture requests appendix
```
