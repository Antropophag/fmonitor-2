# Independent final review — ACTUAL-START-FROM-ORDER-001

- Reviewer: `/root/assignment_start_final_review`; authored none of the reviewed specification, tests, implementation, or evidence.
- Review date: 2026-11-13.
- Base: `6664d5d85dc5e0812b7b4758457087a683f5644d`.
- Exact candidate source: `73675f16152a4ac63a70bac003d5ec08853b37179cc527c3ced7e650da12350e`.
- Exact executable source: `7a84a1b39842078fbccfad9924e7201c35ad97d660283cb68c9cf050db14ed91`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T101559Z-8f2dad5ded/package.json`; reconstructible snapshot is alongside it.
- Contract: `specs/ACTUAL-START-FROM-ORDER-001.md` and `openspec/changes/actual-start-from-assignment-order/`.
- Verdict: **APPROVED** for the owner-authorized independent final review.

## Findings and correction disposition

The first reviewed source, `0616fba2bda4a6b59fbd26345118135aad517a168ff7f5b9c3eb65005a0b8242`, was `CHANGES_REQUESTED` for two findings.

1. **P1 resolved:** `PreopeningResources::originalStart()` referred to the nonexistent `OriginalStartOwner` type, which would turn the active upload into a caught `TypeError` and HTTP 503. The corrected source declares the concrete returned `MariaDbOriginalStartOwner` type.
2. **P2 resolved:** the Yii upload controller acquired the PDF body before checking `installation.open`. It now performs the canonical capability check before `rawBody` access while retaining the composite owner's pre-write and transactional authorization checks. The real Yii request probe proves an upload-only actor receives 403 without `getRawBody()` and an allowed actor reaches the accepted path.

No remaining blocking contract, replay, concurrent-submission, authorization-race, subsequent-order, correction, migration/readiness/recovery, active-UI, architecture, or focused-test finding was identified. The immutable intent and canonical-revision checks prevent changed request semantics and cross-request date borrowing; retry after intent/original commit is idempotent; opening facts remain fixed while later orders and same-date corrections update only application state.

## Exact-source evidence reviewed

`/Users/antropophag/.local/share/fmonitor-2/actual-start-from-order-20261113/final-focused-2.log` records all ten focused checks passing with executable source `7a84a1b39842078fbccfad9924e7201c35ad97d660283cb68c9cf050db14ed91`: metadata, domain acceptance, concurrent retry, schema, canonical migration runner, adjacent legacy opening, readiness, schema frontier, real HTTP, and desktop/mobile browser behavior. It includes the new pre-body authorization probe.

`/Users/antropophag/.local/share/fmonitor-2/actual-start-from-order-20261113/final-recovery.log` records current public backup/restore passing with a nonempty schema-39 original-start intent. `final-architecture.log` records the HTTP global-call qualification and all eight architecture rules passing. The initial `final-focused.log` setup failure is retained as historical evidence and is not counted as GREEN.

## Limits of approval

This is the substantive exact-source independent final review required by the owner's emergency production-first route. It is not automated Gate 5 admission: earlier runs were not harness-native per-test records. Formal Gate 3 and full exact-source GitHub CI remain owner-deferred and `UNKNOWN`; the historical recovery fixture failure is not called GREEN. This review does not itself approve CI, merge, old-data deletion, or any broader production change outside the scoped deployment described in the delivery record.

Within those explicit limits, candidate source `73675f16152a4ac63a70bac003d5ec08853b37179cc527c3ced7e650da12350e` is **APPROVED** for the scoped production delivery authorized by the owner.

## PR293 integration correction — independent final review

- Reviewer: `/root/pr_test_closure`; authored none of the specification, tests, product implementation, fixture corrections, or evidence.
- Review base: `7835373b01066f11ed327b0fd751bb8dea4dc1f7`.
- Frozen patch SHA-256: `3389fa7a775a71263b4a05639f44f73d78fd5ce899f371fa3a574a31cfacf3b2`.
- Exact candidate source: `aeff81129c6f452b352e46849680ce1f29f05c0e92064c4af231adec9b526a30`.
- Exact executable source: `01ab2ae90c523c60b47d42f7439a3f0d04156b50f4416fb383f032f4b2756c9d`.
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T144115Z-b4acc6a8bc/package.json`.
- Verdict: **APPROVED** for the complete frozen PR293 integration-correction delta.

No unresolved findings remain. The earlier retrospective test-review finding is fixed by changing the current recovery manifest expectation from 105 to 106 tables. The bounded preimplementation finding is fixed by negative standalone-opening coverage for UUID versions 1, 2, 3 and 5 with an exact unchanged snapshot. The implementation widens UUID acceptance only for the persisted signed-start path after its immutable intent/revision/date binding; standalone confirmed opening remains UUIDv4-only. Passing the persisted flag through the lower opening owner preserves that boundary. Removing the composite `%PDF-` shortcut restores the original owner's `not_pdf` and `unsafe_pdf` classification, safe receipt and no-accepted-revision behavior.

The fixture changes migrate current upload flows to the public signed-upload seam: upload applies composition and opens immediately; correction preserves the initial opening tuple; partial opening retains the accepted original; exact replay, concurrent submission and lost-commit acknowledgement remain covered. Manual `open` and `open_confirmed` HTTP actions are now tested as retired with zero writes. Raw-original setup remains only where an unopened/upcoming or legacy read-model state is the explicit subject. Schema inventories add exactly the v39 intent table, recovery expects 106 current tables, and the changed asset contract retains exact hashes. I found no weakened material assertion or deleted acceptance without an equivalent current-seam check.

Retained evidence includes the complete initial 60-failure inventory and all intermediate failures. The final focused logs cover transport receipts/roles/bytes, browser behavior, lineage and cutover, current recovery, architecture/HTTP qualification, and the production nginx/FPM browser lifecycle. `uuid-final.log` records exit 0 for the persisted UUID v1/v2/v3/v5 and standalone-negative matrix at executable source `01ab2ae90c523c60b47d42f7439a3f0d04156b50f4416fb383f032f4b2756c9d`. `git diff --check` is clean.

This review is independent substantive review, not harness automated admission: the historical focused evidence was retained as complete logs rather than native per-command reviewer records. A new exact-source full GitHub CI run is still pending and is not called GREEN here. This verdict does not approve deployment, data reset, or any source beyond the frozen PR293 correction delta.

## Short-payload transport correction — independent final delta review

- Reviewer: `/root/pr_test_closure`; authored none of this delta.
- Review base: `6dae29513f1f9508c0448632cfd8175e686d0e39`.
- Frozen patch SHA-256: `1f4fed025d01cb415b7068723254fc7da78ffbd3fe61d3aee8fabf5fef5ea108`.
- Exact candidate source: `df57165afffe4319f52966a4263db309cd10dc60d13925028d98ac1b7212c4be`.
- Focused executable source: `9da95ea58db442198a44f714723a30ced1202cef5c67ad576c435af3f6b83a34`.
- Verdict: **APPROVED** for this frozen three-file delta; no findings.

The correction preserves every complete payload, including empty and one-byte input, for classification by the original owner. Technical stream failure, an empty non-EOF chunk, and payloads over 20 MiB still return the composite technical-failure sentinel. Payload SHA-256, size and the common semantic fingerprint are computed before replay resolution for every complete payload. An existing accepted request therefore rejects a changed one-byte payload as `request_id_conflict` with the exact facts unchanged.

New short payloads skip only insertion into `fm2_assignment_order_original_starts`, whose schema requires a byte size of at least five, and continue through the original owner to its established `not_pdf` receipt. No opening can follow because only accepted or replayed original results proceed to canonical start lookup. The existing transport corpus retains `unsafe_pdf`, ordinary `not_pdf`, exact replay, receipt shape, authorization, persistence failure and the exact 20 MiB boundary; the new cases add one-byte `not_pdf` and changed-payload replay protection.

Retained RED evidence records the former 503 misclassification and the rejected first fix that allowed an accepted request to borrow a changed short payload. `short-pdf-final.log` records the complete transport test passing at executable source `9da95ea58db442198a44f714723a30ced1202cef5c67ad576c435af3f6b83a34`. `git diff --check` is clean. Exact-source full GitHub CI remains required and is not called GREEN by this delta review.

## PR292 + PR293 stack — integration-only review

- Reviewer: `/root/pr_test_closure`; no authorship in either feature or the integration resolution.
- Approved parents: PR293 `3ae555d73be6820634c6d546d4893ca151d4ee83` and PR292 `0c5c91470fab55b3ad9383ae04ea77c1aa60003c`.
- Prepared combined candidate source before this review record: `d15f845c79ebae3d52a9242329c6c481b5cdd3a96e0ded6ab2af038302be138c`.
- Combined executable source: `2148cc5830b3bb530949b5f3c6eab5ce384ddde3a61983a21bc09c90aa455b09`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T145958Z-fd9b2515b1/package.json`.
- Verdict: **APPROVED** for the integration composition only; no findings.

Every merged product and test blob is byte-identical to its approved parent. There was no product or test conflict. `tools/verification/suites.tsv` is the exact semantic union: it retains the PR293 original-start HTTP, browser and metadata registrations and adds the PR292 month-session browser registration; inventory validation passed. The only content conflict, `docs/operations/current-delivery-goal.md`, keeps the current two-PR merge objective and both complete historical feature-goal sections without changing either contract or claiming CI success.

The combined verification input includes both contracts, all PR292 planned paths, and the MONTH-SESSION-001 A1–A5 mapping alongside the unchanged ACTUAL-START-FROM-ORDER-001 mappings. The original-start delivery record adds only the stacked-head/integration explanation. `missing_tests` is empty and `git diff --check` is clean.

`combined-browser.log` records both `yii2_month_session_001_test.php` and `yii2_original_start_browser_001_test.php` passing together at executable source `2148cc5830b3bb530949b5f3c6eab5ce384ddde3a61983a21bc09c90aa455b09`. This confirms the composed session configuration and signed-start browser fixtures coexist. This review does not repeat either already-approved feature review, claim automated admission, or call the required exact-source full GitHub CI GREEN.
