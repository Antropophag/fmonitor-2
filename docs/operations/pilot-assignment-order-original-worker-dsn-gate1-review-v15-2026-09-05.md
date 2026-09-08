# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v15 worker DSN — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_worker_dsn_gate1`
- Reviewed commit: `eccc17a9eb018723052c0f74633e3e1acc2c6607`
- Triggering gap record: commit `c954714c2722bcf63f28b6df3d783a2cbb548dca`
- Scope: technical worker DSN amendment and coherence of the complete current
  executable specification/OpenSpec package; no tests or production reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding — host grammar contradicts its exclusions

V15 closes most of the reported construction gap: segment names and order are
literal, whitespace/encoding/duplicates/extras are excluded, port/database/user
bounds are exact, password bytes inherit a closed grammar, the mysqli tuple and
`set_charset('utf8mb4')` are explicit, and invalid config fails with exit `70`
before password content, DB, storage or log access.

The accepted host language is nevertheless not closed or coherent with those
rules. The normative regex `[A-Za-z0-9.:[\]_-]{1,255}` accepts, among others:

```text
p:localhost
[localhost]
abc]
[abc
a[b]c
::::
```

`p:localhost` is then an “every other host” passed byte-exact to mysqli, while
the next sentence expressly forbids persistent prefixes. Thus the same input is
both accepted by the only host grammar/mapping and forbidden by the contract.

The bracket rule is also underdefined. It says “Bracketed IPv6” loses brackets,
but the regex does not require balanced outer brackets or validate that their
contents are an IPv6 literal. It therefore does not determine whether
`[localhost]`, malformed bracket placement, invalid IPv6 text, or raw colon
forms are rejected, stripped, or passed byte-exact. Those choices change the
observable worker exit and the exact mysqli host argument. A Gate 2 author
cannot select them independently from a future parser implementation.

This is security-relevant as well as syntactic: mysqli assigns semantics to a
`p:` host prefix, so merely passing regex-approved bytes is not equivalent to
excluding the expressly forbidden connection mode.

## Required amendment

Define disjoint canonical host alternatives and their mapping. For example,
the contract can separately define a bounded hostname/IPv4 token that excludes
colon and all brackets, and a balanced `[<IPv6-literal>]` form whose inner value
must pass one named exact IPv6 validation rule before the outer brackets are
removed. Explicitly reject `p:`/`P:` semantics, unbalanced or interior brackets,
non-IPv6 bracket contents, and any raw-colon form not deliberately accepted.
Equivalent exact grammar is acceptable, but every regex-approved host must be
compatible with the exclusions and have one deterministic mysqli argument.

After amendment, a fresh independent Gate 1 review is required. Task 1.14 must
remain unchecked; this verdict does not authorize worker/CAS RED.

## Coherence and scope checks

- The fixed segment order and `charset=utf8mb4` literal otherwise make
  reordered, extra, socket/query/option and alternate-charset forms rejectable.
- Port `1..65535` with canonical decimal/no leading zero, database `1..64`, user
  `1..32`, and inherited password `1..1024` printable ASCII bytes are bounded.
- Invalid config is ordered before secret-file content and all DB/private/log
  resource access, and maps to fixed redacted stderr plus exit `70`.
- Worker configuration remains serializable and reconstructs the declared real
  repository/storage/application composition. It adds no environment/global
  selector, runtime DDL, second mutation seam or IPC object serialization.
- The five-FD READY/RELEASE/result protocol and bounded cleanup remain coherent.
- No role, capability, workflow, HTTP contract, original semantics,
  composition/opening behavior or blocked legacy E2E behavior changes here.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (before this append-only review; no output)
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
497565e3a4ef06129c1541ae3befef8502d75047c15e0f3e83dece17c8747e41  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
986ff4080e7270fe1168fed784a1b030cbbf4729096f85b8280004a74d09e054  openspec/changes/replace-pilot-registration-with-original-upload/design.md
ffcf5805b3f15240fa115731e7de240d556298e00243f6129da911263e305914  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
4531ce1827d6e2d63c9a68f761f9a8739b95396d3f1bfabe36c5d5d2114729c3  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
530c67f355f123f2de6541192b2c080c30a9f54280cbab3ec085acacabb01bfc  docs/operations/assignment-order-original-command-matrix-worker-dsn-gap-2026-09-05.md
```

This review record omits its own circular hash.
