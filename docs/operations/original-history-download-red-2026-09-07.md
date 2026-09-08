# Production original history/download — native RED

Source HEAD58203f3945d00c17ca1c96d5ec446aa40d618d86. New change
read-assignment-order-original-history-download; spec
ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001 v0.1 Gate1 +literal-v2 APPROVED,
final SHAca1a153bafdf9a08f0d74e2219a3b90c59a14f24da9a56b0bf34f727f100fd11.
No production history reader or HTTP/grant/application changes yet.

`php tests/AssignmentOrderComposition/original_history_download_001_test.php`
with PATH Homebrew/Docker and explicit FMONITOR_TEST_DB_HOST127.0.0.1,PORT23306,
ADMIN_USERroot,ADMIN_PASSWORDfmonitor2_test_root_local:
final red-v3.log exit1,18SETUP_OK/18CLEANUP_OK/18intended failures.
Every case first accepts native selection +two distinct native PDF revisions;
max-size case additionally accepts real20MiB third revision before target assertion.
Actual failure: missing public production history factory, Expectedtrue/Actualfalse.
Future metadata/FS/worker assertions remain behind that absent seam, not claimed GREEN.

Tests cover exact pagination/metadata/historical buffers, returned copy isolation,
new correction/cursor and new pending order, both-way cross-order revision lookup
with two accepted roots, typed invalid/configuration/DB/transaction cases, corrupted
DB backing, missing root/storage aliases/corrupt byte counts/digest lease, max20MiB.
Busy digest lease uses existing public storage owner plus bounded independent PHP
read worker; parent keeps exclusive lease until worker returns unavailable. This
avoids deadlock/hang if a candidate implementation mistakenly uses blocking flock.
All DB rows/DDL and private filenames/hashes/modes/owner/link counts are observed;
no permission probe or function/native interception. Runtime cannot use diagnostic
EvidenceReader. Reuses existing approved fixture and bounded worker control only.

Earlier RED logs retained. Before final capture author corrected nullable observer
signature, added accepted-root cross-order checks, and replaced same-process timing
with bounded native worker. No production changes during these iterations.

Final reviewed artifact SHA256 candidates:
- test d5507a8374c1d654ab1b09317a45ddc91e8d4493e5c0d1073835d0e6ce57db2d;
- OriginalHistoryFixture3444175bd365238680cd937bce5cdf4cde549746d43aef1e78e369e6f2d3a5d0;
- worker b3c23b8af3efd3c7ccaf8dac71c0a80ab4ceeb5209e478e08a9da2e80ee16ae0.

Primary root: /Users/antropophag/.local/state/fmonitor2-verification/original-history-20260907.
Independent Gate3 required before implementation. Parent all-role HTTP/fullVERIFY,
application date decision and native opening remain unchanged and unclosed.

Independent test review requested stronger root/PDF/lock mode and digest-lock alias
checks plus explicit transaction-idle assertion. Added root0750 positive and
root0755/PDF0644/lock0644 validation negatives: all remain readable, so this is
metadata validation, not an OS permission-denial/privilege/owner probe. Added
symlink/hardlink/directory digest-lock cases and root lstat mode/UID/link-count
to before/after observation. Final REDv4 exit1,18setup/cleanup/intended failures.
Final test SHAe16e41c2ecc548b748582d02c91965f1bf908cb9d9d03fd38f3da8da87566093;
fixture749294f33774688faf8c734edf31397cd54f9f05071e25985663e7bd1f3015e7;
worker SHA unchanged b3c23b8af3efd3c7ccaf8dac71c0a80ab4ceeb5209e478e08a9da2e80ee16ae0.
