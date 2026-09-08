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

## V3 candidate revision 2 — independently executable verifier seam

This appendix supersedes the gate-method paragraphs above that treated reaching
a downstream E2E failure as focused GREEN. That is insufficient. The existing
full E2E must still run unskipped; its status remains separate and mandatory.

New test-support public seam (no application API):
`FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle::matches(string $html): bool`
in `tests/Support/ProtectedE2eAdmissionOracle.php`. It returns true precisely for
the admission representation specified above, false otherwise, without DB,
network, filesystem mutations or output. Parsing uses DOM with network disabled;
parser diagnostics do not escape. Expected fixture facts are fixed by this
candidate, never passed from actual response/renderer output. This deliberately
fixture-specific oracle is not a general HTML validation library.

Public executable regression:
`php tests/Verification/protected_e2e_admission_oracle_001_test.php`.
The independently authored test first asserts the public oracle exists/can be
called, then exercises the literal matrix below and exits0 only if every result
matches. Missing oracle produces an explicit intended assertion failure, not an
uncaught include/class error or environment failure. That is RED for the missing
verifier behavior only; the existing stale E2E failure remains separate evidence.
No claim of missing production behavior follows. Test then undergoes independent
Gate3 before implementing the oracle. Implement minimal oracle, obtain focused
GREEN, independently review its exact code and test at Gate5. The approved test
expectations must not change to obtain GREEN.

Independent positive literal HTML (UTF-8):

```html
<!doctype html><html lang="ru"><body><main id="main-content"><ul><li><a href="/pilot/objects/4512">4512</a><span>77-000123</span><span>Москва, ул. Примерная, д. 10</span><span>Подъезд 2</span><span>2026-10-05 — 2026-12-20</span></li></ul></main></body></html>
```

This is specification data, not captured renderer output. Expected true for
that literal and the same literal replacing ul with ol. Expected false for each
independently derived one-change negative:

- remove the sole object anchor;
- duplicate the complete object li;
- change href only to /pilot/objects/4513;
- change anchor text only to 4513;
- replace object li tags with div tags;
- change registration only to 77-000124;
- change address house only to д. 11;
- change entrance only to Подъезд 3;
- change start only to 2026-10-06;
- change finish only to 2026-12-21;
- append an empty table inside main;
- append a div with class tokens x shlz-table-wrap y inside main;
- move correct anchor to a navigation list outside main and remove it in main.

Membership is scoped to sole main#main-content, exactly one matching li with
ul/ol parent and one canonical fixture anchor in main. Facts must occur inside
that same item. Canonical link text and fact text use DOM text with whitespace
collapsed; registration/address/date literals are matched as separate values,
entrance as Подъезд 2, so 22 or 20 cannot satisfy entrance2. Extra navigation li
outside main are allowed. Table and shlz-table-wrap forbidden inside main.
A second main with that same id is rejected. Add that explicit negative too.

After oracle Gate3/GREEN, prepare the unapplied protected patch that replaces
all three stale assertions with calls to this exact reviewed oracle on each
actual response body. Preserve real status checks and all remaining protected
bytes. The protected patch receives a distinct independent Gate3 against this
owner-approved amendment and fresh old-fixture mismatch evidence, before it is
applied. Full E2E still executes entirely; oracle focused GREEN cannot convert
its downstream failures to success. Independent Gate5 covers integration and
exact unchanged response/RBAC/cleanup boundaries; full verification remains a
separate launch blocker until literally GREEN. Any inability to establish these
gates leaves the protected patch unapplied, without waiver or skip.

## Candidate revision 3 — exact fact text projection

This appendix replaces the ambiguous “separate values” matching sentence.
Within the already identified object li, visit descendant text nodes in document
order. For each node, collapse Unicode whitespace to one ASCII space and trim
ASCII spaces. Drop empty runs and join remaining runs with one ASCII space.
This is the fixed test-side fact projection; no required span/div/strong
structure, CSS classes or source renderer methods are used. Anchor text is
normalized by the same rule. This projection deliberately does not claim a
browser layout/visibility oracle.

Each expected literal registration, address, start date, finish date and
`Подъезд 2` must occur contiguously in the projected text, with neither immediate
neighbor (if present) being a Unicode letter or number. Literal punctuation is
not a regex wildcard. Thus house10 cannot match house100; entrance2 cannot
match entrance20 or22. Unicode matching uses UTF-8; invalid input is false.
No fact may be supplied by a sibling li or navigation outside main.

Additional fixed positive, independently specified here (not captured output):

```html
<!doctype html><html lang="ru"><body><nav><ul><li>Навигация</li></ul></nav><main id="main-content"><ul><li><div><a href="/pilot/objects/4512">4512</a><strong>77-000123</strong></div><div>Москва, ул. Примерная, д. 10 · Подъезд 2</div><div>2026-10-05 — 2026-12-20</div></li></ul></main></body></html>
```

Its normalized text is exactly:
`4512 77-000123 Москва, ул. Примерная, д. 10 · Подъезд 2 2026-10-05 — 2026-12-20`.
Expected true. Expected false for each independent replacement in this positive:
`Подъезд 2`→`Подъезд 22`; `Подъезд 2`→`Подъезд 20`; `д. 10`→`д. 100`.
These additions supplement every earlier matrix row, never replace or skip one.
