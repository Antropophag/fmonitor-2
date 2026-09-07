# Shared LocalAuth and command session — independent test review

Reviewer: `/root/photo_review`; artifact author: `/root/card`.
Verdict: **APPROVED**.

Owner symptom: an authenticated session could be forced back to login after a
state-changing command. Reported focused RED observed `auth_user_id` change from
`17` to absent when `PilotCommandSession` initialized command state over an existing
LocalAuth payload.

The test independently distinguishes the required cases. For the same actor it
requires every existing auth identity, email, sign-in timestamp and CSRF field to
survive command-state initialization and the next storage read. For a different
authenticated actor it requires regeneration, removal of every foreign auth field,
the requested command actor only, and a replacement cookie. These expectations are
sensitive to both the original destructive assignment and an unsafe merge across
actors.

Exact reviewed test:

```text
b3bfa6a6b8ae82d710aaee47e0b8e5e46a338f7ac289f600dc924e3048e4fa92  tests/InstallationProcess/pilot_session_shared_auth_command_001_test.php
```

Independent execution PASS. Adjacent accepted-payload, sequential-write-identity,
and LocalAuth-lifecycle tests also PASS. PHP lint and focused diff-check PASS.
