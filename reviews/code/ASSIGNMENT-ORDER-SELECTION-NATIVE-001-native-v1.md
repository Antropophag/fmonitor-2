# Independent Gate 5 review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 standalone native binding v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Native production source commit: `c93c27a3480492c16c19edcfb2068d7d8e2276cd`
- Exact tested clean commit: `be0fe026817fa2f2da774c023fa08b25e85e41a1`
- Native spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Controlling core spec v0.11 SHA-256: `d9fe7f3c47e66d059138117f99609493b8a00b1d4e4166582b3185a8037d5f1e`
- Combined native archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-combined-rny_xymw`
- Combined manifest SHA-256: `236a6c5b71b364d3bc9493ef729b57c0a47b47f3da1ef8c873da89730eb90bd2`
- Core regression: 73 approved cases, source unchanged
- Registered-reader regression: 15 approved cases, source unchanged
- Architecture tool regression: 35 approved cases, policy/baseline unchanged
- Review date: 2026-09-06

## Standalone binding audit

No substantive blocker remains in the v0.1 standalone fresh-contour native binding.

The production and verification factories construct the same approved application from all eight real ports. Prefix validation precedes I/O; recovery connections are distinct, idle, and matched to factory-captured database, authenticated user, and charset; caller connections and ambient transactions remain caller-owned. Local authorization requires a complete canonical family and exact builtin-role permission, while a partial family cannot fall through to legacy. Denials use an independent metadata-validated audit transaction without confidential lookup.

Facts, snapshots, clock, request decoding, and results fail closed on malformed or unavailable data. Accepted persistence validates intent/result/allocation/event/audit echoes and snapshot/date/employment fields before facts appear. Case and no-case UoWs enforce exact locks, one stage, result restrictions, rollback causes, generated-ID bounds, request-race classification, and commit acknowledgement mapping. Registry allocation remains global with per-case versions. Terminal replay is authorized and silent; request conflicts disclose no success.

Locked state proves selection registry/header/member coherence and fresh-contour source ownership. Pending and accepted-root outcomes, root case/composition linkage, missing or contradictory lineage, no-change/stale rules, and forward identity allocation are covered. SQL remains confined to `AssignmentOrderComposition/MariaDb*.php`; DDL remains in approved `InstallationProcess` migrations; the architecture baseline did not grow.

`effectiveOrder` is null in this standalone contour. That is consistent with section 17: selection and the synthetic accepted-root precondition do not apply an order or open work. The later original application/locked-validation/opening integration must bind the real effective owner before that separate slice can be approved. This review does not authorize treating selection as applicable or effective.

## Evidence

The exact clean archive passes 56 native cases covering tracer/replay/replacement/no-case, authority and denial audit, transaction restrictions, eligibility/state/request corruption, registry/event/audit capacity, accepted-payload validation, real same/different-case and request-key races, delivery loss, native exception/boolean interruption, prefix 25 and invalid prefixes, readiness drift, and accepted-root state. `make architecture-check`, OpenSpec validation, and `git diff --check` also pass. The approved core 73, registered reader 15, and architecture tool 35 results are reused because their sources are unchanged.

The killed-connection evidence proves a native interruption after staging and before commit yields unknown transaction outcome plus a fresh absent read and no blind retry. The separate lost-response case proves silent replay after a committed result was lost before process delivery. It is not evidence of lost COMMIT acknowledgement; the approved core recovery mapping and native fresh-reader behavior cover the typed mapping, while further integration-specific acknowledgement scenarios remain outside this standalone claim.

## Scope and decision

The readiness/prefix/root verification extension is **APPROVED** as no-op production Gate 5, and the complete standalone eight-port native binding is **APPROVED** at the exact source and tested commits above.

This approval does not cover PDF rendering or storage, signed-original application, HTTP/routes, effective-order wiring, opening, deployment, or launch. Section 17 leaves those as separate gated slices. It also does not create legacy writer conversion or historical migration obligations for the fresh launch. Full integration still requires its later gates and literal `make verify`/`VERIFY_OK` evidence.
