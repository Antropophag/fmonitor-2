# Code review: TERMINOLOGY-OBJECT-001

- Reviewer: `Codex agent /root/terminology_object_001_code_review` (independent; did not author the specification, test migration, implementation, or corrective changes)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/TERMINOLOGY-OBJECT-001.md`](../../specs/TERMINOLOGY-OBJECT-001.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/TERMINOLOGY-OBJECT-001.md`](../tests/TERMINOLOGY-OBJECT-001.md), verdict `APPROVED`
- Initial review: `CHANGES_REQUESTED`; four terminology/completeness findings were returned for correction
- Repeat-review verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2` after the corrective changes:

```text
for test_file in tests/InstallationProcess/*_test.php; do
  php -d display_errors=1 -d error_reporting=E_ALL "$test_file" || exit
done
find app tests -name '*.php' -type f -print0 | xargs -0 -n1 php -l
git diff --check
sed -n '/<script>/,/<\/script>/p' docs/fmonitor-2-flow-prototype.html | sed '1d;$d' | node --check -
rg -n -i 'заказ' . --glob '!reviews/**' --glob '!specs/TERMINOLOGY-OBJECT-001.md' --glob '!.git/**'
rg -n 'prepareOrder|getOrderProcess|\borderId\b|\borderSnapshot\b|\borderVersion\b|\borderDate\b|ORDER_NOT_FOUND|ORDER_REQUIRED_DATA_MISSING|needs_order|(^|[^a-z_])order_prepared|(^|[^a-z_])prepare_order' . --glob '!reviews/**' --glob '!specs/TERMINOLOGY-OBJECT-001.md' --glob '!.git/**'
rg -n '\$[A-Za-z_]*[Oo]rder[A-Za-z_]*|\.order\b|\border data\b|\border snapshot\b|\border date\b|\borders?\b' app tests docs specs PRODUCT.md CONTEXT.md --glob '!reviews/**' --glob '!specs/TERMINOLOGY-OBJECT-001.md'
rg -n -i 'состав.{0,35}(или|/).?инженер|инженер.{0,35}(или|/).?состав|состав и инженер|состава, инженера' PRODUCT.md CONTEXT.md docs specs app tests --glob '!reviews/**' --glob '!specs/TERMINOLOGY-OBJECT-001.md'
rg -n -i 'заводск' PRODUCT.md CONTEXT.md docs specs app tests --glob '!reviews/**'
```

Observed results:

- all 16 `tests/InstallationProcess/*_test.php` files pass, including `PASS TERMINOLOGY-OBJECT-001 public interface`;
- every PHP file under `app/` and `tests/` passes `php -l`;
- `git diff --check` exits `0` with no output;
- the prototype's extracted JavaScript passes `node --check -`;
- the public production class exposes `prepareAssignmentOrder(...)` and `getInstallationObjectProcess(...)`; the dedicated interface test proves `prepareOrder(...)` and `getOrderProcess(...)` are absent rather than retained as compatibility aliases;
- old projection, error, process-state, task, and object-event names do not remain outside the dedicated absence test and the normative migration specification;
- current user-facing occurrences of `zavnumber` use `Заводской номер лифта`.

## Repeat-review findings

- **Prototype behavior restored:** `initialState` and the `PREPARE`, `REGISTER`, `OPEN`, and `INSPECT` reducer branches consistently use `installationObject`. No `.order` state access remains, and the extracted script is syntactically valid.
- **Composition language corrected:** the primary specification now identifies `Состав конкретной версии` as the source-of-truth fact and says that correction of the composition or period creates a new version. It no longer treats the engineer as an entity alongside the composition. Remaining matches occur only in explicit glossary/prohibition statements that explain the canonical rule.
- **Object and document meanings are unambiguous:** production now uses `$requiredInstallationObjectFields` and `$installationObjectDataViolations`. Specifications 003–005 distinguish `installation object data`/`installation object snapshot` from `assignment order date`. Preserved `AssignmentOrder`, `assignment_order_*`, assignment-order SQL columns, and test artifact names refer to the распоряжение as allowed by sections 2 and 4.
- **Demo import language corrected:** batch notes and completion output say `installation objects`; the invalid-source-row exception identifies a `Legacy installation object`. Explicit legacy schema identifiers such as `legacy_order_id` remain unchanged as required for source traceability.
- **Repository language:** Russian `заказ` matches outside the migration specification and historical reviews are limited to explicit statements that the word is forbidden (`CONTEXT.md`, primary spec, handoff). They do not name an object instance. The remaining English `order` matches are stable `ORDER-PREPARE-*` identifiers, assignment-order/document artifacts, ordering language (`input order`, SQL `ORDER BY`), allowed external legacy names, and the normative unchanged artifact bytes/hash keys.
- **Behavior preservation:** all previously approved authorization, required-composition, audit, required-object-data, successful preparation, installer-eligibility, and engineer-eligibility tests remain green. Artifact bytes and SHA-256 expectations are unchanged as explicitly required by the terminology specification.
- **Audit/history/security boundaries:** the implementation changes terminology at the existing public command/query seam without adding a second entry point, mutating historical events, broadening permissions, exposing identity in rejection audit, or changing append-only process behavior.
- **Test sensitivity:** the interface test rejects either old method and requires both replacements. The 15 behavioral tests invoke the replacements and assert the renamed state, task, projection, date/version, violations, events, audit, snapshots, and immutable output, so a partial rename or compatibility alias cannot satisfy the corpus.

## Required changes

None for `TERMINOLOGY-OBJECT-001` version `0.1`.

Gate 5 is approved. The approval covers the repository-wide terminology migration and preserved behavior at the currently implemented in-memory public seam; it does not approve the draft behavior of `ORDER-PREPARE-005` or production DB/HTTP/UI wiring.
