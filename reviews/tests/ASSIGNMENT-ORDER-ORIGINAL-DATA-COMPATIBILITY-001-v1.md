# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-COMPATIBILITY-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Patch/helper author: `/root`; reviewer authored neither tests nor production
- Reviewed commit containing unapplied patch: `5d00d189d4a523a98b1b7a83272f654704b8a53a`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md`
  v0.6, SHA256
  `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Independent Gate 1 review SHA256:
  `ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`
- Candidate patch SHA256:
  `2ab9c6752263fa42ad06cd25ec6f80bab530cbe48c49a38e3f243adc463ec6fc`
- Verdict: **APPROVED**

## Exact reviewed evidence

```text
cc4286025f01397db306f012cad35b02e95d9d318470a63b93ad223bd091b4d2  docs/operations/original-data-integrity-compatibility-patch-v1-2026-09-06.md
4e7e38277f78b1a1eccf1a4e55fd4bcc2558454a90ef59c0ef9e25e469cce599  /Users/antropophag/.local/state/fmonitor2-verification/original-data-compatibility-oyy2ioh7/patch-evidence.json
0f5ac2c2c38a30699bb5d3069a0c5cfb2a150880f1c5734f16db091856b34fcf  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-WORKER-001-v1.md
```

The evidence manifest pins the complete copied application tree, base HEAD, all
11 before/after test/helper identities, nine exact commands and their logs. The
root worktree test/helper files remain unmodified.

## Patch scope

The patch changes five existing tests, five existing helpers and adds one
test-only compatibility helper. `git apply --numstat` reports only those 11
paths. It changes no specification, production source, protected E2E, skip,
allowed failure or runtime selector.

The patch reconciles old tests with already approved DATA-INTEGRITY behavior:

- explicit initial assignment-lineage absence before allocation/finalize;
- complete FOUND lineage metadata;
- fresh terminal recovery through a separate one-shot reader;
- canonical composition and commit DTO values;
- same-PDF stale loser before finalize;
- existing synthetic pure-port content identity retained where allowed.

## Compatibility interfaces — PASS

`AssignmentOrderOriginalDataCompatibility.php` defines test-namespace markers
only. When production complete-lineage/fresh interfaces exist, the markers extend
them; on RED bytes they extend only already available test-compatible interfaces
or no production port. No production name, alias, class or service is defined.

The complete-lineage marker adds the approved case/order/revision-list/current-
evidence methods only to FOUND-capable fixture values. Negative values remain
base-compatible. The fresh fake opens a distinct test reader, permits one read,
closes once and counts open/read/close separately. It never calls the ordinary
writer repository to masquerade as fresh recovery.

Conditional markers are limited to compatibility with absent-then-present public
declarations. New DATA tests independently pin the real production API, so these
markers cannot approve a wrong public symbol or constructor.

## Initial precheck and planned rivals — PASS

Initial, dynamic, shape, lifecycle and safe-log fixtures now expose the explicit
assignment-order lineage query. Ordinary no-root fixtures return valid NOT_FOUND;
unexpected extra calls or wrong pair fail rather than inventing absence.

The CAS-conflict isolation fixture no longer contains a rival before normal
precheck. Its before evidence is exact null. `commitAccepted` publishes the
planned rival only when it returns CONFLICT; the post-CAS assignment-lineage read
then returns complete root/revision/case/order/composition/current evidence.
After evidence proves the competing root exists and the current command added no
accepted fact.

This is a genuine planned race transition, not a false initial NOT_FOUND over a
pre-existing root. Traces gain one normal precheck read and retain the separate
post-conflict fingerprint/lineage rereads, lease release, diagnostic and terminal
conflict audit.

## Fresh recovery expectations — PASS

Lifecycle unknown/commit-throw cases replace ordinary `request` rereads with
literal `fresh.open`, `fresh.read`, `fresh.close` traces. FOUND, reliable miss,
unavailable, thrown read and malformed getter retain their existing accepted,
persistence-failure or outcome-unknown expectations and lease/delivery rules.

Gate5Domain's deliberate terminal-attempt Throwable receives an explicit fresh
reliable NOT_FOUND and asserts open/read/close exactly once. Confirmed rollback
cases assert zero fresh calls. This preserves the original failure Result while
proving it from absence on the fresh port rather than reusing the writer.

The five old scripts that do not need new behavior remain green in the copied
candidate. The safe-log, lifecycle and domain tests fail specifically because
unchanged production lacks the new precheck/fresh protocol, demonstrating RED
rather than a compatibility setup failure.

## Complete metadata and canonical value repairs — PASS

Gate5Domain composition hashes for orders 81 and 82 are literal independently
verified hashes of the exact canonical one-installer JSON:

```text
order81 7c824b76b7999bc74e2c8f1fbda74e6e07f388be9b85851c4eebb0d57ec1aaa0
order82 9e5c95c9133e9c4fe7858b2d337977368c10647bf2f9a162ec14edc4f102b96e
```

FOUND lineage includes case/order, ordered revision IDs and current date/PDF
evidence. The two-order initial control uses distinct root/revision IDs, so it no
longer relies on impossible global identity reuse while preserving independent
assignment ownership.

The real missing-prefix MariaDB test now supplies the exact canonical Example A
fingerprint, composition hash, PDF hash/size and digest-bound content identity.
The expected repository ROLLED_BACK result remains unchanged. Future pre-SQL DTO
validation therefore cannot mask the intended real missing-table SQL failure.

Pure in-memory lifecycle fixtures retain `private-content-0001`, explicitly
allowed by DATA-INTEGRITY and COMMAND-LIFECYCLE. The patch does not rewrite pure
port oracles to imitate a filesystem filename.

## Worker race amendment — PASS

The existing same-PDF, differing-date race remains a deterministic winner/stale
control at `AFTER_FINGERPRINT_MISS_BEFORE_CAS`. Under v0.6 the loser detects stale
current state in normal step 11 before finalize and owns no lease. The patch
changes only its obsolete loser-log expectation to an empty list.

All stronger assertions remain: accepted winner plus stale terminal loser,
accepted/conflict audits, winner-only revision/fingerprint/event, loser absent
from domain/blob evidence, unchanged process/private-blob inventory and no extra
orphan.

Actual post-finalize CAS and release-failure coverage is not deleted. The
separately Gate-3-approved DATA-WORKER test uses distinct valid 327/328-byte PDFs,
pauses both after private finalize, proves A commit before B release, observes B
post-CAS stale result plus one `commit_conflict` release diagnostic, and preserves
the permitted unreferenced losing digest as a private orphan with no domain fact.
Its Gate 5 must also inspect source to prove the exact CAS call because the public
worker has no persistence observer.

## RED evidence — PASS

All 11 candidate files lint. Nine affected old scripts ran against unchanged
production in the copied tree:

- PASS: upload, upload-validation, dynamic ports, command shape and real
  Gate5-MariaDB;
- intended RED: safe-log isolation, command lifecycle, Gate5Domain and worker
  transport.

The four RED scripts exit 255 at approved missing precheck/fresh-call or obsolete
lease-log assertions. The five passing controls show the patch does not make the
whole old suite fail through declarations or setup. Command/log hashes and every
before/after file hash match the private evidence.

Expected traces and DTO values come from DATA-INTEGRITY v0.6, its independent
clarification reviews and literal Example A values, not current production
output. The production manifest is byte-pinned and unchanged during candidate
runs.

## Sensitivity and preservation

The patch catches missing initial precheck, pre-existing rival disguised as a
race, incomplete lineage metadata, writer-connection recovery, missing fresh
close, noncanonical composition/commit inputs, reused root/revision IDs and the
obsolete pre-finalize loser lease log.

It preserves authorization, selected Results, cleanup, audit, delivery,
fingerprint, process-state and evidence assertions. No failure is converted to a
skip or accepted as allowed. Protected E2E is untouched.

## Findings and disposition

No blocking traceability, API-compatibility, expected-value independence,
source-copy, RED-classification, race-publication, fresh-reader, canonical-value,
worker-coverage, determinism or scope finding remains.

**APPROVED** for applying the exact compatibility patch as part of minimal
DATA-INTEGRITY v0.6 GREEN after all new DATA test parts retain their independent
Gate 3 approvals. This review does not approve production implementation, Gate 5,
combined original command or launch readiness.
