# Independent review — OBJECT-COMMENTS-20261030

Verdict: **APPROVED** for the owner-authorized short code → independent review → production delivery path.

Reviewed against base `b255d2d4` and `specs/OBJECT-COMMENTS-20261030.md`. The review covered the eight runtime files below, the root-authored focused tests, the final HTTP/browser/lifecycle evidence, and the final desktop and 390 px screenshots. No active findings remain.

## Exact runtime binding

- `3cfe8d47110eee4673e4eb3f0a57cbac9f094128746984adf22d69b35373a467` — `app/InstallationProcess/MariaDbYiiObjectCard.php`
- `1ad15184b9c7252359c0f571d62f25f28be2b8f473e368dd605617fe33a1f551` — `app/InstallationProcess/MariaDbYiiObjectQueue.php`
- `d9269c22e709f376a6ed1b99007004bdad7c15fdf7bbb4e0723e1666eba13162` — `app/InstallationProcess/ObjectDetailsFieldRegistry.php`
- `67965913fc4545b2a77ede699910402c5b3e18b98a33840b47513e59196e9df8` — `app/YiiRuntime/Assets/pilot.css`
- `cd10cd5c08826787475a366420338957ff465b272ac8901561b9008d9214b74f` — `app/YiiRuntime/Assets/preopening.css`
- `33d3bcca07d534058992754784b5ebea4b9420736c62dbff8b5e5b361eb6a88a` — `app/YiiRuntime/Controllers/ObjectCardController.php`
- `151e1d33a06bc42a34334ea2621d7b1f2440bcd6169454950cbb6e5c3940fde8` — `app/YiiRuntime/Views/object-card.php`
- `743e4c3336c2cfbc725447527d6aa595e482648799360499753a9f29cb17f4d7` — `app/YiiRuntime/Views/objects.php`

## Review result

The implementation keeps `ObjectDetailsEditApplication` and its MariaDB store as the sole writer. Comment saves use the existing optimistic revision, request replay, append-only event, actor snapshot, and transaction boundaries. The comment-only form sends exactly CSRF, request ID, expected revision, and comment, so it cannot overwrite technical fields. Conflict and validation responses preserve submitted text while re-reading current persisted state; stale writes produce no facts.

Normalization implements UTF-8 validation, CRLF/CR conversion, Unicode edge trimming, the 2000-character limit, and C0/C1 rejection except tab/LF. Rendering is encoded and preserves plain-text line breaks. Clear stores a null current value while prior events remain immutable. Latest author/time comes from the latest event whose change set contains `comment`, so later technical edits do not replace comment provenance.

Read authorization remains `objects.read`. Edit UI and the writer retain the exact active `fkr_operator|manager` plus `objects.details.edit` policy. The queue projects comments through its existing joined read rather than adding row-by-row queries. The event lookup is bounded to one object and uses the existing `(object_id,event_id)` history index before filtering comment events.

The dedicated card section is separate from the technical passport/modal, and the final queue column links to it. The corrected scoped CSS keeps the textarea full width with at least 120 px height and contains the header, current value, error, form, and textarea at 390 px. Desktop and narrow screenshots show encoded markup, readable conflict recovery, and a usable queue column.

## Evidence reviewed

- `~/.local/share/fmonitor-2/object-comments-20261030/http-final.log`: focused public HTTP save/edit/clear/replay, Unicode/plain-text/history, authorization including wrong-role-with-capability denial, CSRF, conflict, and retained-text checks — PASS.
- `~/.local/share/fmonitor-2/object-comments-20261030/browser-final.log`: exact native form payload, desktop/narrow save and conflict recovery, full-width/min-height geometry and child containment — PASS.
- `~/.local/share/fmonitor-2/object-comments-20261030/lifecycle.log`: active state through native completion, then comment update on the completed object without unrelated process writes — PASS.
- Root reported the updated pure unit allowlist/normalization check with 12 fields — PASS.
- Executor reported the retained Yii object-details HTTP, object-card, and MariaDB object-details focused regressions — PASS. These results were observed in the executor transcript and are not represented as filesystem logs.
- PHP syntax checks and `git diff --check` — PASS.
- Final screenshots inspected at `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-c5578989426b/`: `comment-desktop.png`, `comment-narrow.png`, and `queue-narrow.png`.

An initial suspected UI/server role mismatch was withdrawn after inspecting `PreopeningController::processCap()`: it combines the permission with `hasProcessRole()`, which is restricted to `fkr_operator|manager`, matching the store authorization.

Full CI and ordinary gates are deferred by the owner's recorded authorization. This review does not claim overall suite GREEN, GitHub CI GREEN, commit, deployment, or production verification. Approval applies only to the exact eight runtime hashes above; any runtime change requires renewed review.
