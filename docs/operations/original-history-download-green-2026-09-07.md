# Production original history/download — scoped GREEN

Spec ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001 v0.1, Gate1/v2 and Gate3
APPROVED. Tests/RED commit cfd5a27; source
 eec882f274902c3d4842aa73d4665411b0a855aa.
Tests unchanged throughout GREEN; final Gate3 hashes retained.

Production port remains AssignmentOrderOriginal-owned. readHistory returns exact
13field paged metadata; prepareDownload returns exact9field metadata containing
an immutable9field revision and fully verified bytes. Both methods own idle-only
read-only DB snapshots; prepared FS work starts after snapshot release. Existing
selected/source/StoredReader/lineage/request/audit/event proof is reused; no
Diagnostic EvidenceReader calls or SQL outside the owning module.

Download checks native content identity, canonical private root owner/mode,
regular non-symlink single-link PDF and digest lock, lstat/fstat coherence, exact
byte size/EOF/hash under existing readonly shared nonblocking digest lease. It
creates/chmods/repairs nothing. Special permission bits are also excluded by the
exact mode mask. Fully checked buffer is returned only after release/close; lookup
failure contains neither private identity nor partial bytes. No retained handles,
actor grant, HTTP endpoint, application/opening fact, DDL or migration version.

## Verification

Native `original_history_download_001_test.php`:18/18 PASS,18setup/cleanup, exit0.
Both prefixes0/25, two independently fixed PDFs and accepted20MiB upper-bound PDF;
old/current values, pagination/new correction, accepted-root cross-binding,
missing/config/transaction/corrupt backing, root/bytes/modes/alias/lock negatives,
owner exclusive lease→bounded separate worker unavailable, repeated lease release
and native stream counts all passed. Both worker observations printed
NATIVE_DIGEST_LEASE_EXCLUDES_DOWNLOAD. Final raw green-final.log.

Related regressions all exit0:
- original_application_reference_001:14cases, both case-lock workers;
- assignment_order_original_data_lineage_001:106checks;
- selected_original_binding_001:verification and production constructors;
- original_upload_http_flow_001:direct/replay/correction/historical binding.

Architecture7 PASS. Lint all11new production files +3new test/helper files PASS;
git diff --check PASS. Every new production file <150lines; no baseline change.
Initial GREEN also passed; final rerun follows exact special-mode mask tightening.
Primary external root:
/Users/antropophag/.local/state/fmonitor2-verification/original-history-20260907.

Independent Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001.md. This closes no parent HTTP/all-role/restart/fullVERIFY
requirement. Assigned-engineer scope still needs native application ownership;
reapplication-date answer is still pending. Last full makeverify source18916ae
failed only known protected bootstrap/E2E; it does not include this source. No
VERIFY_OK, new preview deployment, remote mutation or launch approval claimed.
