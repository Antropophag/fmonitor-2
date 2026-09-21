# Code review: OBJECT-DETAILS-EDITING-001

- Reviewer: independent joint Gate 3/Gate 5 reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T171446Z-02427b7fd8/snapshot/source.patch`, SHA-256 `148f11d26567fd7496ae6cb34fa48a2bd8b7624a09aae92f806c6bc54c60125a` (candidate `5be9d45e49f1ce2b00ea0379be3c77bf07bf5ef6227faaa5ec832f4d78ef4e57`)
- Specification: `specs/OBJECT-DETAILS-EDITING-001.md`
- Earlier review: Gate 3 v3 is `CHANGES_REQUESTED`; prior v1/v2 findings are explicitly disposed there and remain history
- Verification: six mapped exact-source tests GREEN; relevant reported consumer/schema/architecture checks GREEN; `pilot_case_import_001_test.php` local result `UNKNOWN` from DB credential mismatch and remains required in CI; authoritative exact-source CI not yet GREEN
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — object-scope authorization is not implemented.** `app/InstallationProcess/ObjectDetailsEditApplication.php:13,21` proves only that the actor is active, has a qualifying business role/capability, and that the object exists. The query contains no actor-to-object scope relation, so any qualifying actor can edit every object. `ObjectCardController::actionDetails()` likewise checks global read capability before delegating. This violates A5 and creates a horizontal-authorization defect. Correction: enforce the repository's canonical object-scope policy inside the sole application owner before mutation and test denial before existence disclosure with zero facts.

2. **BLOCKER — the Bitrix consumer required by A10/A11 still reads the old factory number.** No Bitrix reader appears in the changed source, while the contract explicitly names `MariaDbBitrixOrderDocumentLinks`/technical-document lookup. Editing `zavnumber` therefore does not reach that consumer. Correction: route its lookup through the effective-value seam while preserving already issued documents and add a public consumer regression.

3. **BLOCKER — migration does not validate compatible or incompatible existing tables.** `ObjectDetailsEditingSchemaMigration.php:15-18` treats any same-named table as acceptable and reports no upgrade; malformed columns, constraints, indexes, collation or triggers silently pass. This violates A15's exact-upgrade/compatible-partial/incompatible fail-closed contract and may admit runtime against corrupt schema. Correction: use the canonical schema inspection/locking lifecycle, validate exact definitions, add only approved compatible pieces, reject incompatible states without destructive repair, and cover concurrent runners.

4. **BLOCKER — event history omits required case identity and incomplete metadata.** `ObjectDetailsEditingSchemaMigration.php:13` and `ObjectDetailsEditApplication.php:18` store `object_id` but no installation-case identity. Changes at line 15 store labels and raw/display, but no unit/reference label and do not preserve coherent reference display snapshots. This violates section 5 and makes immutable history insufficiently self-contained. Correction: persist case identity and complete typed/raw/display/unit/reference snapshots atomically; test immutability after source/reference/user changes.

5. **BLOCKER — reference values are not validated against a reference source.** `ObjectDetailsFieldRegistry.php:27` accepts any positive numeric string for `pittype`, `pitmaterial`, and `lift_type`; it cannot guarantee valid code/value or coherent raw/display as required. The UI renders plain text inputs rather than `shlz-ui` select exports (`Views/object-card.php:136`). Correction: bind validation and rendering to the canonical reference catalogue, fail closed for unknown values, and snapshot both code and display.

6. **HIGH — explicit nullable clearing is lost in queue/search projection.** `MariaDbYiiObjectQueue.php:65,124` uses `COALESCE(JSON_UNQUOTE(JSON_EXTRACT(...)), legacy)`. When an explicit nullable override is JSON `null`, this falls back to the legacy value instead of clearing it, so card/effective reader and queue disagree. Correction: distinguish key absence with `JSON_CONTAINS_PATH`; when present, honor JSON null as the effective cleared value in display/search/filter.

7. **HIGH — shared chronology pagination is not implemented as specified.** `ObjectCardController.php:113` paginates only detail events and prepends them to the same initial process-event slice on every page. Earlier process events beyond the existing bound remain inaccessible, and subsequent detail pages repeat process events rather than forming one deterministic compound chronology. Correction: implement one compound cursor/order across process and detail events and verify no omissions/duplicates beyond eight.

8. **HIGH — replay re-authorizes nothing and may disclose a prior successful outcome.** `ObjectDetailsEditApplication.php:11-13` resolves an existing request and returns the stored outcome before checking current active identity, capability, or object scope. A now-inactive/revoked actor presenting the request ID can receive `replayed` outcome/event identity. The contract says owner checks active identity, exact capability and scope before mutation/replay semantics. Correction: authorize trusted actor/scope before replay response, while preserving idempotency and zero writes.

9. **HIGH — migration grants business capability as a side effect.** `ObjectDetailsEditingSchemaMigration.php:16` inserts `objects.details.edit` for every currently active role with code `fkr_operator` or `manager`. This couples schema application to mutable authorization state and silently re-grants permissions on repeat migration, defeating explicit role administration/revocation. Correction: place initial permission seeding in the approved authorization provisioning lifecycle with explicit idempotent ownership, not every schema migration run.

10. **MEDIUM — controller duplicates the effective projection and opens a second raw mysqli connection.** `ObjectCardController.php:99-114` reimplements overlays/history beside `MariaDbEffectiveObjectDetails`/card readers, while `actionDetails()` creates its own environment-bound connection. This raises drift and transaction/configuration risk and already contributes to inconsistent null semantics. Correction: inject one public application/projection service through Yii composition and keep SQL/facts out of the controller.

11. **MEDIUM — evidence is not yet sufficient for PR-ready.** The local import obligation is `UNKNOWN`, and authoritative exact-source CI has not established GREEN. Environment mismatch may explain the local result, but UNKNOWN is not approval. Correction: run the selected existing CI consumer on the corrected exact source and require the complete failed-job/`REGRESSION_FAILURE` inventory if it fails.

## Prior Gate 3 finding impact

The v1/v2 open findings are not merely test-quality concerns: missing tests allowed findings 1-9 above to pass the mapped suite. Quality Graph ownership and real Playwright execution remain valid fixed improvements, but they do not compensate for the open authorization, consumer, schema, history and projection defects.

## Required changes

Fix all BLOCKER/HIGH findings, add corresponding tests and restart at Gate 2/Gate 3 because test changes are required. Regenerate the plan, obtain independent test approval, run exact-source CI including import, then return the corrected snapshot for final review. Candidate is not PR-ready.
