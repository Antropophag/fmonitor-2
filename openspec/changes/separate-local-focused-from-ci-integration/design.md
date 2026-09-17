## Context

#153 Slice A intentionally added a conservative full integration closure. #181 changes only execution placement: the canonical obligation remains, while local focused keeps checks justified by direct, changed, boundary, or known-consumer reasons.

## Goals / Non-Goals

Goals: one deterministic plan; stronger-reason deduplication; honest reviewer package; unchanged full CI and aggregate. Non-goals: FAST expansion, product behavior, new registry/planner/admission, #153 follow-up slices.

## Decisions

1. Extend command metadata in the existing planner with explicit placement and all selection reasons. Deduplication merges reasons and promotes CI-only to local when any reason is local.
2. Existing `focused` runner executes local placement only. Existing integration/full CI selection remains authoritative and does not depend on fabricated local records.
3. Reviewer package exposes plan commands grouped by completed-local versus pending-CI using current evidence validation; CI-only commands do not require local evidence.
4. Regression uses a disposable planner fixture plus current harness/CI public seams. The #187 delivery input is the comparison fixture for actual before/after command composition; no old full suite is executed.

## Risks / Trade-offs

- A metadata/schema change may break consumers: executable tests cover planner check, focused runner, package and CI selection.
- Conservative closure is not semantic completeness: the plan continues to state the limitation and requires full CI.

## Migration Plan

Atomic tooling/spec/test change, independent Gate 3 and Gate 5, then one exact-source GitHub CI. Rollback restores prior local placement; no product/data migration.
