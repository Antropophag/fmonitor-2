# Native UI verifier card — focused GREEN

- Date: `2026-09-07`
- Worktree HEAD during run: `8fe291bfec0fbb5ddda2445b34267570d0b5ba8b`
- Change: `openspec/changes/reconcile-pilot-queue-shell-verifiers/`
- Scope: standalone native object-card verifier and its delta specification only
- Production changes: none
- Deployment/runtime/object 966 changes: none

## Reconciled current contract

The verifier now checks the current card as separate DOM fields and panels:
exact object `h1`, address plus entrance, full registration number, canonical
status badge, localized dates backed by exact `<time datetime>`, «Сроки работ»,
«Команда объекта», «Распоряжение», «Проблемы» and «Последние события».
Only an empty «Распоряжение» panel may omit `dl`; dates and team always require
one nonempty paired `dt`/`dd` list. Opened fixtures require the exact checklist
link, while prepare RBAC still controls the sole upload action.

Predecessor UI strings and combined groups were removed from the oracle. Current
history remains append-order visible with localized event labels, actor names,
timestamps and exact cardinalities. Hostile values still prove escaping. GET,
HEAD, failure, repetition and concurrency retain the complete database and
protected-filesystem fingerprints.

The native standalone directory behavior delivered in `067e624d` is recorded
explicitly in the delta: active local profile by exact `REMOTE_USER` email has
precedence, with active legacy identity fallback when the active local profile
is absent. This is distinct from configured E2E trusted-local-ID authority.
The manual-pilot exception does not claim that deferred authority unification is
complete.

## Focused verification

The database password was supplied through the private environment and is not
recorded here.

```sh
php -l tests/InstallationProcess/pilot_object_card_001_test.php
PATH=/opt/homebrew/bin:$PATH \
  FMONITOR_TEST_DB_ADMIN_PASSWORD="$FMONITOR_TEST_DB_ADMIN_PASSWORD" \
  php tests/InstallationProcess/pilot_object_card_001_test.php
```

Result: exit `0`.

```text
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

Raw output is retained outside the repository at
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/native-ui-verifier-card/focused-green.log`
(`0600`, parent `0700`), SHA-256
`b1b758dd94d9975ba260b145fc027a4705ce578dac79b07e12c570915959b9bc`.

Disposable database, users, HTTP workers and owned artifacts were cleaned up;
the shared DB slot was released immediately after the run.

Additional non-DB checks:

```sh
git diff --check -- tests/InstallationProcess/pilot_object_card_001_test.php \
  tests/InstallationProcess/pilot_object_list_001_test.php \
  tests/InstallationProcess/pilot_ui_shell_001_test.php \
  openspec/changes/reconcile-pilot-queue-shell-verifiers
php -l tests/InstallationProcess/pilot_object_list_001_test.php
php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
```

All passed.

## Review inputs

```text
a618cce172ed326b7ee0d2b3d1a9db6b0dec426c01f221c0ea3a931f10b26b9b  tests/InstallationProcess/pilot_object_card_001_test.php
57cd84f3f7a41aa7a69f49909064d71c87c60c14a964884dddcc80211450e8e0  tests/InstallationProcess/pilot_object_list_001_test.php
3305735b25b043115cc342e3122671229656c03c4ac2b5a1083f95b021b3a631  tests/InstallationProcess/pilot_ui_shell_001_test.php
7b6b936f5890dcec031919a1e58b094f1a633ef34bf03cd95d99a0a00eae2b3f  openspec/changes/reconcile-pilot-queue-shell-verifiers/proposal.md
a5ce6ae77263114585301e672a7f8445c7ed4d9f1129dbe7d3eb45e08208d0c2  openspec/changes/reconcile-pilot-queue-shell-verifiers/design.md
b4e24ea644a43d737891274d0903224df75ee94775dfaf943931105b16da89f0  openspec/changes/reconcile-pilot-queue-shell-verifiers/tasks.md
c380b1c9e92f0e26ee113b1e453088415708a8c07d7568179b7a1b4e5a81f0de  openspec/changes/reconcile-pilot-queue-shell-verifiers/specs/verification/pilot-queue-shell-current/spec.md
```

No self-review verdict is recorded. Whole-package list/shell rerun, Gate 3 and
code review remain pending.

## Independent review after final freeze

`/root` approved the exact frozen package in
`reviews/tests/RECONCILE-PILOT-QUEUE-SHELL-CARD-INDEPENDENT-2026-09-07.md`
and `reviews/code/RECONCILE-PILOT-QUEUE-SHELL-CARD-2026-09-07.md`.
The earlier pending statement above records the author handoff point. Full
exact-SHA verification and deployment remain pending.
