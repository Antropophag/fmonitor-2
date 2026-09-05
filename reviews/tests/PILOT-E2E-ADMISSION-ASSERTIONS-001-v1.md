# Test review: PILOT-E2E-ADMISSION-ASSERTIONS-001 v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/admission_patch_gate3`
- Patch author: `/root`
- Reviewed repository commit: `3a63e44670329972f7f9d91c2a41d9e1c6779bc0`
- Specification: owner-approved revision 3 of `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`
- Public seam: configured production raw HTTP `GET /pilot/objects`, checked through `FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle::matches(string $html): bool`
- Red command and intended failure: unchanged protected verifier through the private capture wrapper; exit `255`, HTTP status `200`, stale first assertion expected `1`, actual `0`; the captured actual body independently satisfies the semantic-list oracle
- Reviewed artifact: unapplied `docs/operations/protected-e2e-admission-assertions-v1-2026-09-05.patch`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
1ea21d78e9c1fe3681085ddeb60557eef5b113d046efa56c68fece12c168a48a  docs/operations/protected-e2e-admission-patch-gate3-packet-2026-09-05.md
2fadffb1b9ca4269f03f090a8347155f5b9f005f6d1f3874ac43f758b3e9d33d  docs/operations/protected-e2e-admission-assertions-v1-2026-09-05.patch
bd4e150b5bffc7bbd5b3f986f0a93c4cc34bfe25943c968f39c22b3eec52f141  docs/operations/protected-e2e-admission-oracle-red-2026-09-05.md
83c703324955d542bba11b831f2ab4500b0c3901f626dd02bc2feed31af1c931  docs/operations/protected-e2e-admission-oracle-green-2026-09-05.md
0014692cf93fd687eb377f3f5e9bb38c09f16c8a88ea5d75fc3febd884d392b6  reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md
7859809e0286d25cb20aa27a0b1537048b4777b3e367a17b4d3c63bc1ab9d5fd  tests/Support/ProtectedE2eAdmissionOracle.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b  proposed tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Private primary evidence, inspected read-only:

```text
6cd9638a470728bd069bd6481ceb2dd16e15040e0267f8e2b032581d34e7776b  capture.php
021076c1ae9d02f8b5fc8e22c0c4c6e68ee246bbbd17bb736bb775f855a15b44  admission.html
0fab206b0b6df958be90d7956892c3d5c2c783bd83dd6c3efbf9501ab69b7a5f  unchanged-e2e.log
b7c101fdc32792a29ab1cd8dd641203255fe52df9e51e664a7829aa9fa430975  check-response.php
```

Archive: `/Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye`.

## Findings

Traceability and correction methodology are sound. The owner explicitly approved
revision 3 and its separately gated replacement of stale table expectations with
the fixed semantic-list representation. The unchanged protected verifier reached
a fresh real `GET /pilot/objects` response with status `200`, then failed its first
table-specific XPath assertion with expected `1`, actual `0`, and process exit
`255`. Independent execution of the archived `check-response.php` returned
`REAL_HTTP_ADMISSION_SEMANTIC_LIST_OK`; the focused 28-case oracle regression
returned `PROTECTED_E2E_ADMISSION_ORACLE_OK cases=28`. Expected facts therefore
come from the owner-approved literal oracle contract rather than from renderer
output.

The private wrapper requires the original protected verifier inside `try` and
writes only the already obtained admission body and status from `finally`. It
does not catch, replace, suppress, or convert the original `Throwable`. The
protected verifier's own outer `finally` remains responsible for stopping the
server, closing DB handles, dropping the owned database and user, and cleaning
the task-owned artifact root before the exception propagates. The archived log
retains the original fatal exception and stack. This is diagnostic mismatch
evidence, not a skip, waiver, allowed failure, missing-production RED, or full
E2E GREEN claim.

The unapplied patch has exactly two additions and two deletions on two physical
lines. The first deletion contains one stale table-link assertion and is replaced
by one oracle call on actual `$queueAdmission['body']`. The second deletion
contains the stale six-heading table assertion and stale table-link assertion and
is replaced by one oracle call on actual `$queue['body']`. Thus all three and
only the three stale assertions are replaced, and both independently obtained
HTTP bodies are checked.

Applying the patch to a temporary copy reproduced the stated before and proposed
after hashes exactly. The complete diff contains no other changed byte. The
status-200 prerequisite, response parsing, `pefRedesignNoRaw`, actor19 and
missing-ID probes, exact grant and revoke checks, negative principals, DB/process/
storage snapshots, transport equality, redaction checks, the complete downstream
journey, and normal/failure cleanup remain byte-identical. `git apply --check`
passes, and `php -l` passes on the temporary proposed file.

The oracle implementation has a separately owned Gate 5. A reported HTML
form-feed class-token edge case is under that review/correction cycle. This Gate 3
approves only the exact protected assertion patch and does not approve or repair
the oracle implementation. The protected patch must remain unapplied until the
oracle's final corrected bytes have completed focused GREEN and independent
Gate 5 with `APPROVED`; any oracle correction must also preserve the public seam
and the semantics assumed here.

This approval grants no whole-E2E, downstream manual-registration golden,
implementation, CI, publication, or launch approval. After the oracle prerequisite
is complete, only this exact patch may be applied; the full protected verifier
must run without skips and retain every downstream failure, followed by the
required full verification and a distinct protected-integration Gate 5.

## Required changes

None to patch SHA256
`2fadffb1b9ca4269f03f090a8347155f5b9f005f6d1f3874ac43f758b3e9d33d`.
Do not apply it until the separate oracle Gate 5 prerequisite above is complete.
