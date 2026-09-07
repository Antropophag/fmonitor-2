# Manual pilot stand cutover — 2026-09-07

Owner explicitly authorized removing the previous preview: «Да ты можешь снести прежний превью».

The old `fmonitor2-local-preview-pilot-1` and `fmonitor2-local-preview-mariadb-1`
containers were stopped and removed. Their volumes remain detached as a recovery
copy. No synthetic test database or other Docker project was removed.

New local project: `fmonitor2-manual`, URL http://127.0.0.1:8092/pilot/login.
Separate new `mariadb-data` and `pilot-state` volumes; clean bootstrap creates only
the configured owner account. Bootstrap credentials and runtime compose override
remain outside the repository under the private manual-pilot runtime directory.

Initial image `fmonitor2-manual:build-candidate` is an intermediate working-tree
build, not an exact-SHA release. Container is healthy; actual owner login, Users
and Objects GET returned 200. Installers GET returned 503 and is under diagnosis.
Real object/workforce snapshots have not yet been published into this target.
Do not call this the finished manual pilot or production-ready integration.

Focused application/reapplication/opening, HTTP original history/download/apply/
opening/card, restart preservation, completion 85/100 and correction checks pass.
Architecture (7 rules), visual contract and focus contract pass on this working
candidate. These checks are not a full `make verify`/CI or independent full gates.
Browser UI automation was unavailable in this session; actual stand HTTP checks
use the ordinary login and server routes.

Next: finish private snapshot import, repair Installers, rebuild from committed
source, check actual populated routes and restart, then hand off for manual feedback.
