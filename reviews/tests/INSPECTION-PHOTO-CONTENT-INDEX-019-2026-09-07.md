# Identical photo re-upload — independent contract and test review

Reviewer: `/root`; artifact author: `/root/auth_review`.
Verdict: **APPROVED** for the bounded v19 implementation.
Source base: `b662e1e3a08c206c06f4df40a992fbd083856c1b` (subsequent checkpoint is docs only).

## Authority and observable result

GRILL-007 (`inspection-photo-revoke-retention-owner-decision.md`) authorizes new
evidence identity after revoking identical content and permanent retention. The
v0.1 SQL-1062 transcript remains historical provenance; v0.2 explicitly replaces
its final outcome. No new upload/revoke capability or supervisor policy is added.

Reviewed public seams: canonical migration application/catalogue, v19 migration,
runtime schema admission and `ChecklistSync::accept`/projection. Upload permission
checks retain their existing HTTP admission owner. The revoke fixture supplies
the actual capability, current registered engineer and bounded reason; it no
longer mistakes authorization failure for the historical uniqueness defect.

## Exact reviewed artifacts

```text
4d3d5c63607036db4532dde59e10948d20cbfa3d0cec5f2d08be1b5b1ea3494e  specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md
57d32139c4718e0fb2268615ba03878fbb2fceb70a8e15eb25527cbc04223965  rapid-pilot/verify-checklist-photo-revoke.php
0d695d528b8ecc915ce3d1eb2a5c04c69ffd2b9549f6e74c32f487916904c46e  tests/Verification/characterize_inspection_photo_revoke_001_test.php
d7a4dbea75a91950fb8ab3f59881b2be466b7a84d853c5e5c15f5c6529abadc4  tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
```

## RED and review findings

Executed by reviewer:

```text
php tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
exit 255: INTENDED_RED: canonical photo content-index migration v19 is absent
```

This fails before DB creation for the absent specified public migration, not an
environment error. All later assertions remain executable requirements; no GREEN
or target behavior proof is claimed yet.

Initial review found that repeating v19 alone would miss the full runner rejecting
the successor at historical v8. The revised test now runs the actual full catalogue
twice, requiring versions 1–19 followed by no applied versions. Historical literal
v8 remains exact; the current catalogue/runtime must explicitly recognize final v19.

The schema test observes populated revoked-photo and operation rows, allocator,
ordered non-unique lookup, unique operation identity, malformed-index refusal and
opposite-prefix preservation. Characterization observes a new photo/operation,
revision 3, one active and one revoked row, three ordered operations and one blob.
The entire earlier row equals its post-revoke snapshot. Fresh active duplicate and
revoke retries retain zero-mutation fingerprints; meta-test checks exact audit,
deterministic transcript and owned cleanup/foreign decoys.

Existing same-case concurrency/photo-limit and HTTP authorization checks are
required focused regressions. Their unchanged contract is sufficient for this
index-only policy correction; no new rare-case matrix is required. Any fixture
correction discovered during execution must preserve these outcomes and receive
supplemental review. Implementation/code review and populated deployment proof
remain pending. No stand/user data was used.
