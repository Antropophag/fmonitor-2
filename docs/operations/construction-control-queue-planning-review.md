# Construction-control queue characterization — independent planning review

- Review date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/planning_reviewer`
- Change: `characterize-construction-control-queue`
- Scope: planning review only; no executable Gate 1, RED, implementation, code
  review, sync, archive, or product approval
- Independence: the reviewer did not author or edit the reviewed planning
  artifacts, tests, test support, production code, or evidence.

## Exact reviewed artifacts

```text
1b1d5795af057ca8a44fb1fe6b0246d78ada16aea9ef1eb4bfa8e29a645b6313  openspec/changes/characterize-construction-control-queue/proposal.md
61b0861f58d74347a528d9424fbfe2640de8ed129f1ac72d96687b93ede68748  openspec/changes/characterize-construction-control-queue/design.md
f7bc52208d6a538ff018de589ae6bce949aea4313770218d16ba1c2799d07fae  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
0f39e0c5488179e6cf3df02dbb7a19ef848867249a9db926203236cd96d6631a  openspec/changes/characterize-construction-control-queue/tasks.md
```

## Evidence and method

The review checked all four artifacts against `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, the pilot contracts,
`docs/operations/pilot-behavior-inventory.md`, inspection-schedule and
inspection-planning evidence, and the current public implementation in
`PilotE2ECoordinator`, `ConstructionControlView` and `control-queue.js`.
Current SQL/order/page, permission, rendering and browser-only behavior were
used only to assess characterization traceability, not as target product truth.

## Findings

No blocking planning defect was found.

1. **The public seam is real and bounded.** The planned verifier uses actual
   `GET` and `HEAD /pilot/construction-control` through production composition,
   including identity, authorization, MariaDB projection and production
   renderer. It does not self-attest rows through a direct renderer call or
   introduce the proposed future read-model API.
2. **Observed hazards are not promoted.** Working-only selection, broad
   permission visibility, server ordering/page size, engineer-event/fallback,
   `MAX(device_time)`, legacy PTO completion display and live-clock labels are
   consistently marked `PILOT_ONLY`. Client `mine/all`, completed/search,
   sessionStorage, IndexedDB, service worker and offline synchronization are
   excluded. Target assignment visibility, inspection/completion meaning,
   ordering/pagination and API design remain `NEEDS_GRILL` and require a
   separate owner-approved Gate 1.
3. **The planned matrix is independently testable.** Literal fictional actors,
   cases, events and operations can distinguish permission admission, working
   selection, both engineer-source branches and absence, activity/PTO signals,
   escaping, canonical links, the 50-row server page boundary, malformed and
   out-of-range pages, repeat reads, concurrent reads and infrastructure
   failure. Task 1.1 correctly requires the future executable spec to replace
   planning phrases such as “существенные headers” and “current
   infrastructure-failure outcome” with literal expected responses and a
   stable transcript before owner approval or RED.
4. **Isolation and cleanup are fail-closed.** The package requires unique short
   prefixes, private artifact/session roots, pre-proved ownership, a DML/read
   runtime principal, whole owned-state fingerprints around reads, an ambient
   decoy, bounded deadlines and process reaping. Setup or cleanup uncertainty is
   classified as `SETUP_FAILURE`; it cannot become a successful oracle. Cleanup
   is limited to verifier-owned DB/files/process resources.
5. **Scope and shared-file serialization are safe.** Planning confines future
   work to `tests/Verification/`, dedicated test support and one canonical
   characterization registration. It explicitly forbids edits to the three
   shared pilot test hotspots, `PilotView.php` and
   `ProductionPilotHttpEntrypointFactory.php`, and requires unchanged
   production/rapid-pilot mutation baselines.
6. **Delivery gates and independent roles are ordered.** A fresh executable-spec
   review and exact-hash owner approval precede RED. RED author, independent
   Gate 3 reviewer, minimal test-only GREEN implementer and a different fresh
   Gate 5 reviewer are separately assigned; reviewers cannot approve their own
   work. Inventory/status and sync/archive follow reviewed GREEN rather than
   silently promoting characterization to product requirements.

## Verification

- `openspec validate characterize-construction-control-queue --strict` — PASS
  (`Change 'characterize-construction-control-queue' is valid`).
- `git diff --check` — PASS (exit 0, no output).

## Verdict

**READY_FOR_EXECUTABLE_SPEC_AUTHORING**

The four planning artifacts are coherent, traceable and safely isolated. This
verdict permits drafting the exact Gate 1 characterization specification only;
tests, verifier registration, production changes and GREEN remain prohibited
until that exact spec receives fresh independent review and append-only owner
approval.
