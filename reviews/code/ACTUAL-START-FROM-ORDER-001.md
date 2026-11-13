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
