# Independent Gate 1 — PILOT-LOCAL-TRUSTED-SCHEME-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the specification, tests or proposed configuration change.
- Date: 2026-09-07
- Reviewed HEAD: `a5419effa8a2da44edd216da0f5fc99d64bd978c`.
- Specification: version 0.1, SHA-256 `b23d6d96a040cd4ff854ac8250904b4ca1b3b911ef857b6931671c7d234810b7`.

## Findings and decision

No blocking findings. The current local Compose profile publishes direct HTTP at `127.0.0.1:8092` and lacks the explicit trusted scheme. The inherited PILOT-SESSION-STORAGE-001 contract requires exact configured `http` or `https`, rejects absent/other values and ignores client forwarding headers. A literal `http` in this bounded local profile supplies the existing required configuration without adding a backend fallback or extending trust to request headers or the host environment.

The effective Compose JSON is an appropriate public configuration seam. Its scheme must feed the real router fixture directly, without an independent test default. The unchanged loopback address and published/target port are explicit expectations, so the repair cannot silently widen exposure. Runtime success requires a healthy native administrator/session setup, exact authorization, Users GET 200 and action tokens committed by the existing session owner before output. A second successful GET proves continued owner-backed operation.

Missing/empty configuration and forged forwarding headers retain the exact unavailable response without users data or new tokens. Current read-only inspection of `PilotE2ECoordinator::sessionResponse` confirms the scheme check precedes session start and the owner-backed users route. `ownerUserAccess` commits session state before returning the successful HTML, matching the proposed observable token proof. No domain facts, directory grants, migrations or protected E2E changes are permitted.

The operation after scoped Gate 5 is restricted to configuration-only recovery of the already-owned local preview with its exact existing image and volumes. Before/after DB DDL/row hashes and previously existing session bytes remain required. To reconcile session preservation with authenticated smoke checks that legitimately add tokens, those checks must use newly created task-owned sessions rather than mutate the pre-existing session files covered by the preservation assertion. Entrypoint effects must be checked; a healthy container alone does not prove preservation or authorize a source rebuild, migration/import, grant change or remote action.

## Evidence and limits

Read the normative specification and all four OpenSpec artifacts, `compose.yaml`, the trusted-scheme clause of PILOT-SESSION-STORAGE-001, and the relevant coordinator session/users response code. Reused previously read repository constitution and delivery requirements. Only this review record was added; no tested artifact, configuration, runtime resource or session was changed and no test or recovery action was run.

| OpenSpec artifact | SHA-256 |
| --- | --- |
| proposal.md | `c77e778b2c93d79b6bb0fd128b6d73d3a549551d636ea706d36ca60512fae02f` |
| design.md | `15e2235574fb257396be1d22c619307d50ec66b7013772e7c133f0586d4c0306` |
| tasks.md | `f08c2e28a5072ae4a2a62982366c7ce755aa58efe2c1c29d934b61e687b1505a` |
| specs/pilot/local-trusted-scheme/spec.md | `30f9dbe3dee3988313e96c52b50618982277d9b10f5b74d1a71da1c5469ab330` |

Gate 1 only is **APPROVED**. Healthy native setup followed by intended RED, independent Gate 3, minimal configuration GREEN, related regression/configuration checks and independent Gate 5 remain required before preview recovery. This is not full verification or launch approval.
