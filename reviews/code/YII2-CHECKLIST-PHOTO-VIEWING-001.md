# Gate 5 final review: YII2-CHECKLIST-PHOTO-VIEWING-001

- Reviewer: independent Codex reviewer `/root/photo_final_review`; authored none of the specification, tests, implementation, or Gate 3 record
- Executor: `/root/photo_executor`
- Base: `70590d392cc481541956f26d5804e9b35c93607a`
- Reviewed exact source: `cfab93e55d5823ac98a4fa209e4a461b877e44795c665fb57009f131f15a7b6f`
- Reviewed pre-review working-tree snapshot: `git diff --binary 70590d392cc481541956f26d5804e9b35c93607a -- . ':(exclude)reviews/code/YII2-CHECKLIST-PHOTO-VIEWING-001.md'`, SHA-256 `393144228358d3dc4e637c88d503f7686adc64c3e718f4e7e16363241a0d0d34`
- Specification: `specs/YII2-CHECKLIST-PHOTO-VIEWING-001.md` v1.0
- Controlling Gate 3: `reviews/tests/YII2-CHECKLIST-PHOTO-VIEWING-001.md`, final rereview revision 4, verdict `APPROVED`
- Reported exact-source evidence: new HTTP GREEN; new browser GREEN; existing inspection journey GREEN; existing inspection browser GREEN; `make architecture-check` GREEN with advisories only
- Verdict: `APPROVED`

## Findings

None.

## Review

1. **Authorization and disclosure containment conform.** `photo-read` accepts only GET/HEAD under the canonical positive-integer route. The controller applies the existing authenticated-user flow and object-level `read` decision before resolving the photo. Missing objects, unknown photos, cross-object photos, and revoked photos therefore return 404 without blob metadata; an authenticated actor without read access receives 403. Upload or revoke permission is not required for viewing.

2. **Private storage handling conforms.** The read model selects only the active photo row belonging to the object's installation case. The controller accepts only the existing 64-hex `.bin` internal storage identity and persisted image MIME allowlist, constructs the path only below the existing private `checklist` directory, rejects symlinks and non-regular/missing artifacts, verifies persisted size before and after reading, and returns safe 503 plus `Retry-After` for storage/integrity failures. No original filename, storage name, absolute path, bytes, or exception text is exposed by refusal responses, and the read path performs no recovery or mutation.

3. **HTTP representation conforms.** Successful GET returns the stored bytes with persisted MIME, exact length, `nosniff`, `private, no-store`, and an inline generic MIME-derived filename. HEAD uses the same action and headers; the verified runtime suppresses its body. Verb filtering and the explicit GET/HEAD route preserve 405/Allow behavior for other methods.

4. **Projection and revocation conform.** Every controller-produced checklist projection decorates active server photos with an object-bound same-origin `viewUrl`, including page load and mutation responses. Existing projection filtering continues to exclude revoked rows; the direct read model also requires `revoked_at IS NULL`. The implementation does not change photo persistence, revocation history, checklist revision, progress, completion, crew, marks, queues, or private-file lifecycle.

5. **Browser behavior and local queue containment conform.** Both the section strip and gallery prefer an existing local `previewUrl` and otherwise render/link the durable server `viewUrl`. Load failure keeps the card, shows the exact required copy and retry control, and increments a card-local monotonic retry query on the same base URL. Retry only changes the image request and does not enter the checklist operation pipeline. The IndexedDB stores, local blob preview, upload payload/order, and queued/sending/error state machinery remain in the established paths; the focused browser evidence covers clean reload fallback, retry, revocation in both views, and the adjacent queue-state regressions.

6. **Overlap review found no conflicting ownership.** The change adds one read-only repository method, one controller response seam, projection decoration, and presentation behavior. Existing upload/revoke writers and offline synchronization remain the sole mutation owners. Route ordering keeps the more specific photo-read pattern ahead of the checklist page and generic compatibility aliases.

## Verdict

`APPROVED`

Gate 5 is approved for the exact source and snapshot above. This verdict does not claim exact-source GitHub CI, PR readiness, merge, deployment, or settings changes; those remain subject to the repository's subsequent delivery gates.
