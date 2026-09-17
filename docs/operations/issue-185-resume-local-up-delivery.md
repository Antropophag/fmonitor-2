# Delivery №185 — безопасный повторный local `make up`

## Scope и authorship

- Base: `e419c2b5d1e4d6c9e46edda1adf7abac6883447a` (merge PR #188).
- Change: `resume-provisioned-local-stand`; contract `INITIAL-OWNER-PROVISIONING-001`.
- Root authored scope, normative spec, verification input and tests.
- Separate executor `/root/issue185_executor` authored production changes in `Makefile`, CLI and `MariaDbInitialOwnerProvisioning`.
- Independent reviewer `/root/issue185_gate3` performed Gate 3, two correction rereviews and supplemental test-delta review. Separate reviewer `/root/issue185_gate5` performed final Gate 5.
- Owner explicitly authorized apply through PR-ready. No merge, deploy, production import or external send is authorized.

## Implemented result

`make up` explicitly selects `--resume-existing-local`. The direct production CLI remains strict clean-create/exact-replay. Local resume runs a read-only transaction under the existing database/prefix advisory lock and accepts only one active expected owner whose `user` and `superadministrator` grants retain bootstrap/null-actor provenance, active roles, credential and required permissions.

Local success neither compares nor changes the password and performs no repair/write. Other users, invitations, auth/role/status history, owner profile/session changes and additional manual roles are retained. Foreign, absent, ambiguous, blocked, credential-less, provenance-corrupt, inactive-role, missing-grant and insufficient-permission owners return `LOCAL_OWNER_NOT_RESUMABLE` with full identity snapshot unchanged.

## Evidence

- Planner: `CRITICAL`; reviews `gate3`, `final`; local acceptance plus governance/unit obligations, semantic integration closure in exact-source CI.
- Gate 2 RED records: route `1789685651020086000-d6264f44d2464ecba0c8d0b1f090d667`, MariaDB `1789685655128313000-09ad7ae4b0dc43d28c7e13c4add0e289`.
- Gate 3: append-only verdicts in `reviews/tests/INITIAL-OWNER-PROVISIONING-001.md`; final and supplemental verdicts `APPROVED` after two correction returns.
- Current focused GREEN records before documentary closeout: route `1789686523686295000-5a048e04fa2c4d698519cc0aede47db4`, MariaDB `1789686527756759000-b79007a1be344de0b35bae7ea12b81af`, governance `1789686749871303000-9fb284313a084efe9c1d8af0efec3d3d`, architecture `1789686776544339000-c6b86921b29e4ffd83d2e297863fad1b`.
- Full local `make test`/`make verify` was not run per owner decision.
- Final review: `APPROVED`, findings none, record `reviews/code/INITIAL-OWNER-PROVISIONING-001.md`, reviewed candidate source `24e498a1d99cce3fb4b05453a1405041025e02b59523ef20a6d2f618c20e106b`.
- PR/head and exact-source CI: `PENDING`; until recorded, publication readiness is `UNKNOWN`.

## Review/rework

Gate 3 returned twice: first for incomplete sensitive branches, local lock/concurrency and execution-level Make-route proof; second for complementary provenance predicates and complete concurrent process triples. Root corrected all findings. During Gate 4 two root fixture defects were found and corrected (unconsumed `RELEASE_LOCK` result and wrong pre-development snapshot oracle); supplemental independent Gate 3 approved both without reviewing production code.

## Remaining #185 scope

This PR intentionally leaves POSIX modes, file UID ownership, VPN route, import filters, engineers and chunking for later slices. It does not take #182 or alter planner/harness algorithms, skip rules or architecture exceptions.
