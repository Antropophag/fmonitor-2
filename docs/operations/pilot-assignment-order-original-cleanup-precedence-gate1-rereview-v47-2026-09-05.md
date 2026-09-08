# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v47 cleanup precedence — independent Gate 1 rereview

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/assignment_cleanup_log_rereview3`

Reviewed commit: `220ff53ef1e924f95cccd4ec0ab672de2370b02e`

Scope: OpenSpec tasks `1.29` and `1.30`, including the v46 findings about a
reachable post-commit close branch and a constructible canonical abort-failure
run; no test or production implementation reviewed or changed

Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
b907d7da3f822acb6fdfbc306da21ed18f53572a87ee593303874284f144b9fa  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
6e03adaff4887b9642e8204788a934e95b44566de2425df28578659505ee9858  openspec/changes/replace-pilot-registration-with-original-upload/design.md
fbc39cf38755ac652ab7b51ddf2e5b7f9deffe149198a9bb0fe531eeeae281e9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
7d7e26e41f29fda0257fefce763ece6b65b69eea7532de73fe6718b3dec3b7fa  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
46d8e7ba33673902b0e530afaa6e7df899a28485421256a5edf9f0a4b996c6f2  docs/operations/pilot-assignment-order-original-cleanup-precedence-gate1-rereview-v46-2026-09-05.md
```

Canonical context/process hashes consulted:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Finding

### G1-47-1 — the canonical abort run still lacks exact independent inventory bytes

The v47 amendment now gives the abort selector a reachable base: request
`00000000-0000-4000-8000-000000000301`, the fixed 327-byte positive PDF, an
injected `INVALID_PDF` inspector, `stage-0001`, abort failure followed by
successful stage/stream closes, `REJECTED/INVALID_PDF`, and exact correlation
`d87e745407ea`. It also states the intended cardinalities and replay behavior.

But the promised audit/private inventory remains prose rather than an exact
oracle. The contract says only “one terminal rejected request+audit” and “one
abandoned stage of 327 bytes”. The closed evidence shapes require additional
observable values: all request fields, `auditId`, `actorIdentity`, mode,
case/order, status/reason and `attemptedAt`, plus stage `createdAtUtc`. The
storage/lifecycle transcript for this canonical failure is likewise not stated
as exact ordered items, even though the amendment is specifically meant to make
abort/close ordering independently testable. Gate 2 would have to obtain those
expected values from the future implementation or invent them, contrary to the
Gate 1 independently determined expected-value requirement.

Add literal canonical JSON for this isolated run's requests, safe audits,
private blobs and safe logs, plus the exact ordered storage/lifecycle events
actually observable through the already specified public observers. State the
same literal inventories after the same-request retry (or explicitly identify
the byte-identical ones). Fix all otherwise unspecified values, including audit
identity and stage creation time. The throwing safe-log-observer variant must
name the exact difference—zero log items—and preserve all other literals.

Mirror the canonical abort-base and exact-evidence requirement in the OpenSpec
delta/design rather than leaving their generic v46 wording to stand for the new
v47 construction.

## Checks that pass

- The exact PHP stream contract now makes bounded acquisition/validation one
  owner subroutine and permits an accepted candidate to escape it only after
  stage close then stream close succeed. Repository commit therefore has no
  reachable post-commit stage/stream-close branch.
- Non-accepted cleanup is unambiguous: required abort, stage close when present,
  stream close, and only then terminal rejection/conflict audit; all applicable
  attempts occur once and later cleanup failures do not replace the selected
  outcome.
- Accepted-candidate stage-close and stream-close failures map respectively to
  retryable `STORAGE_FAILURE` and `STREAM_FAILURE`, forbid commit, retain the
  finalized content privately and attempt one lease release with phase
  `rolled_back`.
- Plain cleanup fault selectors and the injected throwing safe-log observer are
  separated from production configuration. The injected observer performs no
  retry and cannot change the selected result.
- `SHA-256("00000000-0000-4000-8000-000000000301")` begins with the specified
  correlation `d87e745407ea`; event names and sole `phase` safe field remain
  exact and do not expose payload, path, identity, SQL, exception or diagnostics.
- The delta, design and tasks do not contradict the executable spec, tasks
  `1.29`/`1.30` correctly remain unchecked, and strict OpenSpec validation
  succeeds. They still need the canonical v47 evidence detail above before
  approval.

## Verification evidence

```text
$ git rev-parse HEAD
220ff53ef1e924f95cccd4ec0ab672de2370b02e

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check c976806bafb2355954d4b3b0c1fa4fed5c80c4e5..220ff53ef1e924f95cccd4ec0ab672de2370b02e
PASS (no output)

$ printf %s 00000000-0000-4000-8000-000000000301 | shasum -a 256
d87e745407ead6dc30a245ccc3ab8293d5e77dbd823dc47076a32569416415fc  -
```

Tasks `1.29` and `1.30` are not approved at this commit. Resolve G1-47-1 in the
executable specification and matching OpenSpec artifacts, then obtain a fresh
independent Gate 1 rereview before authoring cleanup-fault RED.

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
