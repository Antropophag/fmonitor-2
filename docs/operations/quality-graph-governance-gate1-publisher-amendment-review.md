# QUALITY-GRAPH-GOVERNANCE-001 v0.6 publisher amendment — independent Gate 1 review

Date: 2026-09-03  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked amendment reviewer; did not author the specification amendment, owner decision, OpenSpec changes, tests, or implementation  
Reviewed commit: `e7bc1af99236f43ac591ea250ee28ff3ad122d7f`  
Owner decision: `docs/operations/quality-graph-custom-publisher-owner-decision-2026-09-03.md`  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.6  
Verdict: **APPROVED**

## Trust-boundary findings

- The custom publisher has an exact minimal permission ceiling: `actions: read` for workflow-run artifacts, `contents: read` for trusted base-branch topology, and `checks: write` for the aggregate check. The contract explicitly excludes `actions: write`, `issues: write` and `pull-requests: write`; no other permission is authorized.
- Its only event is `workflow_run`. The generated v0.1.7 `issue_comment` trigger and command/approval job are expressly non-deployable, so collaborator comments cannot become an alternative approval or execution seam.
- Checkout is prohibited. The trusted publisher therefore cannot fetch or execute untrusted PR code; topology remains sourced through the pinned runtime's base-branch trust path.
- Runtime use is restricted to the immutable action `alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef` and the upstream `watch`/`publish` commands. The amendment does not authorize a repository reimplementation of artifact validation, Result v0 provenance checks, topology selection or check publication.

## Upstream preservation and drift behavior

The comparison oracle is fail closed: declaration, manifest and runner remain clean compiler output; the publisher may differ from the pinned generated publisher only by the documented privilege-removal transformation. In concrete review terms, the allowlist permits removal of the `issue_comment` event, removal of the command/approval job, and reduction of the permission map to the three named permissions. A mutation to the retained `workflow_run` event, watch/publish job structure, pinned runtime reference, inputs/environment, conditions, dependencies, artifact/provenance handling or any unrelated YAML is “every other drift” and must fail.

This preserves upstream watch/publish behavior while deleting—not replacing—the unnecessary command surface. The generated publisher may be retained solely as a reproducible comparison artifact or regenerated for comparison; it must not occupy a deployable workflow path.

## Fail-closed acceptance outcomes

- Exact allowlisted removal with otherwise preserved generated watch/publish content: comparison passes.
- `issue_comment`, a command/approval job, checkout, any runtime command other than `watch`/`publish`, any forbidden/additional permission, a mutable runtime ref, or modified retained publisher behavior: architecture/repository comparison fails.
- Missing generated comparison baseline, compiler/version mismatch, malformed YAML or an unclassifiable diff cannot be treated as an allowed transformation and must fail.
- Disabling Quality Graph node approvals does not excuse deployment of the generated command job; repository-owned Gate 3/Gate 5 records remain the only approval evidence.

## OpenSpec and authority

Proposal, design, delta specification and tasks consistently describe a repository-owned minimal publisher, unchanged compiler-owned runner/manifest, pinned upstream watch/publish runtime, the exact permission set, the prohibited comment/command/checkout surface and the allowlisted comparison. Migration phase B still requires trusted base-branch topology, and the amendment does not authorize merge, branch-protection changes, old-harness removal or a parity waiver.

The owner decision explicitly approves v0.6 and the selected custom-publisher behavior. Gate 1 for this amendment passes. Gate 2 RED should include a passing exact transformation plus independent failures for each removed trigger/job/permission reintroduction and for mutations to every retained watch/publish field.
