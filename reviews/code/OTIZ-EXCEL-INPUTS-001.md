# OTIZ-EXCEL-INPUTS-001 — Gate 5 code review

- Date: 2026-09-14
- Reviewer: `review_inputs` (independent agent; authored none of the reviewed production code, specification, tests, or evidence)
- Implementation author: separate executor `implement_inputs`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194524Z-91b4fb39bf/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `719b1c81888252e0d199c94e58b0e3bfe0a3f563420cf246882a2c786032a1dc`, executable source digest `612923bd364f53de61c46374016d3c7e7be7a3768c3fda673265f42565f8970e`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194524Z-91b4fb39bf/snapshot`, patch SHA-256 `e2185a187f7473cd14a29806c3d2d7f20fff44ed759b53dc3f3360ef1eb7293c`
- Specification: `specs/OTIZ-EXCEL-INPUTS-001.md`
- Approved tests: `reviews/tests/OTIZ-EXCEL-INPUTS-001.md`, including the implementation-fixture delta approval on this exact source
- Verification: record `1789415080626953000-4f508594324e41f5a7ded7d9a111cc1c`, `php tests/Otiz/excel_inputs_001_test.php`, GREEN, `source_drift=false`
- Scope: the five `MariaDbNativePremiumInputs*` production files only. Certificate implementation, calculator, publication, payment, rapid-pilot compatibility, deployment, import and backfill are outside this verdict.

## Findings

1. **HIGH — migration classification is joined to the legacy object but not to the operational case it classifies.** `MariaDbNativePremiumInputs::forDate` joins `fm2_migration_classification_provenance` using `output_kind` and `legacy_object_id`, then accepts `native_candidate` or no joined row (`app/Otiz/MariaDbNativePremiumInputs.php:42-46`). The provenance schema owns classification by unique `(output_kind, output_id)`, and established consumers additionally bind `m.output_id=c.id`; `legacy_object_id` is only a non-unique secondary index. If a second operational case points at the same legacy object, a provenance row belonging to that other case can incorrectly exclude an otherwise unclassified native case, admit a differently classified case, or duplicate the returned row when multiple matching provenance records exist. This violates `specs/OTIZ-EXCEL-INPUTS-001.md:13`, which distinguishes classification state per native operational case. Correction: bind the join with `m.output_id=c.id` as well as `m.legacy_object_id=c.legacy_installation_object_id`, and add a focused adversarial witness containing two case IDs for one legacy object with provenance belonging to only one/different categories. Because this new test is required to catch the concrete defect, return through Gate 2 and independent Gate 3 correction review before Gate 5 rereview.

## Conformance and verification assessment

Apart from the finding above, the implementation follows the bounded contract. It reads the current raw plan, delegates certificate evidence to the public same-connection owner, validates applied composition and object-card hashes, resolves checklist events by accepted revision with both cutoffs, conserves attributed basis points, separates current PTO comparison from documentary progress cutoffs, emits canonical evidence, preserves caller transactions, reports blockers without constructing operands, and introduces no writer or rapid-pilot compatibility path. The five-file split keeps evidence, progress, documentary and value rules bounded around the existing public reader.

The exact-source focused test is GREEN and covers the declared matrix, but its provenance decoys use distinct legacy objects and therefore do not expose the cross-case join defect. Syntax checks for all five production files and both native-input test files and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

## Required changes

Bind migration provenance to the operational case ID, add the focused same-object/different-case regression witness, obtain correction Gate 3 approval, rerun the bounded exact-source checks, and resubmit Gate 5.

## Verdict

`CHANGES_REQUESTED`

The native input slice is not complete and must not proceed to final integration on this source. The owner decision excluding rapid-pilot compatibility is preserved; no compatibility work is requested.

---

## Gate 5 correction review — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T195317Z-82228810e5/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `913ba88363e3fbc2025324898b11aeac6488933376cddfb1fd483d85f01a982e`, executable source digest `dec5bea5981be3e0e0ff2cf49bca3ac1b56caa98f646464f6f0b110311fabf58`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T195317Z-82228810e5/snapshot`, patch SHA-256 `fca19328b89be6d3c59765f5c4645bddc3141e79d569473e946dc59aa35295cf`
- Verification plan SHA-256: `457b92882858b642dbca2cda7c16bb72e8db79dfc10c436c81bf41b05af415e3`
- Approved correction test: provenance-join regression recorded in `reviews/tests/OTIZ-EXCEL-INPUTS-001.md`

### Finding resolution

The sole Gate 5 finding is resolved. `MariaDbNativePremiumInputs::forDate` now joins migration provenance on `output_kind='operational_case'`, `m.output_id=c.id`, and the matching legacy object. Classification therefore belongs to the operational case identified by the provenance owner, while an unclassified native case remains admissible. The approved same-object/foreign-case witness keeps exact IDs `[4512]` for both foreign `active_candidate` and `native_candidate` rows, detecting both erroneous exclusion and duplicate admission.

No additional findings arise from the one-line production correction. The rest of the previously reviewed five-file implementation is unchanged in this bounded rereview. Owner-directed exclusion of rapid-pilot compatibility remains intact. The concurrently landed feedback migration 25 and the necessary later certificate migration-26 renumber are separate deltas and are not approved or rejected here.

Exact-source record `1789415542111456000-81516ad74b8e41258a9d61b55762d4d3` runs `php tests/Otiz/excel_inputs_001_test.php` GREEN with output `PASS OTIZ-EXCEL-INPUTS-001`, candidate source `913ba88363e3fbc2025324898b11aeac6488933376cddfb1fd483d85f01a982e`, executable source `dec5bea5981be3e0e0ff2cf49bca3ac1b56caa98f646464f6f0b110311fabf58`, and `source_drift=false`. The frozen snapshot restores to the same digests; PHP syntax and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN`.

### Final verdict

`APPROVED`

Gate 5 passes for `OTIZ-EXCEL-INPUTS-001` on the exact source above. This approval remains limited to the native input-reader slice and does not cover certificate migration renumbering, publication, settlement/payment, rapid-pilot compatibility, deployment, import or backfill.
