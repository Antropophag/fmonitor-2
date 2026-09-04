# PILOT-PREPARE-RBAC-FIXTURES-001 — corrected Gate 2 RED v9

Дата: 2026-09-04  
Автор RED: independently tasked agent `/root/prepare_v15_red`  
Base/review commit: `05b1e0bab9106864a23981418e74ff679e300c6d`  
Prior Gate 3: `reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001-v8.md`, **CHANGES_REQUESTED**  
Verdict: **QUALIFYING RED / fresh independent Gate 3 required**

## v8 finding closure

Verifier сохраняет всю v7 admission/RBAC/revoke/renderer/read-only/cleanup
матрицу и все v8 upload-first assertions. Коррекция добавляет:

- raw public `GET|HEAD /pilot/assets/picker.js` с отсутствующей identity,
  заведомо неверными DB credentials и отсутствующим CSS: exact repository
  bytes, JavaScript content type/length, `no-store`, asset CSP, HEAD parity,
  deterministic repeat, POST `405/Allow: GET, HEAD`, DB/filesystem read-only;
- исполняемый Node `vm` + deterministic DOM harness реальных asset bytes:
  exact U+0009..U+000D/U+0020 normalization, `ru-RU` case folding обоих
  operands, Unicode-code-point minimum, ASCII-only tab digits, 20-result cap,
  exact live/accessibility copy, pressed state, hidden-ID synchronization,
  focus after rerender, open/Escape/focus return и atomic fail-closed malformed
  delivered records;
- full template direct-child grammar: non-span elements, span text and
  forbidden interstitial text are observable; only approved whitespace text
  nodes pass;
- public server-side malformed/boundary fixtures for ID `0`, `1000000`, exact
  `999999`, position 160/161 code points, exact 300-code-point name and exact
  whitespace normalization. Existing blank-name, row-ceiling and literal
  deterministic order checks remain.

Duplicate ID, missing columns, over-300 name and malformed UTF-8 cannot be
persisted through the canonical MariaDB catalogue schema (primary key,
`NOT NULL`, `VARCHAR(300)`, utf8mb4 connection). The verifier does not weaken
those storage invariants or add a test-only production input seam; duplicate
ambiguity remains covered by the exact public order/identity oracle and client
parser rejection harness.

Production files and the existing renderer spy/router support were not edited.
OpenSpec task 2.2 remains unchecked.

## Canonical RED

Executed from the task worktree:

```text
$ date --iso-8601=seconds
2026-09-04T09:34:28+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: picker client contract process: ...
Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# non-zero; completed 2026-09-04T09:34:31+03:00
```

This is intended missing behavior at the owner-approved client seam, not setup
or predecessor failure. Before invoking the client harness, the verifier
successfully fetched exact same-origin asset bytes with full public admission,
executed the complete retained v7 rejection matrix, performed allowed GET/HEAD
through the canonical renderer decorator, and preserved guarded state. The
current script leaves the approved pre-init hidden opener/dialog and visible
fallback unchanged instead of atomically enabling the validated picker.

The isolated harness independently reproduces the same intended failure when
run directly against `app/PilotHttp/picker.js`. MariaDB/PHP/Node were available;
the verifier's `finally` cleanup completed.

## Validation and exact hashes

```text
node --check tests/InstallationProcess/support/pilot_prepare_picker_client.js
php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
php -l tests/Support/PrepareRendererInvocationSpy.php
php -l tests/Support/pilot_prepare_renderer_spy_router.php
openspec validate pilot-prepare-rbac-fixtures --strict
git diff --check

# all exit 0

c0ccf20c23a085d4dda1c1404d8640ffdd30bdb3b74e4c1d5b13fb27f7be2c0f  tests/InstallationProcess/pilot_prepare_form_001_test.php
cd8907209d40691ab8ad25a52305bd42f8d2d089981e93b2aa845b0f21745c5c  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```

Any verifier/harness/support edit after these hashes restarts Gate 2. A
separately tasked reviewer must perform Gate 3; this evidence is not self-review.
