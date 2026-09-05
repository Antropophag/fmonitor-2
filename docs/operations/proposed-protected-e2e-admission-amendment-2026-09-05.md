# Proposed protected E2E admission amendment

Identifier: PILOT-E2E-RBAC-FIXTURES-001 admission amendment v3 candidate.
Status: DRAFT / INDEPENDENT TECHNICAL REVIEW AND OWNER APPROVAL REQUIRED.
No protected spec or test is changed by this proposal.

## Простыми словами

Проверка допуска к списку ищет объект внутри таблицы, хотя утверждённый экран
обязан использовать список. Предлагается исправить только эти ожидания,
сохранив доказательства RBAC, отсутствия мутаций и полный дальнейший запуск.
Это не утверждение устаревшего сценария ручной регистрации распоряжения.

## Authority and exact scope

This candidate supersedes only the representation expected by both main
actor18 GET /pilot/objects observations in the protected fixture. It inherits
PILOT-UI-SHELL-001 section 5 and PILOT-OBJECT-LIST-001. Existing owner-approved
RBAC v2 remains authoritative for identity, grants, denial, isolation, snapshots,
revoke/repeat and cleanup. No production/UI change is proposed.

Protected input at candidate creation:
`tests/InstallationProcess/pilot_e2e_flow_001_test.php`, SHA256
`a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`.
Current conflicting expectations are the initial tbody/tr link, six table
headings, and repeated tbody/tr link. This amendment covers all three together.

## Exact observable expectation

The real configured HTTP response for actor18 remains status200. Within
main#main-content, the fictional object 4512 occurs in exactly one semantic li
whose parent is ul or ol. That item contains exactly one anchor with literal
href /pilot/objects/4512 and normalized visible text 4512. No second such anchor
exists in main. Both initial admission and unchanged-state repeat independently
prove this contract. Main contains no native table and no element with class
token shlz-table-wrap. Table headings are neither required nor an allowed
alternative. Shell/navigation list items are outside the object collection and
cannot satisfy object membership.

The same item retains fixed inherited fixture facts: registration 77-000123,
address Москва, ул. Примерная, д. 10, entrance 2, and planned date values
2026-10-05 and 2026-12-20. Expected facts come from the pre-existing fixture
and approved list contract, never from rendered output. No process-state or
next-action columns are added to the collection contract.

All existing actor19/missing-ID/authority-matrix/revoke/negative-principal
sentinels, before/after full snapshots, main-grant equality, transport equality,
redaction and attempt-all cleanup requirements remain. No early return, skip,
catch-and-success or allowed exit-code list is introduced. The full existing
E2E executes beyond the admission boundary and retains its failures.

## Gates: fixture correction, no product change

After technical review and exact-hash owner approval, preserve a fresh execution
of the unchanged protected verifier showing status200 followed by the stale
admission assertion expected1/actual0. Separately demonstrate from the real HTTP
response that the exact inherited semantic-list contract holds. This is intended
fixture/oracle mismatch evidence; it is NOT a claimed missing-production RED.

Prepare an unapplied exact assertion-only patch. Independent Gate3 must assess
both that mismatch evidence and the patch against the approved representation,
including sensitivity to missing/duplicate/wrong-object links and forbidden table.
Sensitivity evidence uses fictional HTML as test-only oracle input and does not
substitute for the real HTTP acceptance. No production output supplies expected
values, and no application test selector or public route is added.

The unchanged verifier remains in place until Gate3 APPROVED. Apply only that
reviewed patch, repeat the complete protected verifier, and record whether all
admission/authority assertions pass before the first downstream failure. No
whole-E2E GREEN is claimed from reaching that boundary. Retain raw exit/failure
and full make verify status. A fresh independent Gate5 reviews the exact patch,
scoped admission evidence and all downstream failures. If Gate3 cannot accept
this fixture-correction RED methodology under the mandatory delivery process,
implementation stays blocked; this proposal grants no waiver.

## Downstream and Done boundaries

Manual registration/registered/open predecessor assertions remain legacy
characterization and cannot prove target readiness. Combined-PDF expectations
retain their separate authority. Original-first selection, direct/optional-template
upload, immutable revisions, read grants, opening from applicable original and
real public golden path need their own coherent approved contracts and delivery.
No CI or integration permission follows from this amendment: first full literal
VERIFY_OK at exact SHA remains mandatory. Failures are never converted to skips.

This package asks the owner only to approve this exact protected-fixture
representation correction after technical review. It does not ask to change
product policy, weaken gates, approve the remaining legacy journey or publish.
