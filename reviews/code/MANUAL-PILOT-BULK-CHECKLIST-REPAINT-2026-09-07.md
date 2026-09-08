# Manual-pilot bulk checklist repaint — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the JavaScript correction or private browser verifier.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `a42d48d521f65e19a0bd1558c16799d50e349daa`.
- Scope: only the `app/PilotHttp/checklist.js` working-tree diff against that base.
- Verdict: `APPROVED`.

## Exact reviewed identity

```text
0238fc9da243c1642221e433c0e9224004f226016ef15df7b1155473926dfb29  app/PilotHttp/checklist.js
bf871c1535cad3f3c7cf5706ff998f5960306cf57ac83dc7cdb149fa73491eab  git diff --binary a42d48d521f65e19a0bd1558c16799d50e349daa -- app/PilotHttp/checklist.js
```

The semantic production change is the single insertion `for(const e of f.operations)N(e)` after rebuilding state from a server projection.

## Findings

No blocking findings.

`T(serverProjection)` remains the authoritative rebuild: it replaces revision, crew, accepted item completion state and completed-section state with the server response and merges accepted photo identities. The added loop then reapplies every local operation through the existing overlay function `N`. Because `N` deliberately ignores `status === "accepted"`, an acknowledged operation is represented only by the server projection and cannot be double-counted or repaint stale local evidence over the server result.

Queued, sending, retryable, rejected and conflict operations remain local overlays. During a sequential bulk send, the projection returned for the first acknowledgement may contain only that accepted item; the remaining queued/sending items are immediately reapplied, so their optimistic checkmarks do not disappear between responses. This changes rendering only. It does not alter queue order, predecessor revision resolution, request payload stripping, IndexedDB persistence, retry classification, or the stop-on-error protocol.

The same existing overlay semantics are preserved for corrections and evidence types. A pending/rejected item completion or installer correction continues to show its local item state alongside the sync banner. A pending/rejected retraction continues to remove the local checkmark and section-complete overlay until the conflict is resolved. Accepted retractions rely on the returned server projection. Pending photo upload/revocation and section completion continue through their existing `N` branches; accepted forms are not reapplied. Thus the insertion restores the local-over-server layering already used at startup without inventing a new status transition.

The strengthened private headless golden verifies the actual failure interval rather than only initial optimistic paint and final persistence. After reload with nine queued items, it samples every animation frame until sync is `ok`; the candidate retained all 9 checkmarks for all 262 samples, preserved all 9 across pending reload, then completed 41 items, 7 sections, 7 photos, 85% checklist progress and final 100% closure with no errors. The recorded predecessor failed by falling from 9 to 1 across 229 frames. No real stand data was mutated by this independent review.

Private evidence SHA-256:

```text
b7158ef51d96380fdfeccb7a8bcf608904d2d8b44ae5302d187203c710a64765  golden-browser.cjs
a17307c9f6e51fd166cc4130bc4e39308ba447640d7ce2520e857031a3fa78cb  golden-browser-result.json
```

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/checklist_bulk_online_sequence_manual_test.php
PASS optimistic online bulk persists together, sequences revisions, and stops on conflict

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/control_queue_bulk_protocol_manual_test.php
PASS control queue resumes exact bulk dependency protocol

node --check app/PilotHttp/checklist.js
PASS

git diff --check -- app/PilotHttp/checklist.js
PASS
```

The correction leaves `control-queue.js`, server commands, request payloads, authorization, append-only evidence and protected E2E files unchanged. This verdict is bounded to the exact JavaScript diff and does not assert installation on the manual stand or full `VERIFY_OK`.
