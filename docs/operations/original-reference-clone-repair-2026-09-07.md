# Original reference — cloned issuer repair

ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 requires binding to each
issuing reader instance. After initial scoped Gate5, root found shallow PHP clone
shared the WeakMap object. Separate independent Gate5 supplemental P2 finding:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone-finding.md,
CHANGES_REQUESTED. Prior approval/history retained.

External native reproduction on unchanged18916ae: CLONED_ISSUER_GUARD=matched,
healthy native selection/original, cleanupOK. Native test prepared in external
clone-overlay while fullmakeverify source stayed frozen; overlay app/bootstrap
symlinks reuse repo source, no interception or replacement implementation.
Four cases at prefix0/25 ×clone-before/after issuance pass setup/ownership/no-write
checks then intended RED: actual matched/matched/matched/matched, expected
matched/matched/unavailable/unavailable. Both own receipts and both cross-reader
directions are checked. Sentinel catches hidden caller commit/rollback.

Gate3 APPROVED exact test SHAdf73f74e28687686f118fe214212c5734c756b4a0ff71370fb83b6647bf2721f,
reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone.md.
Byte-identical test copied into repository only AFTER fullrun terminal exit2.

Source45d7a668170e49e9edbba13977513a4c4fcd7e7f adds public __clone with fresh
WeakMap. Copied native readers keep the same borrowed connection/source, but
never share issuance. No SQL, filesystem, schema, mutation or new authorization.
New4cases GREEN, exit0; original14case regression GREEN, exit0, including2real blocked workers.
Changed PHP lint and diff-check PASS. Architecture boundaries unchanged; exact
fullrun18916ae had architecture7/lint/unit/characterization/diff PASS and only
known protected bootstrap/E2E failures. No VERIFY_OK or launch claim.

External primary logs under original-reference-20260907:
clone-repro.php/log, clone-red.log, clone-green.log, clone-reference-regression.log.
Supplemental Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone-repair.md.
P2 closed, prior finding retained. All6tasks of reference change complete; not archived.
