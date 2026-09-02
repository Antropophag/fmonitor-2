# Test review: CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001

- Gate: 3 — independent test review of corrected 12-hex namespace contract
- Reviewer: separately tasked agent `/root/photo_upload_token_length_test_review`
- Independence: this reviewer did not author the specification, test, oracle, or verifier
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_upload_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-upload.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Review result

The corrected specification and test consistently require exactly 12 lowercase
hexadecimal characters. With prefix `photo_verify_<token>_`, the longest declared
fixture table is `fm2_checklist_template_associations` at 61 bytes, below MariaDB's
64-byte identifier limit. The 12-hex token retains the specified 48-bit namespace.

The malformed probes are raw public inputs and cover missing, 11-character
lowercase hexadecimal, 12-character uppercase hexadecimal, and a 12-character
separator/non-hex value. Each must produce exit `2`, empty stdout, and exactly:

```text
SETUP_FAILURE: photo verifier run token is invalid
```

Every malformed probe compares the complete visible table-name set and artifact
tree before and after, plus exact fingerprints of an ambient decoy and an explicit
foreign valid-12-hex SQL/storage namespace. Thus rejection must occur without
mutation.

## Isolation and sensitivity

- SQL and storage collision probes use separate valid 12-hex owned namespaces,
  require the exact occupied-namespace failure, fingerprint the colliding object,
  and prove restoration of the baseline after fixture cleanup.
- `/tmp`, lexical `..`, and a repository-local symlink root remain exact path-safety
  probes and must fail before creating SQL or storage state.
- Two successful namespaces retain exact transcript, replay/idempotency, retryable
  storage failure, literal PNG metadata/hash, byte-identical repeat, and complete
  owned-namespace cleanup coverage.
- The table-list helper ignores well-formed verifier namespaces belonging to other
  concurrently running tests so independent runs do not spuriously fail. This does
  not hide destructive behavior: the test's explicit foreign valid-token table and
  storage child are fingerprinted after malformed, collision, unsafe-root, nominal,
  repeat, and unavailable-DB probes, while each test-owned token is checked directly
  for residue.
- Final cleanup is bounded to table names discovered under the test's explicit
  owned tokens and exact generated paths. The explicit foreign decoys are removed
  only by their exact names in the meta-test's own `finally`; no sibling discovery
  grants ownership.

## Reproduced RED evidence

With the disposable MariaDB healthy, two sequential executions and two concurrent
executions all exited `1`. Each reached the SQL-collision assertion and reported
that the current partial verifier rejects the now-valid 12-hex token as invalid:

```text
Expected: 'SETUP_FAILURE: photo verifier owned namespace is occupied\n'
Actual: 'SETUP_FAILURE: photo verifier run token is invalid\n'
```

This is a behaviorally relevant RED against the public verifier, not a setup
failure. The current verifier was inspected only to identify the cause: its partial
implementation still accepts 16 hex characters. It is not approved by this Gate 3
review. After all four runs, no `photo_verify_*`/meta-test decoy table and no
`characterize-photo-upload-*` artifact or symlink remained.

## Reviewed hashes

```text
dad4a0e6cb48095b6c7b86d2d740b705379dcafb9d061838d8aa036c85de4311  specs/CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001.md
fcf6f7f3543e8920522abd282344c7c2dc7a583432cd527cdfc61ca0edebd49a  tests/Verification/characterize_inspection_photo_upload_001_test.php
```

Gate 3 is approved. Minimal implementation may correct the verifier's public token
grammar to 12 lowercase hexadecimal characters and proceed only as far as needed
to satisfy this reviewed RED.
