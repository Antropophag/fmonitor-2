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

## Populated candidate update

Source `415e5c8adc0a108857f351d51c95419609a03d90`, image tag
`fmonitor2-manual:415e5c8`, deployed by recreation preserving the new volumes.
Native private object import succeeded:381 snapshot objects/381 identified/381
created cases, zero missing identification. No user/old checklist history import.
Native buffered workforce publication succeeded:1264 rows,929 employed/335
 dismissed, original observedAt `2026-09-07T10:20:37Z`, all employedFrom unknown.
Run `aa830484-0bb2-4c1d-bc63-4464049b9dce`; publication survives image recreation.

Installers503 resolved by local-first user resolution; owner-only superadmin now
gets403 because business catalogue access is not inherited. Owner confirmed he
expects self-service role assignment: actual Users page200 offers the own-account
role form (including manager) and invitation form. No business role was assigned
by the agent. Source role action permits self-assignment; last-superadmin protection
remains. Hourly sync is not configured and UI no longer promises hourly updates.

Newly observed blocker: Objects200 still shows zero rows after successful381-case
import; list projection under repair. Do not claim populated user flow ready yet.
Architecture now passes7rules after removing redundant explicit include; existing
production factory autoload covers the extracted queue. Manual checklist flow PASS.

## Owner feedback and immediate fixes

Owner reported «При вводе логина Недопустимый запрос». Real HTTP reproduction with
an absent but syntactically valid old preview cookie: login GET200 did not emit
replacement cookie, subsequent email POST403. `RapidPilotLocalAuth::startSession`
now publishes a cookie whenever returned session ID differs from incoming ID.
After deployment the identical loop emits a replacement cookie and email POST200;
ordinary full owner login also200. Isolated LocalAuth lifecycle regression added.
No CSRF check was removed or bypassed. Owner was asked to refresh login page.

Objects route is intercepted by `RapidPilotObjectQueue`, whose inner provenance
join excluded the private-snapshot cases. It now admits native provenance OR an
absent provenance backed by a valid technical-detail snapshot/hash, preserving
plain fixture exclusion. It recognizes native applications and permits missing
planning dates. Actual populated GET200 displays50 distinct object links on first
page; safe query count381. Direct imported card200. No real object was opened or
modified for this verification. One configured owner user remains; owner assigns
roles/invites colleagues himself. Stand is available for this manual preparation;
full on-stand operational journey awaits those accounts and owner feedback.
