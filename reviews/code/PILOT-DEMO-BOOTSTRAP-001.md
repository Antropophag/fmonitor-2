# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 5 — fresh independent final production review
- Reviewer: separately tasked agent `/root/bootstrap_v2_code_review_final`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Approved test review: `2098869`
- Exact reviewed implementation HEAD: `2186132152d8a7d466e7c4b3b3addca4c7220a7d`
- Implementation commits: `d421a09` + corrective `2186132`
- Browser evidence: `~/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-2186132/`
- Review date: 2026-08-29
- Verdict: `CHANGES_REQUESTED`

## Standards

### Blocking — bootstrap leaks persistent process-external state

`bin/fmonitor2-pilot-demo.php` imports and calls production `ShlzCssManifest` during preflight. At the reviewed head, `app/PilotHttp/PilotHttp.php:93` attaches a SysV shared-memory segment for each PID/entry key and only detaches it; no path removes the segment. After verification, `ipcs -m` shows unattached 131072-byte segments (`nattch=0`). Thus `start`, failed starts, tests, and cleanup can leave persistent kernel resources, while the CLI does not declare or preflight the hard `sysvshm` extension dependency.

This violates Gate 5 maintainability/integration-boundary safety and the bootstrap cleanup intent. The 32-bit CRC key can also be reused after PID cycling or collide with stale/unrelated state, causing a valid checkout to fail closed against an old snapshot. Replace this with explicitly lifecycle-owned state that is removed, or avoid process-external coordination, and cover cleanup/stale-key behavior through SSD/TDD before re-review.

### Judgment call — Divergent Change

The inherited `ShlzCssManifest` concentrates filesystem validation, graph traversal, CSS parsing, resource bounds, and IPC coordination in dense methods. This is not a separate rejection, but makes the bootstrap's security-critical preflight difficult to audit.

## Specification

No additional bootstrap-specific behavior defect was found. Corrective commit `2186132` now preserves post-bind shlz failure classification: a transitive public response mismatch stops the child server, leaves no active/server manifest, emits exact `SHLZ_ASSETS_UNAVAILABLE`, prints no ready banner, and exits nonzero. The added GET byte/MIME/length, root HEAD parity, and unknown-route checks are consistent with inherited `PILOT-SHLZ-ASSETS-001`.

The exact-head browser evidence completes the full walkthrough, downloads both artifacts, reaches `В работе`, exposes the checklist and engineer next step, verifies 1440/768/320 layouts, and reports no page, console, CSS, or unexpected-network errors. This evidence does not waive the process-external resource leak.

## Verification

```text
php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php   PASS
php tests/InstallationProcess/pilot_shlz_assets_001_test.php     PASS
tests/InstallationProcess/*_test.php                             49/49 PASS
PHP lint: app public bin tests/InstallationProcess tests/Support 149/149 PASS
git diff --check 9193839...2186132                                PASS
demo/router process residue                                      none
.test-artifacts residue                                          none
SysV shared-memory residue                                       BLOCKING
```

Gate 5 remains closed for `PILOT-DEMO-BOOTSTRAP-001 v0.2` at exact reviewed commit `2186132152d8a7d466e7c4b3b3addca4c7220a7d`.
