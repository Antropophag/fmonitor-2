# Code review: YII2-DOCUMENTARY-CLOSURE-001

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification/tests nor the implementation.
- Specification and tests: `specs/YII2-DOCUMENTARY-CLOSURE-001.md` and the Gate 3 candidate approved in `reviews/tests/YII2-DOCUMENTARY-CLOSURE-001.md`, including the approved test-helper appendix. The later observer-only screenshot delta is independently approved in `/tmp/doc-screenshot-review.md` and is included byte-for-byte in this final source.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T191921Z-d4695d5d18/snapshot`, patch SHA-256 `a137e60e128d26706b9b5189c58db09f576a05679fab489575b3893e6035b24d`, source digest `d3dbc47ca1e00b31dce62e45101cbffabb459b36345b29e62f6d99f857be144c`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match `manifest.json`, `git diff --check` is clean, and `harness.py state` reports the exact GREEN source digest.
- Verification plan: package `20260910T191921Z-d4695d5d18`, SHA-256 `08683d893895bdce63cd101ee1dc3900ca482f6eebdca6ade05fba0059384fa3`, all A1-A7 mappings present and no mapped test missing.
- Exact-source GREEN: HTTP `1789067889733334000-7e1ce36947514fcf81b4c323207665d1`, held-lock concurrency `1789067889733330000-3dc5a55407c24cc5aa19158f7b46a121`, browser `1789067889735279000-1ad98f8d1297407dae51e89f71768229`, and `make architecture-check` `1789067890912187000-50d4ca6e1703462f9ffc8cb761b0d1cf`; all report source `d3dbc47c...`, exit 0 and no source drift.
- Adjacent unchanged-scope GREEN: object card `1789067463260478000-69bbf6d2257c498f9996afa0cef9a23b`, object queue `1789067463260059000-b8fea16cb7d14af1bd3c568a037a14b4`, and inspection journey `1789067463260382000-1ce077eacf1545bb9489b4a1d5ead476`. These precede only the reviewed test-helper/observer corrections; the final mapped tests and architecture check cover the final exact source.
- UI evidence: final document-top captures `documentary-desktop.png` and `documentary-mobile.png` under `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-fcf97fd676c5/`; automated detector result `[]` for all three inspected views.
- Verdict: `APPROVED`.

## Findings

None.

## Review disposition

The final implementation conforms to A1-A7. `CompletionController` bounds and parses the form before invoking the owner, derives the actor from the session, checks the exact active grant through the existing `MariaDbYiiLocalIdentityStore::grants()` public reader, and delegates all completion DML, authorization recheck, case locking, concurrency ordering and transactions to the existing `MariaDbInstallationCompletion`. It does not copy authorization SQL or completion writes into Yii. Canonical route IDs, CSRF, content type/length, malformed/duplicate members, exact capabilities and domain failures map to the reviewed HTTP results; unexpected database/schema failures are sanitized `503` with retry advice and no runtime DDL or repair.

`MariaDbYiiCompletionQuery` is read-only, uses the configured Yii connection and validated prefix, reconstructs effective details and ordered append-only lineage, resolves current display names while retaining stable actor IDs, and fails closed for duplicate roots, broken versions or unavailable schema. Root facts and prior corrections remain immutable. Queue/card projections derive 85/100 and completion status from checklist operations and documentary facts without changing `process_state`; correction remains admitted by the owner below the current 85 threshold, as specified.

The view escapes all document, reason and identity text; renders the stable history selectors and complete attribution; gates each form by its exact grant and stage; and preserves checklist/card/queue return paths. Moving `inert` from the whole read-only checklist layout to its mutation sections restores the authorized navigation link while keeping mutation controls inert. The desktop and 390px captures retain the existing shlz-ui/craft-floor card hierarchy, typography and controls, with no horizontal overflow or material layout defect. The completion section is a narrow extension rather than a redesign.

Gate 5 is approved for this exact source. The authoritative exact-source full CI, final checkpoint byte comparison (allowing the review/documentation files to be enumerated separately), PR publication and any later deployment authorization remain delivery obligations; this review does not claim those later states.

## Gate 5 A4 nonworking-form delta review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification/tests nor the implementation.
- Scope: production correction for the independently approved A4 nonworking-form test delta; no schema, mutation-owner, history or transport change.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T192855Z-c579c06440/snapshot`, patch SHA-256 `1ba8de8b2dd85ce56437bc1a06ac73619f1901066442709db7cb1563b3816cf7`, source digest `77cfb37c41a748ca01e61e01dc46fc70189b8e3b011e8de9fc65c37e58e31bee`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact GREEN source digest.
- Verification plan: package `20260910T192855Z-c579c06440`, SHA-256 `327f36eab7c8f2f37b0e5a5c0ac14094cffe9e6d0628e16481a0e17fe6f070dc`, with the card projection added to the planned boundary and no mapped test missing.
- Exact-source GREEN: HTTP `1789068471692036000-9e3a240b113d46588fee95da0e7fbfa9`, held-lock concurrency `1789068471709745000-1139b4831af84fa7ac81b6706cd3b061`, browser `1789068471694895000-9f29ea5cd992438ca80ba58200c40d31`, and `make architecture-check` `1789068472982793000-7a5a66fc93c94b5394647a2121ca5e36`; all report source `77cfb37c...`, exit 0 and no drift. The HTTP run includes the formerly RED post-root nonworking case and the existing working/completed form matrix.
- Prior approval provenance: the complete Gate 5 review above remains the baseline for source `d3dbc47c...`; this section reviews the full production delta from that approved snapshot together with the intervening approved A4 test.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

`MariaDbYiiObjectCardProjection` now exposes `completionWritable` from the persisted case state after validating the allowed opened/unopened state combinations. The value is assigned on every successful card projection path and is true only for `working`. `ObjectCardController` conjuncts that value with each existing exact grant, so all four mutation forms disappear for `needs_assignment_change` while completion facts and history remain readable. Working cards retain their previous stage/grant-specific forms, and completed working cards retain correction forms. The public POST still delegates to the unchanged owner, which independently enforces working state and authorization.

This A4 production delta is approved for source `77cfb37c...`. Final exact-source architecture/full-CI evidence, checkpoint byte comparison, PR publication and deployment authorization remain separate delivery obligations.

## Gate 5 A7 verification-roster delta review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification/tests nor the implementation.
- Reviewed delta: `tests/Verification/verification_ci_001_test.py` between retained snapshots `20260910T194712Z-3ac7eaf02d` and `20260910T194801Z-2e5dcfb59e`; the only executable change is one documentary-browser expected E2E member.
- Corrected exact source: base commit `7aea9393fac2970c08d08b4b7a8f6e40840db366`, snapshot patch SHA-256 `6ec70896901cb7455e3bd623bd03ee052c1829f18386aa9fc3bdd6df58ae9c8a`, source digest `dc9ac4f7174279fb066b193ce4df5d59998332e5f2f2a7ef0dadd3b8ff270db0` and plan SHA-256 `24b9213d5b9613459c41b3c52ff5981b291a8d33b9ade410a7252cfbe063b669`.
- Verification: intended stale-roster RED `1789069483334595000-af1564d1ed9c43e1a4cc2e493090c8d2`; corrected 16/16 GREEN `1789069633889502000-afff3f2d89e148ef8363f4cd2c54b92a`. Restored snapshots reproduce their recorded source digests and pass `git diff --check`.
- Production identity: production code remains the already reviewed commit `7aea9393fac2970c08d08b4b7a8f6e40840db366`; this delta changes only the verification contract literal and delivery evidence.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The correction aligns the exact-composition oracle with the already approved and registered documentary browser test. It neither suppresses a suite member nor changes its category, command, order, shard behavior or evidence requirements. The test remains sensitive to omissions, duplicates and unexpected E2E members. The first PR CI failure is therefore resolved at its stale expected roster while preserving A7's requirement that the browser journey run in the authoritative E2E category.

This verification-only delta is approved. The replacement full CI must run on the exact correction commit before the PR is reported green.
