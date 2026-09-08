# Fresh independent Gate 1 rereview v6 — assignment-order original evidence reader

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_evidence_gate1`  
Reviewed commit: `9f110a9e35e543b74eeb3205ff7ff205af9ce1b4`  
Scope: evidence-reader factory/config amendment and its coherence with the full
current `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` / OpenSpec package; no tests or
production implementation reviewed  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed artifacts

```text
df6f7dffc4b13d2d3a244c49452448eed5dddbb6e4308eba5ac5c7aaa1950490  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a3f115412cb104d34c5c9f4991f56f7d4963552b3da03869ce1d8d95bb45f615  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
2ef5d38a7b3dcd12a9e03a622df327b2c85ade8ec7e639f7777634f869773ccc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
aa2547e5e5a67f887014212e20b7e879d371d2c051fc39f56f9899fce0353315  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
db7351193ffee758479a2dae9ef2ad128f7f96e5208adc0f3a31544f8e3046f5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Prior authority and constructibility evidence consulted:

```text
60d75d17230e1a75ca3b8a729bf120eddfd13f2b2439396165385956f5c44542  docs/operations/pilot-assignment-order-original-gate1-rereview-v5.md
16dcf48cea1f6d1210d5f97af073a1ea011ddd09dcc50ade73c3850ccb1825ad  docs/operations/assignment-order-original-upload-v4-owner-approval-2026-09-04.md
498d4ce618eed5c8edfaa115fbc360cd96a30c8df9fbcc74d4faead95451aed5  docs/operations/assignment-order-original-upload-gate2-evidence-reader-gap-2026-09-04.md
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

## What the amendment resolves

The amendment closes the central gap recorded at `5ce2069`: Gate 2 now has a
named public factory, a serializable config, an independently owned fresh
MariaDB connection, private-root/log observation, and explicit idempotent
`close()`. The reader owns knowledge of the canonical original-evidence tables;
tests are forbidden to supply SQL, table selectors, callbacks, schema discovery
or command repositories. The eight closed top-level JSON evidence families are
already stated in the executable spec and the amendment does not create a
second mutation seam. Proposal, design, delta and task 1.8 consistently describe
this intent.

The PHP declarations themselves are syntactically valid. The read-only owner is
separate from upload/maintenance mutation owners, and the scope still excludes
HTTP, composition application and opening. No manual-registration or blocked
legacy-E2E behavior is restored.

## Blocking findings

### 1. Failure is promised as typed but no public failure type exists

The interface methods return only `string`, while `close()` returns `void` and
`create()` returns only the reader. Prose says any create/read/close failure is
“typed unavailable”, but declares neither a result union/status nor a public
exception class and its stable/redacted payload. A Gate 2 author therefore must
invent the failure type and cannot write the required failure assertions
independently of implementation. This is the same kind of private-contract guess
the amendment is intended to remove.

Declare one exact public failure protocol for factory creation, every read, and
close (for example, a named final redacted exception with a closed phase/reason
enum), including idempotent-close behavior after success and after partial
construction/read/close failure. Align the executable spec and delta scenario.

### 2. “Exact” config does not define a unique valid config grammar

`tablePrefix` is described only as “canonical ASCII `0..25`”; this gives a
length, not an allowed alphabet or whether the empty prefix is valid. Path rules
do not state whether `privateStorageRoot` and `safeLogFile` must already exist,
their required file kinds/permissions/readability, or who creates/opens the log
when absent. Password bytes are read from a `0600` file, but newline handling
and empty/NUL content are unspecified. Consequently two conforming-looking
factory implementations can accept different configs, and the RED author
cannot construct one authoritative positive fixture or exact invalid matrix.

Specify the prefix regex and exact path/file lifecycle, including existence,
type, ownership/permission/readability requirements and safe-log creation rule.
Specify password-file byte decoding/normalization and invalid contents. These
are verifier construction rules, not product behavior, but they are required by
task 1.8's exact-config acceptance criterion.

### 3. The JSON-shape cross-reference is incorrect

The new factory prose says its methods return shapes “defined in section 11”.
Section 11 is Audit; the closed evidence JSON shapes are in section 16. The
shapes themselves are sufficient to prevent tests from inventing table schema,
but the normative reference must point to section 16 so the factory obligation
has one unambiguous target.

## Verdict basis

The amendment establishes the right ownership boundary and avoids private SQL,
schema inference, test selectors and a second mutation seam. However Gate 1
requires observable, constructible acceptance statements before tests. The
missing public failure protocol and non-exact config grammar still force the RED
author to choose implementation behavior. The current batch is therefore not
ready for owner approval and task 2.2 remains paused until an amended exact-hash
batch receives a fresh independent Gate 1 review.

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
