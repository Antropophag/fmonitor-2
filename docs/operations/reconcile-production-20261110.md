# Production reconciliation —2026-11-10

Owner requested all deployed changes in Git with tests, independent reviews and exact-source GitHub CI. Root captures source/read behavior only; production mutations are outside scope. Base main60ed34ac; dedicated branch codex/reconcile-production-20261110. Contract: specs/PRODUCTION-RECONCILIATION-20261110.md. OpenSpec: openspec/changes/reconcile-production-20261110/.

## Capture and provenance

Evidence is private: ~/.local/share/fmonitor-2/reconcile-production-20261110/{capture.json,runtime-audit.json,main-divergence.json,candidate-divergence.json}. Production checkout remains a69be7a5 with hotfixes. Actual PHP scan covers1000files; every runtime byte matches the corresponding host source. Main differs in34runtime paths. Published public-guide hashes match host and main; the guide is served from gateway state, explaining its absence in the PHP image. Worker/scheduler differences are inventoried; runtime synchronization is not performed.

Integrated existing history rather than reauthoring: main OTIZ/object chain f34cb330→f524e212→a3dd8b7c→b255d2d4→6af4cf69→dbc5de11→ffba36b7→70eb4008→3611d601; ERP eebf3f9b; template c8d5b981; actual dates0437bc42; search/password1bc3e652; installer-directory17126f28. 70eb4008 is patch-identical to45368f8c and is included once. Original code/test authors and reviews remain in those commits. Root authored the integration scope/contract and resolved goal-document merge conflicts to keep the new task active; other source/test merges were automatic. No runtime differences remain versus the1000-file production snapshot after integration.

Unshipped PTO workbench/contractor/calendar work in neighboring worktrees remains separate. No local WIP is included merely because it exists. Production v14 historical identity receipt gap remains a recorded operational issue; no receipts, identities, database facts, schemas or containers were modified during this reconciliation.

## Verification status

Historical focused test reviews/RED evidence are preserved, but their emergency-delivery CI deferral does not qualify the integrated candidate. Current planner selects CRITICAL with independent Gate3 and final reviews and full unit/integration/e2e/governance CI. Root runs only bounded focused local commands. Independent retrospective test review and final source review are APPROVED with no unresolved findings. PR and exact-source full CI remain pending; no overall GREEN/admission/merge claim.

## Focused qualification and review corrections

All26mapped focused checks passed on frozen source434894b1adb9d84b76adb5bfcf183dd53f2723287ff916dc94dac2127df48d76; exact records and reviewer package20261110T075348Z-5ca1730af0 are retained outside Git. Full protected nginx/FPM browser covers document download, original upload/correction, object/checklist completion, OTIZ export/acceptance and session persistence across restart. Recovery/readiness checks passed. Architecture, lint, canonical inventory and current Quality Graph validation passed.

Root strengthened the public preview regression to compare13financial tables, updated five literal resource hashes to the captured bytes and adapted the protected runtime browser to the already deployed attachment-download behavior. Exact PDF bytes/length/filename, single POST/download, unchanged page and no GET retry are asserted. Only Chromium's exact validated attachment-navigation abort is expected; other errors still fail. A private test-only prepend probe guarded to local port33434 and random fixture database injected3preview events; the new assertion correctly rejected them. Original runtime source remained unchanged. Separate author /root/reconcile_inventory added six missing ownership mappings; independent reviewer /root/reconcile_gate3 closed all three initial findings and reviewed all34runtime/config paths.

The copied Composer dependency cache initially retained an absolute generated autoloader path to a removed historical worktree. Its read-only generated files were made writable only in this new checkout and autoload files regenerated; source/lock files were unchanged. Initial setup failures and both root test-helper corrections remain in private logs. Later exact-source runs are the qualifying evidence.

The legacy v1 Gate3 package builder insists on at least one current INTENDED_RED and cannot package an already implemented all-GREEN import. This automated preimplementation package is not claimed. The independent retrospective test/spec review instead used the bound reviewer package, historical RED/review provenance and the demonstrated fault-injection RED. The required independent final review is separate; both records are committed under reviews/. No artificial runtime defect, disabled gate or modified planner was introduced.

The final grouped commit adds these review records and updates delivery reports/tasks to the reviewed snapshot; implementation and test bytes match the reviewed source. Full exact-source GitHub CI remains the required publication check.
