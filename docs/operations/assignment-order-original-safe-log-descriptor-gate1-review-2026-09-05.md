# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 safe-log descriptor — technical Gate 1 review

- Review date: `2026-09-05T13:25:26Z`
- Reviewer: fresh independently tasked technical Gate 1 reviewer
  `/root/importer_authority_review`; reviewer authored none of the reviewed
  specification, planning, audit, test or production artifacts
- Reviewed repository HEAD: `4db6c004d444b129c56b33879e7f9cc896dc3762`
- Scope: narrow `G5-SAFELOG-2` retained-descriptor integrity clarification and
  deterministic public production-factory verification only
- Verdict: **APPROVED**

## Determination

The candidate is a conforming technical clarification of the existing owner
decision. The owner already required the production safe log to be a
pre-existing canonical non-symlink regular file, owned by the current effective
user with exact mode `0600`, validated before database/private-storage access,
never created or repaired by the factory, and represented externally by one
fixed redacted construction failure. Those properties necessarily apply to the
descriptor retained for append. Rechecking them with `fstat` does not introduce
a new product outcome, privilege policy or user decision.

The exact current candidate closes the `G5-SAFELOG-2` gap without weakening the
inherited contract:

- retained descriptor device/inode must equal the final non-following pathname
  observation;
- descriptor `fstat` must independently prove regular-file type, current
  effective UID ownership and exact permission bits `0600`;
- every open, `fstat`, identity or attribute mismatch closes an opened
  descriptor and maps through the existing exact
  `AssignmentOrderOriginalProductionConfigurationUnavailable` boundary;
- failure occurs before database access, private-root validation/access or log
  write and cannot create, repair, replace, truncate, append to or otherwise
  modify the file.

The existing exact exception contract remains applicable: class and message
`AssignmentOrderOriginalProductionConfigurationUnavailable`, integer code `0`,
`previous=null`, with no path, secret or underlying exception detail. The
candidate therefore makes both the no-resource outcome and fixed failure shape
observable at the approved public seam.

No new owner approval is required. This technical review supplies Gate 1 only;
it does not supply RED, Gate 3, GREEN or Gate 5 and does not close the historical
code-review finding by itself.

## Constructibility and bounded verification

The approved scenario is deterministic and implementation-independent enough
for Gate 2:

1. A task-owned child first proves an unchanged valid-control construction at
   `ProductionAssignmentOrderOriginalFactory::create(mysqli, config)`.
2. A verification-only native interposer changes only the pathname metadata
   observation for one exact synthetic safe-log path. It preserves the real
   device/inode and reports regular/current-EUID/`0600`, while the real opened
   descriptor retains a safely created non-`0600` mode. This directly exposes
   the missing descriptor-attribute check without a timing race.
3. Fixture identity, real mode, reported pathname attributes, effective UID,
   loader activation and control behavior are setup prerequisites. Failure to
   establish any one is `SETUP_FAILURE`, never qualifying RED.
4. The assertion requires the exact fixed factory exception, zero database
   calls, no private-root touch, unchanged fixture bytes and metadata, and no
   retained descriptor. Cleanup is bounded to revalidated child/interposer/
   fixture artifacts; no recursive, wildcard or ambient cleanup is permitted.
5. No real document, production path, privilege change, external target,
   probabilistic replacement loop, production callback or production runtime
   selector is needed.

The phrase that the interposer is unreachable from production “environment” is
read consistently with the surrounding contract: production PHP/configuration
must not inspect an environment flag or expose any application selector for the
behavior. The verification parent may invoke the private child through the
platform's test-only native-loader mechanism; otherwise a native interposer
could not be loaded at all. The library/path/token are test-owned inputs to that
child and are not production configuration, request, CLI, global or service
locator inputs. Gate 3 must reject any proposed production-code selector.

Mode disagreement is the mandatory portable RED sensitivity case. It does not
reduce the normative descriptor checks to mode alone. Regular-file type and
exact effective UID remain mandatory production assertions, and Gate 5 must
reject an implementation that adds only the mode check. Additional safe
synthetic type/UID cases may be added if the test platform can establish them
without privilege changes, but their absence cannot weaken the exact spec.

“No retained descriptor” must be demonstrated inside the live child after the
factory throws, rather than inferred only from process exit. A bounded open-FD
inventory for the exact fixture identity, or another independently reviewed
child-local observation, is suitable. The expected fixed exception and zero
resource/file-mutation facts must be asserted independently of implementation
text.

## Planning coherence

The four current OpenSpec artifacts consistently carry the same narrow change:
mandatory descriptor identity plus `fstat` type/UID/mode, close/fixed-redacted
failure, verification through the real production factory, unchanged control,
no resource touch or file mutation, and bounded test-only interposition. Task
1.34 correctly remains open for this review; tasks 4.3 and 5.3 correctly require
fresh Gate 3 before implementation. Existing approvals and completed tasks are
left as append-only history.

This review does not reopen the owner-approved `safeLogFile` policy, alter the
separate worker/evidence-reader safe-log contracts, authorize production hooks,
or approve unrelated original-upload behavior.

## Exact reviewed SHA-256

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
9781ab565bf30e4a857cbf64886d8db43e15e773dab6fd3cb00ee6f4a3b75e56  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
9ef3c47fb1e6c93bd72f625656f399661a5541673f32270dba6da0a6d7f31ced  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
8f4bbe855260892b5f9edfee76e045662f8317de97bb9ffa3ae3b85868ae10ee  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bbc98abf1e14d563f01b94a93655f78a1b02649ed3087992d1ecd6ed30e8d76e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
ad0e68e16883a681af59aefe4f99e4b940c10caf7e2108d72ceb79804526c6ed  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
0e430ef58c62076feb1291b6742dd5cd61b1f3adce156dc60bf79055e018b97f  docs/operations/assignment-order-original-production-safe-log-descriptor-integrity-audit-2026-09-05.md
3da95e342c0f49d96cfd2d91aca12ba6eecdba3fb75777333fab04e0b1ae5ec6  docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md
98f99e8cc7201725225e5c4d2bc41c61905e606bbb776287782f66fee1e4c693  docs/operations/assignment-order-original-production-safe-log-descriptor-race-gate1-gap-2026-09-05.md
d9178b78bb08463e2e0ce1c89587ca9a413509125a40d8f5f44f93ece7088f8c  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-v55.md
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
eda63c2ca641a0eb141a6198e63137c1a2419233a53c48797b2be8886c60be19  tests/Support/css_lstat_swap_preload.c
c0b5b86646e654f52907c391309f879a0364af91f7f8da70c75a21e1b3f55b9a  tests/Support/css_lstat_swap_preload_probe.php
513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

## Final verdict

**APPROVED** for the exact current candidate hashes above. Gate 2 may add only
the narrow deterministic descriptor-integrity RED described here. Fresh
independent Gate 3 remains mandatory before minimal production changes. No new
owner approval is required unless a later amendment changes policy, privilege
requirements or the externally observable failure outcome.
