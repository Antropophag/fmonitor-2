# Fresh independent Gate 1 rereview v7 — assignment-order original evidence reader

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_evidence_gate1`  
Reviewed commit: `e84074a6daecf9979803a35d41e59a5c7f73066e`  
Scope: closure of v6 evidence-reader findings and coherence with the full current
`ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` / OpenSpec package; no tests or production
implementation reviewed  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed artifacts

```text
fe0e9e95b0918e07d9313a15472b530ef6ba0c8bb6dd0287f9d94470beda690a  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a3f115412cb104d34c5c9f4991f56f7d4963552b3da03869ce1d8d95bb45f615  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
3c901ee220961cafee285cd3b8937cd04e0827c3e706316cc0f11c29dd0b77ab  openspec/changes/replace-pilot-registration-with-original-upload/design.md
aa2547e5e5a67f887014212e20b7e879d371d2c051fc39f56f9899fce0353315  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
39bfe87314f3e8d00fe71b5c2846bc55b40c9adc4e0e18322af767ad21e1ea4e  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
caacc24de7dd71e14bc87fb3b5455a2b6f875bfc2cd0f2e5c2d6536e6109ce5e  docs/operations/pilot-assignment-order-original-gate1-rereview-v6.md
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

## v6 finding disposition

### Public failure totality — resolved

The contract now declares one final public
`AssignmentOrderOriginalEvidenceUnavailable` whose constructor fixes message,
code and previous exception. Factory construction and every reader operation
either return their complete declared value or throw only a fresh instance of
that redacted type. This gives Gate 2 an implementation-independent assertion
surface and forbids partial JSON and leaked database, secret or path diagnostics.

`close()` attempts every live descriptor/connection on its first invocation,
caches the aggregate outcome and never repeats I/O. A successful first close
makes later calls normal no-ops; a failed first close and every later call throw
the same fixed public failure shape. Partial construction and adapter failures
are covered by the factory/read totality rule, so no internal exception becomes
observable.

### Exact config grammar and pre-access order — resolved

Host, port, database, user and prefix now have bounded exact ASCII grammars;
the prefix explicitly permits length zero. The host expression was also checked
as a valid character-class pattern including IPv4/IPv6 bracket/colon syntax.
All three paths must be absolute canonical non-symlink paths outside the
repository with no control/NUL/`..` component. The private root must already be
an owner/root-owned directory at exact mode `0700|0750`; password and safe-log
must already be owner/root-owned regular files at exact mode `0600`. The factory
does not create or repair them, including a missing safe-log.

Scalar/path/metadata validation completes before password bytes or database
access. Path construction is therefore exact. One password-byte discrepancy
remains, recorded below.

### Shape reference and ownership — resolved

The factory now points to section 16, where all eight recursively key-sorted,
versioned, closed JSON shapes are defined. The factory owns fresh connection and
filesystem readers; the reader alone knows canonical tables introduced by this
change. Tests cannot provide SQL, infer schema, inspect `information_schema`,
inject selectors/callbacks, or use a command repository. Reads are observational
only and cannot feed maintenance enumeration or mutation, so the seam is not a
second mutation path.

## Remaining blocking finding

### Password byte alphabet differs between executable spec and delta

The executable spec permits every byte except NUL/CR/LF, with one optional final
LF. That permits control bytes such as TAB (`0x09`) and DEL (`0x7f`). The delta
instead requires `1..1024 non-control bytes`, which rejects them. Therefore a
TAB-containing password file is valid under one normative artifact and invalid
under the other, so Gate 2 cannot derive one independent boundary expectation.

Choose one exact byte alphabet and state it identically in executable spec,
delta and design. If “non-control” is intended, name the exact allowed byte
range (for example printable bytes with any deliberate non-ASCII policy) rather
than relying on a locale-sensitive term. Preserve the already exact optional
single terminal LF/removal, empty-result, CR, embedded-LF and NUL rules.

## Coherence and verdict basis

Executable spec, design and delta agree on path/config validation, no-create
policy, optional-final-LF framing, fixed failure type, cached idempotent-close
lifecycle and independent fresh-connection reads. Proposal and tasks retain the
same limited impact and correctly require owner exact-hash approval before
resuming task 2.2. Existing upload/maintenance ownership, append-only history,
authorization, private blob safety and deferral of HTTP, composition application,
opening and blocked legacy E2E remain unchanged.

The amendment is syntactically valid, establishes the necessary
fresh-connection MariaDB/private-blob/safe-log evidence owner, and does not
require private SQL, schema inference or a test-owned mutation surface. The
single contradictory password boundary nevertheless prevents executable/delta
coherence and an independent invalid-config test. The current batch is not ready
for owner approval; after aligning that alphabet it requires a fresh exact-hash
Gate 1 review.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ (concatenate all php fences under one namespace) | php
PHP_DECLARATIONS_OK

$ validate the four declared scalar regexes with PHP PCRE
host/database/user/prefix: valid; representative match returned 1

$ git diff --check
PASS (before adding this append-only review; no output)
```

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added. This review is
not owner approval; Gate 2 resumes only after an append-only owner decision names
this exact reviewed hash batch.
