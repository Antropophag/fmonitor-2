# Fresh independent Gate 1 review — assignment-order original worker safe-log v5

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_evidence_gate1`  
Reviewed commit: `d229f1e60ac7ac5e2cec97cb01904f39a7adad75`  
Scope: full current executable/OpenSpec package, with emphasis on the v5 worker
safe-log binding amendment; no tests or production implementation reviewed  
Verdict: **NEEDS_CHANGES**

## Exact reviewed artifacts

```text
55d2880545d2561c5fe971b2a80c201301bf86f0429671b4cf79332035cbc698  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d4c7d7344ed621887423cdfe25618c96256f0ffc4615299ab32aaf5724783ac7  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
a610448f04bb00bf62db39c449ff108cefa8af4020f77fe831ecc4fae1497320  openspec/changes/replace-pilot-registration-with-original-upload/design.md
ccabc6790824d224b483391431599b3f237cb92b730c041fca2b0b1648c3fd8f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b997b32f00947eb9d070364c4bd5afafc7a3eafc01b2918701aeae24fb1502e7  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Canonical context and process consulted:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## What is correct

The amendment closes the original observation-path identity gap in the intended
direction. `AssignmentOrderOriginalWorkerConfig` now contains an explicit
`safeLogFile`, and exact worker JSON keys inherit that field with no extras. The
worker and evidence-reader configs must carry the same canonical path identity
for a run. The real observer binds exclusively to that path; environment,
mutable global, default basename, private-root convention and callback selection
are all forbidden.

The file must be a pre-existing absolute canonical non-symlink regular file
outside the repository, owner/root-owned at exact mode `0600`, with no
NUL/control or `..` component. It is validated before password content or DB
access under the already approved evidence-config path rules. Worker does not
create or repair it. These rules are coherent in executable spec, proposal,
design and delta, preserve the evidence reader's read-only ownership, and do not
introduce a second mutation seam or test-private SQL/schema inference.

The prior public fixed exception, evidence-reader totality/cached-close behavior,
password byte grammar, closed JSON shapes, five dedicated FD protocol, bounded
waits, child termination/reaping and no serialized connections remain intact.

## Blocking finding

### The mandatory worker `finally` cleanup omits the new safe-log file

Section 13 requires every verifier-owned artifact to be removed in `finally`.
The v5 contract explicitly makes `safeLogFile` a verifier-owned pre-created file
outside both repository and private root. But the exact section-16 worker
cleanup sentence still says the parent “removes only owned
prefix/root/config/password files”; it does not include the safe-log file.

Because the safe-log is not inside the private root and the worker is forbidden
to create/repair it, neither root deletion nor worker lifecycle can account for
it. Reading that list as exhaustive leaks an owned test artifact; reading the
generic section-13 rule as implicitly extending it contradicts the exact list.
The Gate 2 author therefore has no single normative five-FD cleanup procedure.

Amend the section-16 `finally` list to include the validated verifier-owned
`safeLogFile` explicitly, after the evidence reader has closed and all children
have been terminated/reaped. Preserve target validation and the rule that only
verifier-owned artifacts may be removed. Mirror that lifecycle in design/delta
if their current wording is intended to be exhaustive.

## Verdict basis

Factory construction, path identity, observer binding and evidence reading are
otherwise exact and sufficient for real commit/release-fault observation. The
remaining cleanup contradiction is observable isolation behavior required by
Gate 2 and by the specification's own verifier contract. Task 2.2 is therefore
not yet fully constructible, and task 1.9 is not ready for owner approval at the
current hashes.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ (concatenate all php fences under one namespace) | php
PHP_DECLARATIONS_OK

$ git diff --check
PASS (before adding this append-only review; no output)
```

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
