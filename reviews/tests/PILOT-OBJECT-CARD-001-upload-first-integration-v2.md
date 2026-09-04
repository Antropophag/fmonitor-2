# PILOT-OBJECT-CARD-001 — independent upload-first integration Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `bddc4bf02279d995f4b447b9e1a0d972ce9daa5f`
- Correction base: `84ea58968e944c97a8bd83bb9faa877a2507d72f`
- Public seam: raw HTTP `GET|HEAD /pilot/objects/{positive-id}` through
  `public/router.php` and isolated MariaDB; UI-shell is reviewed only for the
  bounded object-card upload-first expectation
- Verdict: **APPROVED**

The reviewer did not author the specifications, tests, production, Gate 2 RED
evidence, or correction. This append-only review record is the only review edit.

## G3-1 closure

The correction closes the v1 cardinality finding in both reviewed tests.

For the capable card, each oracle now independently proves:

1. exactly one anchor has the canonical prepare href, regardless of its label;
2. that sole anchor has exact normalized text `Загрузить распоряжение`;
3. the complete `Распоряжение и команда` section contains exactly one
   interactive element among anchors, buttons, forms, inputs, selects, and
   textareas;
4. the visible sequence remains current-order reason → upload-first action →
   team/history explanation;
5. superseded text `Сформировать распоряжение` is absent.

This combination rejects duplicate same-href links with a different label,
duplicate or alternative process-action links, and controls/forms added around
the approved journey. The UI-shell correction applies the same label-independent
href cardinality, exact sole-anchor label, and one-interactive-element oracle to
its card DOM.

The broad-reader fixture still proves actor `19` owns no process capability.
Its full-link allowlist plus forbidden prepare href/text and global control
prohibitions reject both the canonical action and any extra action/control.
Capable actor `18` is separately resolved with exact
`assignment_order.prepare`.

Two new capable-reader negative cases are appropriately sensitive:

- object `4514` has a coherent wrong process state and exposes neither the
  canonical prepare href nor an upload-first action/control;
- object `4518` is otherwise a no-order `needs_assignment_order` case but has a
  valid PTO fact and exposes neither the href nor action/control.

The PTO fixture adds only the already consumed legacy PTO/prefill columns to
the exact SELECT-only allowlist. It creates no write privilege or private
assertion seam. GET/HEAD parity is exercised for both negative outcomes.

## Traceability and regression preservation

The changed expected copy and unchanged route derive directly from the
owner-approved `PILOT-PREPARE-FORM-001 v0.2` replacement of the card-launch
assertions, including its override of `PILOT-UI-SHELL-001` section 6. No new
product outcome is introduced.

The correction changes no production file and does not weaken the complete
inherited object-card surface: Examples A/B, identity/plan facts, complete
process-state matrix, exact current version and team, newest-three durable event
tuples/order, broad read versus narrow write, route/method/body/query grammar,
GET/HEAD parity, exact security/CSP/external-script rules, error priority and
redaction, hostile-value escaping, SELECT-only access, full DB/filesystem
zero-write fingerprints, repeat/concurrency determinism, corruption matrix,
resource cleanup and failure cases all remain present.

The UI-shell file receives only the bounded object-card correction. Its broader
prepare presentation is still a separately acknowledged predecessor RED; this
review neither approves it as GREEN nor reuses its historical approval for the
changed integration composition.

Expected values are specification literals, not production-derived snapshots.
The focused run reaches a successful real HTTP representation and passes CSP
and exact external-script checks before failing on the first missing approved
card fact. The RED is therefore for missing presentation behavior, not setup.

No blocking traceability, seam, sensitivity, expected-value independence,
scope, determinism, isolation, authorization, regression-preservation, or
cleanup finding remains in the corrected Gate 2 bytes.

## Reproduced evidence

At `2026-09-04T11:40:44+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    84ea58968e944c97a8bd83bb9faa877a2507d72f..bddc4bf02279d995f4b447b9e1a0d972ce9daa5f
PASS (no output)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(545): pocSuccess()
exit 255
```

The reproduced failure matches the v2 RED record. No residual test/server
process or task-owned test artifact was observed after cleanup.

## Reviewed SHA-256 inputs

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
cb4e00ce9f139e3efa56ebe2e8f8070d9ac9e692d15f71677c23d1409bc3b257  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
8d4d42ae5aeaccd2a2a577f3e16a8beaaa5508c6a3b1096ac7bdaa05d2a5c7bb  docs/operations/pilot-object-card-upload-first-integration-red-v2-2026-09-04.md
b43d139f9cba57bde3f73c477eb0843622ec903d56b7fb37c4ad3a5b08171341  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md
```

The review record path is metadata because a self-hash is circular. Any change
to the reviewed spec/test/support bytes or relevant scanned-set membership
requires fresh review under the governing manifests.

Gate 4 is authorized for the exact reviewed tests at
`bddc4bf02279d995f4b447b9e1a0d972ce9daa5f`. Implementation must preserve all
approved expectations; no GREEN or Gate 5 claim is made here.
