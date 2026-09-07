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
