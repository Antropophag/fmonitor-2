# Independent Gate 5 code/integration review — PILOT-PREPARE-RBAC-FIXTURES-001 v1

Date: 2026-09-04
Reviewer: independently tasked agent `/root/prepare_v15_gate5`
Implementation author: independently tasked agent `/root/prepare_v15_green`
Production review base: `6137d5e83be6a31b00e801efe6acf00b4ce473ce`
Reviewed commit: `b81011c388983e23a13930be00843eebb56fbadd`
Verdict: **APPROVED**

The reviewer authored neither the specifications/OpenSpec artifacts, approved
tests/support nor production. No test or production file was edited during
this review. The review record and the post-verdict task 4.2 checkbox are the
reviewer's only changes.

## Authoritative state and integrity

At `2026-09-04T11:15:29+03:00` the worktree was clean on
`codex/pilot-prepare-rbac-green`, exact HEAD was
`b81011c388983e23a13930be00843eebb56fbadd`, and the branch was synchronized
with `origin/codex/pilot-prepare-rbac-green`. The requested base resolved to
`6137d5e83be6a31b00e801efe6acf00b4ce473ce`. The only change after verified
production candidate `382fd831b9190f4d430f32fd95f3497430fe6593` is the
append-only final full-verification record; production and approved test hashes
are identical at both commits.

The owner-approved normative hashes were recomputed and match the append-only
approval record exactly:

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
0c87ed39e3454e87339e606b3c1d4202538cd0d46534a590e69739cf8d19087a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
c70299b78cc2a8698e7ca4d1eca381967ab0b11e949f2e2b8cb99ea7dcdb8576  docs/operations/pilot-prepare-rbac-fixtures-gate1-rereview-v15.md
5a9288ce38c1e4ad55b1c779b691404e4df06d85f1eb2ebab7d94795513bbba4  docs/operations/pilot-prepare-rbac-v15-exact-hash-owner-approval-2026-09-04.md
```

The OpenSpec task hash is expected to have advanced only through checked
delivery tasks; current hash before this review is
`76841f0586b5516eeb76f00e3639cafde13e1befe05403ec5d9fa6720844d226`.

Gate 3 v17 is `APPROVED` for the unchanged final tests. Recomputed hashes:

```text
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
fae262571db508b02175a6c2f52cd67e8867b15b9ad7a572da05e2888f3c7ec8  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```

Final production hashes reviewed:

```text
6c02c00bf14e2f9559e9f4fd79e538ce9699dc2b14c004212d59bdd8f2b5b17c  app/PilotHttp/MariaDbPrepareEngineerDirectory.php
d8534e2c5904e716fe62d73bd6b873131dbd1ee22b1fb5843e3750b6c60dbb20  app/PilotHttp/MariaDbPrepareWorkforceDirectory.php
649d7e67791fc6c3bd511bea99c5ea633829de3ee79ecd825441dca486cf8b1a  app/PilotHttp/ObjectCardView.php
773926e7a03f2c3192401b079f04b18c90634fc63de473b8aa94ed77428068ee  app/PilotHttp/PilotHttp.php
8cbcb9b66bfd48dfbbd45885784ec62f42fa275c480af783daec61d1adc2114d  app/PilotHttp/PrepareFormView.php
9994edc1e66cc2c8b8991a486399cefd100bef1854759dceb80a7e0951b64aa8  app/PilotHttp/picker.js
```

## Standards axis

**PASS — zero findings.** The complete production diff
`6137d5e83be6a31b00e801efe6acf00b4ce473ce...b81011c388983e23a13930be00843eebb56fbadd`
keeps SQL in named MariaDB read adapters, preserves the canonical factory-owned
renderer decorator, uses the one existing HTTP/domain read composition, and
adds no state-writing seam. The former fixture-specific SVG byte rewrite and
unused busy/history presentation work are absent. Full-catalog validation,
explicit Unicode code-point ordering, exact provenance association and
fail-closed parsing are cohesive with the approved contracts. No actionable
documented-standard violation or Fowler-baseline smell was found.

## Specification axis

**PASS — zero findings.** The diff implements the separately ordered local
`assignment_order.prepare` and process-capability gates, canonical factory
observation, GET/HEAD parity and rejection precedence without a legacy or
`objects.read` fallback. It returns the approved upload-first, read-only form;
validates every catalog row before eligibility; applies the 500 eligible-record
ceiling; preserves Unicode code-point/name plus numeric-ID ordering; exposes
homogeneous or exact per-candidate workforce provenance; and atomically rejects
malformed client provenance grammar. Engineer eligibility, legacy prefill,
unchecked confirmation, exact inert records, empty `data-busy`, object-card
launch, redaction, and zero mutation conform to the approved v15 contracts.
POST/application mutation behavior remains outside this bounded diff.

The reviewed tests would catch plausible regressions in admission order,
fallback authority, canonical renderer invocation, catalog truncation,
eligibility and ceiling, Unicode/numeric ordering, provenance cardinality and
association, malformed DOM grammar, hidden-ID creation, GET/HEAD representation
and persistence/file mutation. No assertion weakening, production-derived
expected value, fixture-specific production branch or test bypass remains.

## Fresh verification

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac` at exact HEAD
`b81011c388983e23a13930be00843eebb56fbadd`.

From `2026-09-04T11:17:00+03:00` through
`2026-09-04T11:17:19+03:00`:

```text
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary
```

From `2026-09-04T11:17:29+03:00` through
`2026-09-04T11:17:59+03:00`:

```text
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
# exit 0, no output

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check
# exit 0, no output
```

## Full verification and bounded Gate 5 decision

The final recorded full run started at `2026-09-04T11:10:56+03:00` on exact
production/test candidate `382fd831b9190f4d430f32fd95f3497430fe6593` and ended:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

It emitted the prepare PASS literal and passed reset, migrations v1..v11,
architecture 7/7, lint and final working-tree diff-check. The subsequent HEAD
commit adds only that append-only result record. The named failures are existing
predecessor/integration classes outside this production diff: checklist
item/session composition, navigation/object-list/card/UI-shell/E2E presentation,
host TCPDF dependency and rapid auth-hot-path constructor availability. They do
not contradict the owned focused and relevant regression evidence and are not
waived by this review.

Therefore bounded Gate 5 for `PILOT-PREPARE-RBAC-FIXTURES-001` is
**APPROVED**: every owned behavior and relevant regression is green at the
reviewed exact code/test tree, and the complete diff conforms to the approved
specification and repository boundaries. Repository integration and release
remain explicitly **NO-GO**: there is no literal `VERIFY_OK`, the predecessor
failures must still be corrected through their own Gates 1–5, and this verdict
must not be used as repository-wide GREEN or merge authorization.

