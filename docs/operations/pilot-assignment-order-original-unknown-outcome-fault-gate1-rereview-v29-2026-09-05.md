# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v29 unknown-outcome fault — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_unknown_fault_rereview`
- Reviewed commit: `e141df60171f844a0bbc3c094d0b90863b69733c`
- Prior review: `c4ce3d1a6b48100f2d075fe73285edb3f25ca1c1`
- Scope: correction of the v28 unknown-outcome worker-language ambiguity and
  coherence of the full current executable specification/OpenSpec package; no
  tests or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Review result

The sole blocking v28 finding is closed. The obsolete
`COMMIT_AFTER_UNKNOWN = 'commit_after_unknown'` member has been removed from
the canonical `AssignmentOrderOriginalFaultPoint` enum. It does not occur in
active executable-spec or OpenSpec truth. Consequently worker config can no
longer select that ambiguous fourth unknown-outcome behavior through its
canonical fault-enum grammar.

The remaining unknown-outcome language is closed and coherent across the
executable specification, OpenSpec design and delta scenario:

- `COMMIT_UNKNOWN_FOUND` / `commit_unknown_found` durably commits, reports
  repository `OUTCOME_UNKNOWN`, then permits exactly one fresh terminal-request
  lookup on a new connection to return `FOUND`; the current invocation resolves
  to the stored accepted result.
- `COMMIT_UNKNOWN_NOT_FOUND` / `commit_unknown_not_found` rolls back before
  reporting `OUTCOME_UNKNOWN`, then permits exactly one fresh lookup to prove
  `NOT_FOUND`; the current invocation returns retryable
  `FAILED/PERSISTENCE_FAILURE` and a same-request retry may make one new commit
  attempt.
- `COMMIT_UNKNOWN_UNAVAILABLE` / `commit_unknown_unavailable` durably commits,
  reports `OUTCOME_UNKNOWN`, and makes exactly its one fresh lookup
  `UNAVAILABLE`; the current invocation returns retryable
  `FAILED/PERSISTENCE_OUTCOME_UNKNOWN`, while a following normal same-request
  worker reads the durable terminal row and returns `REPLAYED` without another
  effect.

Each composite script is consumed once and is explicitly unable to affect the
earlier request/fingerprint reads or later retries. The typed content lease is
held until the corresponding fresh lookup resolves `FOUND`, `NOT_FOUND` or
`UNAVAILABLE`, then release is attempted exactly once with the matching safe-log
phase. This agrees with the section-10 failure matrix and does not introduce a
blind recommit after an unknown outcome.

Production remains bound to inert faults and cannot select these scripts by
environment, request, CLI or config. Only verification worker config accepts
the canonical fault enum. The amendment changes no actor, capability, workflow,
HTTP contract, schema, original lineage, composition/opening behavior, runtime
DDL or blocked legacy E2E behavior.

Gate 1 for the v29 unknown-outcome amendment is therefore **APPROVED**. This
review makes task 1.20 eligible for integrator bookkeeping and owner exact-hash
approval; it does not itself authorize Gate 2 or approve any test/production
artifact.

## Verification

```text
$ git rev-parse e141df60171f844a0bbc3c094d0b90863b69733c
e141df60171f844a0bbc3c094d0b90863b69733c

$ git diff c4ce3d1 e141df6 --check
PASS (no output)

$ rg -n "COMMIT_AFTER_UNKNOWN|commit_after_unknown" \
    specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md \
    openspec/changes/replace-pilot-registration-with-original-upload
PASS (no matches)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
f52b6fa78913b2654247b722f65de2184378886430038d1df7c5197ed2d7d9f9  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0f57d5a958ae5cc6cd1484325810d733a1ab1a162d2f257b3adcc87769990a3a  openspec/changes/replace-pilot-registration-with-original-upload/.openspec.yaml
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
3b26a248dbc545bde5544303b7e83419c1148d4ecbbe0305aadee8633fea5c32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2625105fb790c72f929a6ae813e67aa0e030c3ea74bf16babc044ba23a15e4b9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
72aa32a0163e3e1299a86eed4e9334aaf2a2e77612f5eb3f515e3410073286ea  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
14a02ef3decfebe4ff8627d4c0805eb307f89ffc34200b99e7283837c9abe714  docs/operations/pilot-assignment-order-original-unknown-outcome-fault-gate1-review-v28-2026-09-05.md
```

This review record omits its own circular hash.
