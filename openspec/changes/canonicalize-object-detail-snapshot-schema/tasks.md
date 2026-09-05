## 1. Executable specification and approval

- [x] 1.1 Проверить фактический runner catalogue и 25/26-byte pre-DB boundary перед назначением migration version. На 2026-09-05 registry содержит 1–11, draft предлагает candidate 12 без reservation/registration. Independent dependency review разрешает data-free schema drafting до importer GREEN; `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` по-прежнему блокирует acceptance claims/изменение его DML, но не подготовку exact schema spec. Verification: свежие registry hashes, independent Gate 1 scope и version disposition сохранены до RED.
- [x] 1.2 Write the approved executable schema specification for the exact `fm2_pilot_object_details` and `fm2_pilot_object_detail_quarantine` family, including clean creation, repeat, both partial-recovery directions, family-wide conflict preflight, collation/prefix boundaries, preservation, and DDL-free importer expectations; verify the owner approval is recorded before Gate 2 begins.
- [x] 1.3 Record the resolved GRILL-004 synthetic/native and no-personal-data policy from `docs/operations/test-user-data-reset-decision.md`, keep literal fixture/object-detail population in separately gated `seed-test-user-fixtures`, and verify no fixture rows or production import are embedded in this data-free change.

## 2. RED and independent test review

- [x] 2.0 После Gate 1 доказать deterministic partial-CREATE failure, final
  verification failure и two-worker serialization/timeout через approved
  verification composition и isolated DB privileges; verification: bounded
  IPC/cleanup, qualifying RED, no production runtime selector.
  Evidence: independent requirement accounting
  `docs/operations/object-detail-schema-task-2-0-evidence-accounting-2026-09-05.md`
  связывает forward held-lock/observer/native-false Gates с каждым behavior;
  late direct DDL-denial/two-creator tests остаются supplementary retrospective
  coverage, не переписанной forward history. Importer/integration не включены.

- [x] 2.1 Add MariaDB RED tests derived only from the approved executable specification for clean creation, populated repeat, both exact-compatible partial states, incompatible member/decoy isolation, collation, composed prefix 25/26 boundaries, and existing-row preservation; verify overlong/invalid prefix rejection occurs before DB connection/access and all failures are assertion failures rather than environment/setup failures. Evidence: combined schema-engine review `07f58c7dd10700b3a951f792e94b972839dfe876`; importer and integration tasks remain separate.
- [ ] 2.2 Add RED characterization proving the importer performs no DDL, succeeds with exact precreated tables under a DDL-denied principal, and fails closed before source mutation when either table is absent or incompatible; verify existing six-field extraction, immutable/hash-repeat, source-change conflict, quarantine, and detail/quarantine coexistence semantics remain unchanged.
- [ ] 2.3 Assign a fresh independent test reviewer, record the review under `reviews/tests/`, resolve every finding without implementation-derived expected values, and verify Gate 3 approval is explicit before production code changes.

## 3. Minimal GREEN implementation

- [x] 3.1 Implement and register the data-free canonical migration with family-wide preflight and exact-compatible partial recovery; verify all reviewed migration RED tests turn GREEN without schema or data redesign. Evidence: combined schema-engine review `07f58c7dd10700b3a951f792e94b972839dfe876`; importer and integration tasks remain separate.
- [ ] 3.2 Remove both runtime `CREATE TABLE IF NOT EXISTS` statements from the importer and add the exact fail-closed schema precondition; verify the reviewed DDL-denied importer characterization turns GREEN and no consumer gains schema ownership.
- [ ] 3.3 Tighten the architecture ratchet to reject object-detail-family DDL outside canonical migrations and reduce the runtime-DDL baseline only for statements actually removed; verify `make architecture-check` passes and a targeted forbidden-DDL fixture is rejected.

## 4. Integration, regression, and Done

Supporting fixture contracts после landed v12:
`CANONICAL-V12-CONSUMER-FIXTURES-001` (11 exact patch targets, Gates1/3/5
approved; final11/11 focused PASS на 361ea4d) и отдельный
`HARNESS-OTIZ-CANONICAL-V12-001` (exact prepared predecessor, Gates1/3/5 approved
на 26e6cda). Reviews: `reviews/code/CANONICAL-V12-CONSUMER-FIXTURES-001-v1.md`
и `reviews/code/HARNESS-OTIZ-CANONICAL-V12-001-v1.md`.
Они входят в работу 4.1/4.2 и не завершают parent task по отдельности. Protected
E2E и importer serial Gate 1 остаются за своими gates; shared catalog defaults
не меняются. Scope/evidence: `docs/operations/canonical-v12-consumer-fixture-scope-correction-2026-09-05.md`.

- [ ] 4.1 Run the object-detail import characterization and consumer checks against a clean canonical database, including a source-free empty deployment with intentionally absent evidence; verify the contour deploys without external source access and consumers fail closed rather than fabricating premium inputs.
- [ ] 4.2 Run fresh reset/migration, database tests, characterization tests, architecture checks, golden journey, and `make verify`; verify any failures are classified as environment/setup, expected RED, known baseline regression, or new regression, with no unclassified failure.
- [ ] 4.3 Assign a different fresh independent code reviewer, record the review under `reviews/code/`, resolve every finding, and verify Gate 5 approval covers canonical ownership, restartability, preservation, DDL-free importer/consumers, and the absence of hidden population or semantic redesign.
- [ ] 4.4 Mark the slice Done only when the approved spec, reviewed RED evidence, minimal GREEN, rapid-pilot data-only adapter, architecture and regression evidence, and both independent approvals are committed or otherwise durably recorded; verify object-detail population remains a separately gated `seed-test-user-fixtures`/behavior slice under the approved GRILL-004 policy.
