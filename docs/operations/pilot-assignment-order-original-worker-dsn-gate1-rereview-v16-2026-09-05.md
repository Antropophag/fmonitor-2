# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v16 worker DSN — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_dsn_rereview`
- Reviewed commit: `8414444b7f328d26c5ef9eb9765b03a76dee04fd`
- Previous review: `docs/operations/pilot-assignment-order-original-worker-dsn-gate1-review-v15-2026-09-05.md` at commit `06cd7bf`
- Scope: technical worker DSN amendment and coherence of the complete current
  executable specification/OpenSpec package; no tests or production reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## V15 disposition

The v15 `CHANGES_REQUESTED` finding is resolved. The worker host language now
has two disjoint alternatives:

1. A colon/bracket-free ASCII hostname/IPv4 token with an exact regular
   expression and a `1..255` byte bound. The first and last bytes must be
   alphanumeric, so the declared trailing-dot rejection follows from the
   grammar. The token is passed byte-exact to mysqli.
2. Exactly one balanced bracket pair around an IPv6 value. The inner bytes must
   be lower-case canonical IPv6, pass PHP's named IPv6 filter, and equal the
   `inet_ntop(inet_pton(inner))` round-trip. Only the two outer brackets are
   removed for mysqli.

Consequently `p:localhost`/`P:localhost`, every raw-colon form, zone IDs,
unbalanced/interior/nested brackets, bracketed non-IPv6 values and trailing-dot
tokens cannot enter either alternative. Every accepted host has one exact
mysqli host argument. The previous contradiction between the accepting regex
and the persistent-prefix prohibition no longer exists.

## Gate 1 findings

- The full DSN is exact ASCII with literal segment names and order:
  `host=<host>;port=<port>;database=<database>;charset=utf8mb4`. Whitespace,
  percent encoding, duplicates, extras, socket/query/option forms, reordered
  fields and alternate charset cannot be interpreted as valid variants.
- The non-IPv6 host expression admits exactly length `1` or `2..255`; the IPv6
  alternative is bounded by the canonical IPv6 round-trip. Port is canonical
  decimal `1..65535` without a leading zero, database is `1..64`, user is
  `1..32`, and password content inherits the closed `1..1024` printable-ASCII
  grammar with only one optional terminal LF removed.
- The observable construction is exact: parse and validate configuration,
  obtain the already-declared password bytes, construct
  `(host,databaseUser,passwordBytes,database,port)`, and call
  `set_charset('utf8mb4')`.
- Any invalid DSN, user or worker configuration maps to worker exit `70` and
  fixed redacted stderr before password-file content, database, private
  storage, or safe-log access. Thus an invalid host cannot trigger mysqli's
  special persistent-prefix semantics or leak secret/resource diagnostics.
- The OpenSpec scenario accurately summarizes the normative executable
  contract, including the disjoint host forms and rejection classes.
- The amendment changes no role, capability, workflow, HTTP route/result,
  original lineage, composition/opening behavior, runtime schema ownership or
  blocked legacy E2E behavior. It introduces no environment/global runtime
  selector, object serialization, second mutation seam or runtime DDL.
- The five-FD READY/RELEASE/result protocol, exact shared safe-log identity,
  production adapter composition and bounded parent cleanup remain unchanged
  and coherent with this construction rule.

The worker/CAS Gate 2 author can now derive valid and invalid DSN fixtures and
their exact worker outcomes independently of the future parser implementation.
This approval satisfies the independent Gate 1 rereview requested by task
1.14; it does not approve a future test or production implementation.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 8414444^ 8414444 --check
PASS (no output)

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
4a3c83258015868907059b5bb31eb67ecd40d0253203a6c3b3a1b2f154c8fd7c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
986ff4080e7270fe1168fed784a1b030cbbf4729096f85b8280004a74d09e054  openspec/changes/replace-pilot-registration-with-original-upload/design.md
ffcf5805b3f15240fa115731e7de240d556298e00243f6129da911263e305914  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b30f3259fd153169b13441f651158550cc9ec0a60075cd96ee7af60742507ed0  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
530c67f355f123f2de6541192b2c080c30a9f54280cbab3ec085acacabb01bfc  docs/operations/assignment-order-original-command-matrix-worker-dsn-gap-2026-09-05.md
5b96741c6d8f493e311f7730f4c2def15bdfa3fb1316ccd4743774baf9dd5ed2  docs/operations/pilot-assignment-order-original-worker-dsn-gate1-review-v15-2026-09-05.md
```

This review record omits its own circular hash.
