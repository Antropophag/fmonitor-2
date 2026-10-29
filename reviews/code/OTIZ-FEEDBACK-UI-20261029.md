# OTIZ feedback UI — independent final review

Reviewer: `/root/otiz_ui_review`, gpt-5.6-sol / low; not code/test author.
Code author: `/root/otiz_ui_executor`, gpt-5.6-sol / low. Root authored scope and tests.
Verdict: APPROVED after corrections, 2026-10-29.

F1 historical installer count: fixed with validated applied snapshot and active registered fallback.
F2 HTTP SQL and missing certificate evidence validation: fixed using existing owning read service.
F3 verification gaps: root added native/expired/corrupt/date/permission/tab cases, all pass.

Approved application SHA-256:
- a54f4232e749b84e9444377ba8f173581e14ae36c933d6ba5e4f078d0ee97282 app/InstallationProcess/MariaDbYiiObjectQueue.php
- 8103b8e06cd282e0a83ee6351a9ee78864988333d0ab42f34a5007ebbf70a647 app/InstallationProcess/MariaDbYiiObjectQueueProjection.php
- 5973885ef93c96b344ec0b999b39c03aabf39ea38182647a852248f985ce40ad app/YiiRuntime/Views/objects.php
- 2953362dcce67ede1442e478788c263ab63f3b32ad54b9f04502f1238898e436 app/YiiRuntime/Controllers/ObjectCardController.php
- b8dc758f277e1f744d9fec024b2f798a65db00ef250348eb184d5148cb210cb3 app/YiiRuntime/Views/object-card.php

Reviewer ran new queue feedback test, object queue lineage and deadline certificate HTTP tests,
all five PHP lints, and diff check. Earlier object-card/presentation/browser checks passed.
Only isolated local DB port24329 used for tests; no full local suite.

Owner authorized short code/review/production cycle instead of ordinary gates.
Deployed exact five files to production host and PHP runtime at19:06MSK.
Post-deploy read:309 total objects,65 installation-only,10 needs-change,12 completed;
all returned rows contain installer counts; installation filter has only its exact label.
HTTPS login200. Financial data untouched. Full CI not run, not asserted GREEN.
Raw capture, deployment hashes, rollback paths and post-deploy evidence:
`/Users/antropophag/.local/share/fmonitor-2/otiz-production-20261029/`.
