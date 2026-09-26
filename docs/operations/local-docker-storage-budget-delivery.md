# Delivery — LOCAL-DOCKER-STORAGE-BUDGET-001

- Assignment: owner request 2026-09-26 after Docker Desktop consumed 264 ГиБ and host free space fell to 19 ГиБ.
- Scope: `bound-local-docker-growth`; №157 and every unrelated OpenSpec change are excluded.
- Authorization: normal mode. Root authors scope, normative contract and executable tests; separate gpt-5.6-sol/low executor authors implementation; independent gpt-5.6-sol/low reviewers decide planner-required Gates 3/5.
- Baseline diagnosis: checkout 324 МБ; images 176.9 ГБ/161 total with 169.8 ГБ reclaimable; BuildKit cache 128.6 ГБ/697 records; volumes 54.06 ГБ/355 total; Docker.raw 264 ГиБ.
- One-time owner-requested image cleanup already completed outside this delivery candidate: images 161→4, Docker.raw 264→109 ГиБ, host free 19→173 ГиБ. No container, volume or BuildKit-cache cleanup was included.
- Full local `make test` / `make verify`: prohibited. Merge/deploy/Docker Desktop settings: not authorized.

## Authorship and gates

- Root planning author: `/root` (proposal, delta spec, design, tasks, normative contract, verification input and tests).
- Implementation author: pending separate executor.
- Gate 3: pending planner decision.
- Gate 5: required, reviewer pending.
- Exact-source CI: pending, single selected run only.

## Gate 2 RED

- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`; required local category: `governance`.
- `python3 tests/Verification/local_docker_storage_budget_001_test.py` — intended RED, exit 1 at `owning Docker storage guard is absent`.
- `python3 tests/Verification/change_verification_001_test.py` — 18 tests GREEN after restoring the checkout's user-write permission bit; the first fixture-only permission failure is retained, not misclassified as RED.
- Evidence: `/Users/antropophag/.local/share/fmonitor-2/bound-local-docker-growth/gate2-red.txt`.
- Disposable inventory: current `compose.test.yaml` uses `tmpfs`; `run-in-profile` uses `docker run --rm`; 303 historical unlabeled volumes have no current exact owner proof. No global/historical volume cleanup enters this candidate; a separately tested exact-project lifecycle seam establishes safe ownership for future disposable callers without touching persistent stands.

## Gate 3 return 1 dispositions

- Reviewer `/root/docker_growth_gate3`: `CHANGES_REQUESTED`, four HIGH findings in `reviews/tests/LOCAL-DOCKER-STORAGE-BUDGET-001.md`.
- F1 fixed: H now forbids volume deletion only in storage guard/global paths and names K/L exact disposable-project teardown as the sole exception.
- F2 fixed: the test now executes isolated public runner fixtures over two source commits and one dependency-byte change, observing tag reuse/invalidation, exact labels, immutable image ID and result provenance.
- F3 fixed: added Buildx capability, lock, negative/invalid measurement, exact override bounds, cleanup failures, idempotency, concurrency, exact argv/order and complete doctor schema cases.
- F4 fixed: added an exact disposable lifecycle public seam contract with success, operation failure, cleanup failure, INT, TERM, hostile ambient project and persistent-project rejection cases.
- Fresh intended RED and fresh Gate 3 package are required before Gate 4.

## Gate 3 return 2 disposition

- Rereviewer retained `CHANGES_REQUESTED`: F1/F3/F4 fixed; F2 partial/open.
- F2 completed: the public-runner fixture now independently mutates every enumerated image-content input (`Dockerfile.focused-checks`, `.dockerignore`, `dependencies.env`, `composer.json`, `composer.lock`, `pyproject.toml`, `uv.lock`) and requires a new identity for each; a source-only commit still requires reuse.
- Failed-command provenance now requires exact current Git SHA, immutable image ID, profile, duration and child exit `37`, with exactly one build and run.

## Gate 3 return 3 disposition

- Third review retained `CHANGES_REQUESTED` for the same F2 cause, triggering full matrix reconsideration rather than another incremental expectation.
- The fixture now copies the complete canonical `tools/delivery` module, including the actual `Dockerfile.focused-checks` and its dedicated `Dockerfile.focused-checks.dockerignore`.
- Each of the seven dependency mutations now starts from a separate clean committed fixture, records its own baseline public run, changes exactly one enumerated input and requires a different tag. The source-only control and failed-child provenance remain separate witnesses.

## Gate 3 approval

- Independent reviewer `/root/docker_growth_gate3` approved the rebuilt matrix; all F1–F4 dispositions are fixed and no new blocker remains.
- Harness Gate 3 verdict recorded `APPROVED` at 2026-09-26T08:46:39Z for candidate `8a95bf1911c2ff9750f56d12ddc6cf6f334781653d5296731b6c0f3ee6337b2d`.
- Gate 4 may proceed without expectation changes. Any test/spec change requires plan refresh and Gate 3 reconsideration.

## Gate 4 implementation

- Executor: `/root/docker_growth_executor`, `gpt-5.6-sol / low`; authored `tools/delivery/docker-storage-guard`, `tools/delivery/run-disposable-compose`, `tools/delivery/buildkitd.focused.toml` and changes to `run-in-profile`, architecture ownership and development setup documentation.
- Root-owned specifications, tests, OpenSpec expectations and Gate 3 review were not changed by executor.
- Executor focused results: storage contract GREEN; planner governance 18/18 GREEN; architecture check GREEN (8 rules); Python/Bash syntax and `git diff --check` GREEN.
- Root repeated the same bounded checks without a local full-suite invocation. Formal harness GREEN records and independent final review follow.

## Gate 5 return 1 dispositions

- Final reviewer `/root/docker_growth_final_review`: `CHANGES_REQUESTED`, two HIGH findings in `reviews/code/LOCAL-DOCKER-STORAGE-BUDGET-001.md`.
- F1 test gap acknowledged: A/B now requires the same immutable image ID across source-only revisions, different executed-source digests, an exact read-only `/workspace` bind mount from the frozen materialized source, and absence of source-specific build args/labels or `COPY . /workspace`.
- Because F1 changes an approved expectation, planner/Gate 3 are refreshed before implementation correction.
- F2 remains open process state: current Gate 3/final/CI must be rebound after the correction; UNKNOWN is not GREEN.

## Gate 5 approval

- Independent reviewer `/root/docker_growth_final_review` approved corrected exact code source `3f225a111bc4a9ec578bd3c036c8d8ecffb7fcfee0d4b3bc77e5061a7bea4382`.
- F1 fixed: dependency-only image, no source-derived build metadata, exact isolated frozen source mounted once read-only at `/workspace`; the A/B oracle independently witnesses mounted marker/digest and rejects broad source COPY/ADD.
- F2 disposition: refreshed Gate 3 approves the unchanged current test expectations; code correctness is covered by exact Gate 5. Exact-source CI remains pending and is still required before overall GREEN/PR-ready.

Full logs and destructive-operation evidence remain outside the checkout under the delivery harness evidence root. UNKNOWN is not GREEN.
