# Manual feedback: original upload rejects an authorized local role

Owner saw “Недостаточно полномочий для этого действия” when uploading an original.
Read-only checks on the deployed stand confirmed active manager role grants for
original read/upload/correct, with no legacy individual original-capability rows.
The deployed native authorizer returned DENIED for both upload and correction.

The HTTP form used local role permissions, while the original application
authorizer required `fm2_process_user_capabilities`. The application now checks
the same active local user, active assigned role and exact upload/correct
permission. It does not modify roles or preserve a fallback to stale individual
capabilities. Unsupported capabilities are denied; infrastructure failures remain
unavailable. Other ordinary actions were independently audited and already use
local roles in the current pilot path.

## Focused evidence

- FKR and manager uploads with legacy original capabilities removed: PASS.
- Correction with its separate exact role grant: PASS.
- Inactive roles, inactive users and administrator without business grants: denied.
- Existing original upload/replay/correction/history and prefill HTTP tests: PASS.
- Native fixture setup now grants the actual local FKR permissions; the old
  hidden prerequisite of explicit individual grants no longer establishes success.
- Private `runtime/role-only-original-browser-fixture.php`: actual synthetic login,
  modal selection, save and PDF upload with explicit legacy original caps removed;
  HTTP 201, zero console/page errors.

The continued golden browser run also found a separate execution-page integration
defect: its shared shell scripts were blocked by BASE CSP. A focused renderer/CSP
test was RED and then GREEN after permitting same-origin scripts on successful
GET/HEAD `/execution` pages. Failed commands retain BASE policy.
The HTTP fixture environment callback now optionally receives its allocated port,
allowing browser checks with the same trusted-loopback transport as the stand.

This is focused manual-pilot evidence. Full original verification matrices and
normal production gates are not established by these tests. Full golden-path
completion remains unproven beyond the recorded successful actions.

## Deployed receipt

Source `778d39045db35b786ce5539d5aa68af2fd512bf9`, image
`sha256:972b26e842042b4779553a09829d46c3fc4fe219c28cdb0ad887163b2705a15c`.
Pilot is healthy. All 719 runtime file hashes match the exact committed source.
The same read-only native authorizer probe against the owner's actual account
changed from DENIED before deployment to ALLOWED after deployment for both upload
and correction. The agent changed no owner role assignment or original document.
The preceding image remains available as `fmonitor2-manual:checkpoint-46c1334`.

The continuing synthetic browser run accepted original, correction, application,
reapplication and opening, then stopped at checklist initialization. A separate
real-stand GET `/pilot/assets/checklist.js` returned 503 because the router still
required an obsolete source-string splice. That blocker is being repaired
separately; this receipt does not claim the checklist golden path passed.
