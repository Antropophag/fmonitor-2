# Fresh independent Gate 1 rereview v8 — assignment-order original evidence reader

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_evidence_gate1`  
Reviewed commit: `346b27a77668db6d1dbe95ad9e11bcc5184d7200`  
Scope: closure of the sole v7 password-byte finding and regression review of the
full evidence-reader amendment; no tests or production implementation reviewed  
Verdict: **READY_FOR_OWNER_APPROVAL**

## Exact reviewed artifacts

```text
75466e6c54bcbf119b85d8c9d81872e4b7a8a3692d537362ff188ea4dfbe7cb3  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a3f115412cb104d34c5c9f4991f56f7d4963552b3da03869ce1d8d95bb45f615  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
2329072387211a8c3a2aab2e91c2be9592a23c6c8cbd918157c746895de63207  openspec/changes/replace-pilot-registration-with-original-upload/design.md
aa2547e5e5a67f887014212e20b7e879d371d2c051fc39f56f9899fce0353315  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
4fe0a473ebd95a581cba6a8edaa0484ea5ab472ce3da2b7faa492cf55a500971  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b7fd237d838b5c1fe6cd81ace1878418b8d44d7e23e785b234675f96f9a6aeb2  docs/operations/pilot-assignment-order-original-gate1-rereview-v7.md
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

## v7 finding disposition

**Resolved.** Executable spec, design and delta now define the same byte
algorithm: remove at most one optional terminal LF, then require the remaining
password to contain `1..1024` bytes and every byte to be exact ASCII
`0x20..0x7E`. The contract explicitly rejects empty post-strip content, TAB
(`0x09`), DEL (`0x7f`), non-ASCII bytes, CR, embedded LF and additional newline.
This is locale-independent and supplies unambiguous positive and boundary
fixtures for Gate 2.

The reviewed boundary algorithm was exercised independently with one-byte,
1024-byte and terminal-LF positive values and with empty, LF-only, TAB, DEL,
UTF-8, CR, repeated-LF and 1025-byte negative values. All classifications match
the normative text.

## Prior closure regression

- The public final `AssignmentOrderOriginalEvidenceUnavailable` still fixes
  message, code and previous exception. Construction and every read are total:
  complete declared result or only that redacted exception, never partial JSON.
- First `close()` still attempts all live descriptors/connection exactly once
  and caches its aggregate success/failure. Repeats perform no I/O and preserve
  the cached outcome.
- Host/database/user/prefix grammars and port bound remain exact. Canonical
  non-symlink paths outside the repository, ownership, exact modes, existing
  directory/regular-file kinds and no-create/no-repair policy remain explicit.
  Scalar/path/metadata validation still precedes password-content and database
  access.
- The reader owns one fresh `utf8mb4` MariaDB connection and its private/log
  descriptors. It knows canonical original tables directly, performs read-only
  closed section-16 JSON observations, and accepts no private SQL, schema
  inference, test selector/callback or command repository.
- Evidence cannot feed maintenance enumeration or mutation, so the factory is
  not a second mutation seam. HTTP, composition application, opening and the
  blocked legacy E2E amendment remain outside this slice.

## Coherence and verdict basis

Executable spec, proposal, design, delta and tasks remain coherent about the
factory's verification-only impact, independent resource ownership, lifecycle,
failure totality and task 2.2 dependency. Closed versioned JSON shapes and
canonical table ownership are sufficient for Gate 2 to observe fresh-connection
MariaDB/CAS/fault/maintenance outcomes without test-owned SQL or schema guesses.

No ambiguity affecting executable behavior, constructibility or ownership was
found in the current exact-hash batch. It is ready for owner approval. This
review is not owner approval; Gate 2 may resume only after a new append-only
owner decision names these hashes.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ (concatenate all php fences under one namespace) | php
PHP_DECLARATIONS_OK

$ independent exact-byte boundary classifier
PASSWORD_BOUNDARIES_OK

$ git diff --check
PASS (before adding this append-only review; no output)
```

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
