# Selection command v0.3 — independent executable-draft readiness review

Date: 2026-09-05. Reviewer: `/root/selection_v03_contract_review`.

Verdict: **CHANGES_REQUIRED**.

Reviewed commit: `0132b20bf5c8ea01eeee053f9a20f5ee1f32b8ad` (`spec: define selection command and replay contract`). The worktree was clean and `HEAD` equalled the reviewed commit before this append-only review was added.

Reviewed artifact hashes:

- `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`: `2b30da75a41dc894d43d85a53e8f06979775787f447eaf1b3f82b9675fbe84b9`
- `openspec/changes/select-assignment-order-composition-without-template/proposal.md`: `495b951825e01fba1334e673de224059e9d6b8a73b9689db037a7cb51f26cd16`
- `openspec/changes/select-assignment-order-composition-without-template/design.md`: `40fe549e883c68589a6abf64e19e9f7f741f30404e45e9d972cb5178fe9313b1`
- `openspec/changes/select-assignment-order-composition-without-template/tasks.md`: `261b7d8f9e6ee501cd5d9efb48126ac3446a5d8e79fc35d07bae8d12694fac1a`
- delta spec `specs/pilot/assignment-order-composition-selection/spec.md`: `2670122db8a36f35d4ef4932c3498f5c7e9b310f6a103e4ddbb11534f59d97d7`
- author record `docs/operations/selection-command-contract-v03-2026-09-05.md`: `771b67fb83308d120c8bc218e5fe45377e688f58f9371d1601a0f857c2ffc8c3`

## Prioritized findings

### P0 — the draft cannot persist an original-compatible order without an unresolved invented date

Sections 3, 6 and 7 correctly say `selectionDate` is neither original `documentDate` nor optional-template date and defer physical `order_date` compatibility to task 1.3. However, the approved original reader derives composition only from a physical `fm2_assignment_orders` row whose mandatory `order_date` and member `valid_from`/`valid_to` pass its exact predicates. There is no additive schema or alternate reader contract in this draft. Therefore the promised successful result—an `assignmentOrderId` immediately usable by direct original upload—has no specified legal persisted representation. Writing `selectionDate` into `order_date` would manufacture a template date and change the meaning consumed by the existing original contract; omitting it cannot satisfy current storage/reader constraints. Gate 1 needs one concrete, end-to-end disposition covering physical fields, member dates, original lookup, and migration/version. Until then RED cannot assert the advertised handoff without choosing behavior outside the spec.

### P0 — “current selection” and “effective/applicable order” lack an exact public source contract

The NEW_ORDER/REPLACE_PENDING rules are directionally distinct: REPLACE_PENDING replaces only an unsigned current selection; NEW_ORDER may create a prospective selection after an accepted order. But the draft never defines the exact predicate or read model that determines (a) latest selection revision, (b) whether that exact selection has an accepted original, and (c) which order remains applicable/effective for directory and inspection projections. It also leaves the public observation API and effective-projection owner to task 1.3. This is material, not merely persistence detail: with accepted A plus pending B, a second NEW_ORDER must conflict while REPLACE_PENDING targets B, whereas directory/inspection must continue to expose A. Exact source identity, tie/concurrency behavior, and projection observation must be normative before Gate 1.

### P1 — the advertised exact typed API is not exact or typed enough for independent tests

The PHP block declares typed command mode, but result exposes only `toArray(): array`; no status/reason enums, accessors, concrete key value casing/backing strings, or construction guarantees are declared. The prose uses uppercase symbolic values while the existing original API uses typed enums with lowercase backing strings. Dependency/lookup result types needed to deterministically construct unavailable, CAS-loss, and unknown-outcome cases are also absent. Consequently two incompatible implementations can satisfy the shown interface while returning different public bytes/types. Declare the exact status/reason enums and result methods (or an exact array serialization contract plus typed domain surface), and the minimal deterministic ports/factory seam required by the acceptance cases.

### P1 — accepted-request replay identity is underspecified

Precedence step 3 requires an “exact stored actor/object/canonical command tuple,” but never enumerates or encodes that tuple. It is unclear whether it includes mode, normalized installer IDs, engineer ID, expected selection revision, resolved case identity, or any authority-derived facts, and no canonical encoding/fingerprint is specified. This prevents independent REQUEST_ID_CONFLICT and response-loss tests and risks treating the same request ID with a different mode or expected revision as replay. Define the exact tuple, normalization/encoding, stored terminal result, and lookup outcomes. Also reconcile “FAILED is not terminal-cached” with the fresh lookup branches after uncertain commit so each returned FAILED reason has an observable, deterministic route.

### P1 — authority behavior is still a proposal with no executable bootstrap/revoke outcome

The draft requires both local permission and process capability and correctly reauthorizes replay, but calls the mapping “candidate/proposed” and explicitly defers additive catalog grants and seed/revoke behavior. It does not state the exact observable outcome when one authority dependency is missing versus unavailable beyond the broad shared code, nor how builtin role grants become present without granting custom/display-name matches. Because authorization is precedence step 2 and blocks confidential replay, these are Gate 1 behaviors. Record the exact migration/bootstrap state and deterministic authorization seam before approval.

### P2 — several acceptance outcomes remain explicitly deferred

Sections 3–4 and 8 still label exact audit/result/DTO, schema relation/version numbering, terminal lookup, rejected/conflict safe-audit shape, and public observation API as Gate 1 open items or task 1.3 work. The status table also places `NO_CHANGES` under REJECTED although it is evaluated only after current pending state and catalog/clock checks; this can be valid, but the safe-audit and persisted-request behavior for that terminal rejection must be stated. An executable Gate 1 artifact cannot simultaneously call the contract “closed” and defer these observable choices.

## Confirmed coherent points

- NEW_ORDER and REPLACE_PENDING do not authorize in-place mutation: replacement creates a new identity/revision and accepted composition cannot be corrected through this command.
- Selection is explicitly non-effective: it must not change opening/checklist/history, and pending B must not hide applicable A in directory or inspection views.
- Authorization precedes replay, so revoked authority cannot retrieve confidential stored selection results.
- The independently recomputed SHA-256 for the literal worked JSON is `5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a`, matching the draft. The date conversion for `2026-09-05T09:00:00Z` to Moscow calendar date `2026-09-05` is also correct.

This review does not assess or approve unfinished persistence/metadata integration and is not Gate 1 approval. Resolve the P0/P1 findings and replace the explicit Gate 1 deferrals with normative decisions before requesting a fresh independent Gate 1 review.
