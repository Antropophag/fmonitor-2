# Fresh independent Gate 1 rereview — assignment-order original worker safe-log v5

Date: `2026-09-04`  
Reviewer: separately tasked agent `/root/assignment_evidence_gate1`  
Reviewed commit: `2880b55a8f52d0c64d581f6952ae78441d8fa398`  
Scope: closure of the sole prior safe-log cleanup finding and regression review
of the full current v5 executable/OpenSpec package; no tests or production
implementation reviewed  
Verdict: **APPROVED — READY_FOR_OWNER_APPROVAL**

## Exact reviewed artifacts

```text
9e1ab294b9d4ba241bb3980a1be5668272ebc2e51406b90c1ea2aea26237b6f8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d4c7d7344ed621887423cdfe25618c96256f0ffc4615299ab32aaf5724783ac7  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
64c3fa84e471f7f5629ec5f8a8598f468c72df006c1f5f93e7692a8111e53869  openspec/changes/replace-pilot-registration-with-original-upload/design.md
ccabc6790824d224b483391431599b3f237cb92b730c041fca2b0b1648c3fd8f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
587fd57b78b12ffd621a68957e27d5a2cb1b7133ddbc43bed97152ddb8c44665  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
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

## Prior finding disposition

**Resolved.** The section-16 `finally` protocol now explicitly includes every
new safe-log lifecycle step. The parent closes the evidence reader, closes
pipes, terminates and then reaps every child before safe-log removal. It restores
faults, repeat-validates every cleanup target, and removes only its owned
prefix/root/config/password/safe-log artifacts. The final sentence additionally
states that safe-log removal occurs only after both reader close and child
termination/reaping.

This is coherent with section 13's general verifier-owned cleanup rule and with
the delta/design. No reader descriptor or child writer can remain live when the
file is removed, and the repeat-validation plus ownership restriction prevents
cleanup from following a replaced path or deleting an unowned target.

## Full v5 regression review

- Worker config and evidence-reader config carry the same explicit canonical
  `safeLogFile` identity for the corresponding run. The real observer writes
  exclusively there and the evidence reader observes that same path.
- The file remains pre-created, canonical, non-symlink, outside the repository,
  owner/root-owned, regular and exact mode `0600`. It is validated before
  password content or DB access under the approved evidence-config rules;
  neither worker nor factory creates or repairs it.
- Environment, mutable global, default basename, private-root convention and
  callback selection remain forbidden. The new observation path is not a
  command repository or second mutation seam.
- Public fixed evidence failures, cached idempotent reader close, exact config
  and password grammar, closed section-16 JSON shapes and fresh read-only DB
  connection ownership remain unchanged.
- Five dedicated FDs, two READY-before-RELEASE ordering, five-second monotonic
  bound, malformed/EOF/timeout exit `70`, bounded parent reads/waits, pipe close,
  termination and reaping remain exact. No object or connection is serialized.
- HTTP exposure, composition application, opening and the blocked legacy E2E
  amendment remain outside this slice.

## Verdict basis

The sole prior blocker is closed without broadening behavior. The full v5 batch
now gives Gate 2 one constructible, isolated route from real worker safe-log
writes to independent canonical evidence reads, including deterministic resource
and artifact cleanup. Tests need no private SQL, schema inference or hidden path
selector. No remaining ambiguity affecting observable behavior or construction
was found.

This batch is approved by the independent Gate 1 reviewer and is ready for an
append-only owner exact-hash decision. The review itself does not authorize Gate
2; task 2.2 resumes only after that owner approval names the hashes above.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ (concatenate all php fences under one namespace) | php
PHP_DECLARATIONS_OK

$ git diff 2880b55a..HEAD -- <five reviewed artifacts>
PASS (no output; reviewed hashes remain current)

$ git diff --check
PASS (before adding this append-only review; no output)
```

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
