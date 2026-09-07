# Code review: ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001

- Reviewer: `/root/original_gate5`, independently tasked agent; not specification, test or implementation author.
- Implementation author: root implementation agent.
- Reviewed source: `eec882f274902c3d4842aa73d4665411b0a855aa`, against test source `cfd5a27`.
- Specification: v0.1; final Gate 1/v2 and independent Gate 3 APPROVED.
- Verdict: `APPROVED` for the bounded native history/prepared-download port.

## Findings

No blocking findings. Independently inspected all 11 new production files, the public-seam tests and reached owning-module helpers. Factory scalar validation precedes SQL/filesystem access and returns the specified fixed configuration exception. Reader argument validation precedes connection access; normal reads use the existing idle-only consistent read-only snapshot. Active caller transactions remain untouched. Prepared file work begins only after that snapshot is released; returned values retain no connection, transaction, descriptor or lease.

History reuses the validated registered-selection/current-root source, which checks complete lineage and request/audit/event backing before projection. Revision queries are restricted to that validated root. Download additionally uses exact binary revision identity comparison, so another accepted root's revision cannot be substituted. Pagination preserves ascending revision order, strict after-cursor selection and the same-snapshot total/current leaf; next cursor appears only when a later revision remains. Missing selected evidence and missing revision remain distinct from corrupt backing. A later pending order imposes no new latest-order policy.

Page and download metadata use the specified ordered whitelist and immutable scalar/nested-array values. Private content identity is carried only within the owning reader to its file adapter. The prepared value returns copied immutable metadata/bytes, with no live handle or obligation to close. No diagnostic EvidenceReader, environment-driven fallback, user-directory substitution, grant or HTTP authorization is introduced.

The PDF adapter accepts only the exact native identity derived from the validated SHA-256. It checks canonical root path and inherited ancestor/root policy, then strict process ownership and full mode bits. Both existing digest lock and content file require regular non-symlink, single-link, mode0600 identity. Descriptor/path dev+inode coherence is checked during open and again around reading. The existing digest lock opens read-only and uses shared nonblocking flock; there is no file creation, chmod, repair, inventory/state read or waiting loop.

The read is bounded to the accepted byte size plus one detection byte, requires EOF, exact size and SHA-256, and rechecks descriptor/path coherence and size afterward. A successful return expression still passes through finally: file close, lease release and lock close complete before a prepared value can be returned. Release/close failure throws and the public lookup returns unavailable without partial bytes. Failed acquisition attempts close any acquired local handle; the outer cleanup also releases previously obtained resources. Historical bytes may be prepared after a correction because the selected immutable revision remains the authority.

All additions remain within AssignmentOrderOriginal; no existing production file, domain writer, audit/history fact, migration, schema baseline or grant is changed. Files stay below the established size boundary. No broader filesystem-race or OS-fault test coverage is claimed than was actually executed; the explicit coherence and cleanup paths were inspected as part of this review.

## Verification evidence

Independently matched all 11 source hashes in `green-manifest.json` and the three test/fixture/worker hashes from Gate 3. Inspected `green-final.log`: 18 native setup/PASS/cleanup records, including both prefixes, distinct historical/current PDF hashes, exact metadata/pagination, preserved prepared values, cross-root rejection, corrupt backing, connection/transaction refusal, root/file/lock mode and alias rejection, two accepted 20MiB PDFs and two real nonblocking digest-lease worker exclusions. The observation helper checks transaction idle state, native stream-resource count and unchanged complete DB/filesystem snapshots after reads.

Inspected the four regression logs and recorded exit0 results: original reference 14-case matrix with both native case-lock workers; original lineage 106 checks; selected binding verification/production constructors; and original HTTP flow/replay/correction/historical binding. These native executions belong to root. Independently ran lint for all 14 changed/new PHP production and test/helper files and `git diff --check cfd5a27 eec882f`: PASS. The manifest and operations record report seven-rule architecture PASS; no boundary baseline was changed.

Evidence root: `/Users/antropophag/.local/state/fmonitor2-verification/original-history-20260907`; operations record: `docs/operations/original-history-download-green-2026-09-07.md`.

## Required changes and scope

None for this bounded dependency. Trusted consumers must still authorize actors and object scope before calling it. Parent all-role history/download HTTP, assigned-engineer authority, application/date policy, opening, restart and full exact-source verification remain separate gates. No parent Done, `VERIFY_OK`, deployment or launch completion is asserted. Only this review record was written; no production/test/spec edits or commit were made by the reviewer.
