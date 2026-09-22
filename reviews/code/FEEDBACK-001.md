# Gate 5 final review: FEEDBACK-001

## 1. Disposition

- Reviewer: independent `gpt-5.6-sol` agent `/root/final_review`; authored no specification, test, or production artifact in the reviewed snapshot.
- Reviewed source: base `41573bf75a067eafc1422a24d2761b45805274cc` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T184941Z-61d5e7f71e/snapshot/source.patch` (SHA-256 `745f1c57f0cbcf6d9831c1c3c5f98c340825a83b01dfe95719a8d4539571779e`), candidate source `6315a93a6f56369308d53d5fab859ca4281982989b34bcdf4700a4c90875699f`.
- Verdict: **CHANGES_REQUESTED**.
- UI disposition: **FIX BEFORE SHIP**. The actual captures show that the two multiline entry controls are visually clipped to single-line pills, so A6's ordinary multiline form and usable review history are not met despite the browser geometry assertion passing.

## 2. Capture validity and UI finish

- Inspected all four required captures from the same actual harness run: `feedback-desktop.png`, `feedback-mobile.png`, `feedback-review-desktop.png`, and `feedback-review-mobile.png` under `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-f4171c3961ab/`.
- The captures cover the specified 1280x900 and 360x900 form/review surfaces. Navigation is discoverable at desktop and represented by visible, reachable icons on mobile; headings, labels, actions, safe-context explanation, return action, feedback facts, and recorded review history are legible without horizontal overflow or collision.
- **HIGH — multiline controls are visually unusable.** In all four captures, the description/result textarea is exposed through a roughly one-line-high rounded pill. In `feedback-review-mobile.png`, the empty result input is an unlabelled-looking pale pill separated from its label, and neither form presents the expected five/six-line writing area. The DOM minimum-height check observes `.fm2-feedback-textarea`, but the incumbent shlz wrapper clips its painted content; `app/YiiRuntime/Assets/pilot.css:1756-1759` sizes the textarea while leaving `.shlz-field__control` under the component's fixed/clipping geometry. Correct the feedback-local wrapper geometry so the control itself expands with the textarea (for both ordinary and admin forms), then capture and inspect all four surfaces again. Strengthen the browser oracle to assert the visible field/control box and usable multiline content area, not only the nested textarea's reported box.

## 3. Contract and architecture coverage

- A1-A5 and A7 are coherently implemented through `FeedbackApplication` and `MariaDbFeedback`: active/admin authorization is rechecked at the application seam, context is allowlisted and normalized, output is encoded, roots/results are append-only, request identity replay/collision handling is actor-scoped, concurrent unique-key races recover receipts, v25 is additive, and the current recovery inventory includes both tables and AUTO_INCREMENT frontiers while retaining prior profiles.
- HTTP handling covers authenticated form access, admin-only listing/results, CSRF/method/status behavior, retained retry payloads, and sanitized storage failures. Navigation extends the existing shared Yii shell and leaves checklist/process owners #40 and OTIZ #66 untouched. The two direct frontier corrections and two suite registrations are justified schema consumers; no delivery workflow or CI implementation was added.
- Exact-source focused evidence in the prepared package is GREEN for `php tests/Yii2/yii2_feedback_001_test.php` (`1789411663063777000-b4d6139607d74546a39054a663d8f055`) and `php tests/Yii2/yii2_feedback_browser_001_test.php` (`1789411673882484000-f34c0100ea974f5889a401a285aa6327`). Exact-source `make architecture-check` is GREEN in record `1789411688856722000-60054f65a4244a91adaf8c10474ba6ce`.
- **HIGH — the supported demo bootstrap remains pinned to schema version 24.** Exact-source `php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php` is a `REGRESSION_FAILURE` in record `1789411831286291000-0ea2988063b647b5af7b57884b674626`: the test expects ready marker 25 but receives 24. `bin/fmonitor2-pilot-demo.php:126` writes `schemaVersion => 24`, and line 140 rejects any ready marker other than 24, even though `PilotDemoDatabase` now provisions the v25 catalogue. Update both current-version literals to 25 so newly provisioned demo stands advertise and accept the schema they actually contain. The adjacent production migration runner and readiness consumers are GREEN in records `1789411830114850000-3c170a3dc7b543758f4f00e59405b6e0` and `1789411818219592000-1bc9e6c2be934b839fbe7199faa82154`.

## 4. Maintainability finding

- **MEDIUM — the new production seam is materially harder to review and maintain because most methods and complete views are compressed onto giant physical lines.** Examples include `app/YiiRuntime/FeedbackApplication.php:8-22`, `app/InstallationProcess/MariaDbFeedback.php:9-28`, `app/YiiRuntime/Controllers/FeedbackController.php:10-34`, `app/InstallationProcess/FeedbackSchemaMigration.php:8-30`, `app/RuntimeRestore/RuntimeRecoverySchemaV25.php:5-8`, and both feedback views. This hides transaction/error branches and authorization decisions in horizontal text, produces weak line-level diagnostics/blame, and makes safe follow-up changes unnecessarily risky. Reformat the new PHP classes and views into the repository's readable multiline PHP style without changing behavior; architecture acceptance alone does not make this delivery-quality code.

## 5. Limits and required correction

- The prepared package contains reconstructible snapshot/evidence and no missing test obligations. I did not rerun the mechanical design detector, did not run the forbidden local full `make test`/`make verify`, and did not claim live deployment or server enforcement. Exact-source GitHub CI remains pending after review, so CI/deployment remain `UNKNOWN`.
- This is the complete findings inventory: two HIGH defects (visual usability and the stale demo schema marker) and one MEDIUM maintainability defect. Repair all three, preserve the approved behavior/tests, regenerate exact-source captures/evidence, and return the corrected candidate for independent final rereview before Gate 5 approval.

## Gate 5 rereview — corrected candidate 2026-09-14

### 1. Disposition

- Reviewed source: base `41573bf75a067eafc1422a24d2761b45805274cc` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185902Z-0dfc23c1c1/snapshot/source.patch` (SHA-256 `57271ddfadae5af1f7f40b5afc78be7f357437a0bc9dc313625fd5f3bc343058`), candidate source `2a9723dd8c67791ffd8482d186fc4807fdb229da8d70a0d6c43a51eda8ab1fcd`; correction delta SHA-256 `30fa9fbb1733a915900d0c7de8d03281e2a93809c98ad39270f94d7622ffc237`.
- Verdict: **APPROVED**. UI disposition: **SHIP**. No new findings.

### 2. Capture validity and UI finish

- Inspected all four new captures from `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-1605e64c0ab6/`: desktop/mobile submission and desktop/mobile review. They are current actual-harness captures and show entered content painted inside full multiline fields on both surfaces and widths.
- The prior clipping defect is resolved by feedback-local textarea wrapper geometry at `app/YiiRuntime/Assets/pilot.css:1759`. Form labels remain associated, controls and navigation remain visible and usable, content does not overflow horizontally, return/review history remain legible, and the mobile focused skip link is an expected keyboard-accessible shell state rather than an obstruction to the task.

### 3. Contract and evidence

- The strengthened wrapper-level clipping oracle received independent Gate 3 approval and is GREEN in the exact-source browser record `1789412228545448000-36b317d00d54425296253c5a56e2bfc6`; the owner acceptance suite is GREEN in `1789412215771737000-cd9fad2c8b7f4bdebbeda532105730b2`. Architecture is GREEN in `1789412267448520000-d0a96612e4fc4b8fbdaf59fe1db69b67`.
- The supported demo bootstrap now writes and accepts ready marker 25 at `bin/fmonitor2-pilot-demo.php:126,140`; its exact-source focused test is GREEN in `1789412340800218000-b4171c0d029943ffaadc4ccb3f4417a9`. The previously cited production migration runner and readiness consumers remain GREEN.
- The corrected delta preserves the reviewed A1-A7 authorization, safe context/output, append-only facts, replay/collision/concurrency, v25 recovery, HTTP, and adjacent-navigation behavior.

### 4. Prior findings disposition

1. **Resolved — visual usability.** Both nested textareas and painted shlz field wrappers now expose a genuine multiline writing area on submission and admin review, as shown in every required capture and enforced by the approved browser oracle.
2. **Resolved — demo schema frontier.** Both stale v24 current-marker literals are 25 and the direct consumer passes from the exact candidate.
3. **Resolved — maintainability.** The new production classes are formatted as readable multiline PHP; controller helper responsibilities are moved to the small internal `FeedbackControllerSupport` trait, and the views are split across physical lines sufficiently to expose markup/control branches and produce useful diagnostics and blame. This remains within the existing Yii composition boundary and adds no framework.

### 5. Limits

- Review evidence is exact-source and reconstructible. `git diff --check` is clean. I did not rerun the mechanical UI detector or the forbidden local full suite. GitHub exact-source CI, deployment, and live server enforcement remain outside this review and therefore `UNKNOWN` until their own evidence exists.

### Provenance clarification

- The `41573bf75a067eafc1422a24d2761b45805274cc` value above is the branch/origin-main comparison base. It is not the reconstruction base of either retained harness snapshot.
- Per each authoritative `snapshot/manifest.json`, both snapshots reconstruct by applying their `source.patch` to commit `907737a21e7748236ca610d84abb761fbe648322`: package `20260914T184941Z-61d5e7f71e` uses patch SHA-256 `745f1c57f0cbcf6d9831c1c3c5f98c340825a83b01dfe95719a8d4539571779e`, and package `20260914T185902Z-0dfc23c1c1` uses patch SHA-256 `57271ddfadae5af1f7f40b5afc78be7f357437a0bc9dc313625fd5f3bc343058`. This corrects provenance metadata only and does not alter either review verdict.

## Post-CI six-test delta review — 2026-09-14

- Scope: only the six test files changed after failed exact-source CI run `34884408428`; production and UI bytes are unchanged, so no screenshot or detector rerun is required. Independent Gate 3 approved this exact test delta with no findings.
- Reviewed source: reconstruction base `cf0ba05b9f70251ef87bbd6da7ba733643942802` plus package `20260914T192108Z-0a387d2c17` snapshot patch SHA-256 `061e9a6ff6b782818ab2879e3bd240e0e1967f9aa7320c7c1b8bd0f6d614b648`; candidate source `a71000454186c028fcadc4527d4119fb2ef9b34cf9d048605f709187d9af99e0`; delta patch SHA-256 `450e9316f36e45c920ae122f7988aab8b4aba4d9318f43407b222e22c2f9884e`.
- Verdict: **APPROVED**. No findings. The workforce catalogue adds the two canonical v25 feedback tables; both readiness fixtures update only their expected current schema version; the asset contract pins the hash of the already-reviewed CSS; and the verification inventory adds the registered feedback browser suite. These are exact literal-consumer corrections and retain their behavioral assertions.
- The activation proxy test now compares the three access records as an exact method/path multiset because independent nginx workers may flush completed requests in any order. It still requires exactly three records, exact route/method pairs without arguments, status/upstream outcome, valid timings, unique generated request IDs, suppression of every private request field and token, activation error suppression, and ordinary upstream diagnostics. The correction removes an invalid ordering assumption without weakening privacy or observability coverage.
- The complete first-CI failure inventory was retained and inspected before correction. All six focused consumers are GREEN, including activation proxy record `1789413634604893000-ce2e5593f3b946b7b13ff5daf95c1ef5`; the package binds the owner and browser suites GREEN to the candidate. `git diff --check` is clean. A second exact-source CI run remains required; its result is `UNKNOWN` at this review point.

## Issue #172 Gate 5 final review — 2026-09-22

### Disposition

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue172_final_review`; authored no specification, test, Gate 3 artifact, or production artifact in this candidate.
- Reviewed commit: `be8e925153d5b5ec35579f93508fd75d00280e62` against base `0504d2589835f2583dc9afdbc47e4694e2573365`; exact candidate source `d2c7f6019a208d8884157e351e60908260bfa10897193a160ece876eae7523e5`; executable source `98234a6535d69c91f0f95a98796d28035b43401eb4c9be9912996c08cbae216c`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T163135Z-c89defc15a/package.json`, SHA-256 `6d7df9950254675e85e6922f6b10f4bd5a2197e2c8b666c8912dbc6da29a3376`; required-context SHA-256 `e434887991b52aa9ef824d101f338d804189c484616ba2276a384369645bebc5`; verification-plan SHA-256 `c8c3c682b8fbc74ce7880c28912d6d6f33ef14ea7a3713a75e1c033530de7033`.
- Verdict: **CHANGES_REQUESTED**.

### Findings

1. **HIGH — build identity is read through a pathname TOCTOU gap and the declared absolute-path trust boundary is not enforced.** `app/YiiRuntime/FeedbackApplication.php:125-143` validates pathname metadata with `file_exists()`/`is_link()`/`lstat()`, then separately calls `file_get_contents($path)`. A replacement or link swap after line 136 can therefore make the accepted bytes come from a different inode than the regular, single-link, read-only file that was checked. The reader also accepts a relative configured path, although `openspec/changes/fix-feedback-context-build-identity/design.md` requires the configured **absolute** immutable build file. This fails the design's explicit TOCTOU mitigation and can persist a value that was not proved to be the immutable server-owned identity. The approved tests cover stable missing/mutable/symlink/hardlink/invalid fixtures but do not force a pathname replacement during the read and do not reject relative paths, so all three exact-source suites can remain GREEN under this regression. **Correction:** reject non-absolute paths; open one bounded file handle, compare pre-open `lstat` with handle `fstat` (device/inode/type/link-count/mode/size), read at most the allowed 65 bytes from that handle, then `fstat` again and require unchanged identity/metadata before accepting the exact digest; any mismatch/error must return `unknown`. Add deterministic public-seam coverage for relative paths and a controlled swap/race (or an injected narrow reader that proves the same-file checks), then obtain the required independent test-delta approval and refreshed exact-source evidence.

No other findings. The closed route allowlist and return links reject the reviewed sensitive/unknown paths; snapshot/order/feedback identifiers do not acquire object semantics; full 64-character values, `unknown`, client spoof rejection, persisted admin labels, actor-scoped replay/fingerprint/history, and append-only result behavior otherwise conform to FEEDBACK-001. The diff does not add source hashing, process, network, Git, Docker, migration, readiness, OTIZ/process ownership, external delivery, or other excluded scope. Maintainability is proportionate apart from the blocking reader correctness issue.

### Evidence and limits

- Exact-source GREEN records reviewed: owner/application/HTTP/concurrency `1790094637313385000-7c591125a6d14bc3bf75921aabe5b2b4`; connected browser `1790094648219846000-26d0902a78094eaa9f74359315866bd7`; unchanged strict readiness `1790094666041312000-528eb1a7dc564ca1a35e9a34b83266b1`. Each binds source `d2c7f6019a208d8884157e351e60908260bfa10897193a160ece876eae7523e5` and exits 0.
- `git diff --check` and PHP syntax checks for the three changed production/config/view files are clean. The forbidden local full suite was not run.
- GitHub exact-source CI, PR publication, merge, deployment and live enforcement remain `UNKNOWN`; they are not approval or GREEN. The HIGH finding blocks Gate 5 despite the focused GREEN evidence.

## Issue #172 Gate 5 rereview — corrected current-main candidate 2026-09-22

### Disposition

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue172_final_review`; same returning reviewer for its own prior finding, still author of no specification, test, Gate 3 artifact, or production implementation.
- Reviewed current-main base `bced877aec8a8802e97037749ca4251d3098df1a`, commit `7b9cda428d8ef098ee1310bd31a22273b88bf75e`, exact candidate source `3179ff5d7098ebce56b23117f131367f0095cda8b700c669259a771dea16524f`, executable source `625f24b7938638638992c1b5daf39d02acbed14d54414e0f0abc41a75f24864f`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T170439Z-7cf7d21850/package.json`, SHA-256 `badd9c646209059ac865996536adcbca01bef040bedc3b6245dab57dbe5abeaf`; required-context SHA-256 `07c79d5092b1a60987b12efc8a131b7e1c35de0e9b663806e0c56a269bafac58`; verification-plan SHA-256 `93d00817b34fac89bf764ff5ff8a1b353c236992880196197652412e27d7e78d`.
- Verdict: **APPROVED**. Complete findings list: **none**.

### Prior finding disposition

1. **Resolved — pathname TOCTOU and absolute-path trust boundary.** `app/YiiRuntime/FeedbackApplication.php:125-176` rejects empty, NUL-containing and relative paths; opens one handle; performs `fstat → bounded read(66) → fstat → final lstat`; requires identical mode, link count, size, device, inode, mtime and ctime across the two handle observations and final pathname; then accepts only a single-link, read-only regular file of exactly 65 bytes containing 64 lowercase hexadecimal characters plus newline. All failures return `unknown`, and the handle is closed from `finally`. Accepted bytes no longer come from a second pathname read.
2. The correction's narrow injected filesystem dependency is confined to deterministic same-handle/path-binding verification; the production default exposes only open/fstat/bounded-read/lstat/close and introduces no Runtime/release framework, source walk, process, network, Git, Docker or external-request fallback.
3. Independent Gate 3 approved the real existing relative-file case, stable same-handle acceptance, pathname rebind rejection and independent post-read handle-metadata mutation rejection with the exact call/close sequence. Its later pagination-only mechanic keeps the original two literal immutable history entries and actor attribution while locating the older root solely through public `listing` cursors.

### Full candidate review

- FEEDBACK-001 A2/A8 route normalization remains a closed allowlist: query/fragment and sensitive/download/export/mutating/unknown paths cannot become return links; object ID is derived only from explicit object routes, while snapshot, order and feedback IDs retain distinct semantics.
- A4/A5/A9 replay remains actor/command scoped and fingerprinted only by normalized user content. Build identity is written only on first insert, excluded from the fingerprint, never accepted from client POST, and persisted historical context/build/results are not rewritten. The administrator view explicitly escapes and labels the persisted source path and full build.
- The full 64-character immutable identity, fail-soft `unknown`, authorization, CSRF/method handling, concurrency, append-only results, pagination and browser return paths retain sensitive public-seam coverage. Readiness remains the separate strict fail-closed owner and is unchanged.
- Rebase merges #235–#237 do not add feedback production scope. Policy ownership maps the changed application owner to both feedback verifiers. No migration, OTIZ/checklist/process behavior, status/readiness semantics, external delivery, deployment or stand mutation enters this slice. The resulting reader and route table are bounded and maintainable.

### Evidence and limits

- Package exact-source GREEN records: owner/application/HTTP/concurrency `1790096624355553000-999951fd52e34dfd9de09e3c0659987d`; connected browser `1790096635355051000-51babeeb30f34ea3b279daeb004c6c2e`; unchanged strict readiness `1790096653292252000-2ed040cf9b674721a2baf86037a362b6`. Each exits 0 and binds exact source `3179ff5d7098ebce56b23117f131367f0095cda8b700c669259a771dea16524f`.
- The remaining planner-listed bounded obligations were independently reconfirmed GREEN during this rereview: `python3 tests/Deployment/pilot_jobs_compose_001_test.py`, `python3 tests/Verification/change_verification_001_test.py`, and `python3 tests/Verification/architecture_guard_001_test.py`. `git diff --check` and changed PHP syntax remain clean. The forbidden local full suite was not run.
- This approval is Gate 5 for the reviewed exact candidate. GitHub exact-source CI, PR publication, merge, deployment and live enforcement remain `UNKNOWN` until their own evidence exists; this verdict does not make those states GREEN.

## Issue #172 post-CI Gate 5 delta review — 2026-09-22

### Disposition

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue172_final_review`; author of no production, test, specification, Gate 3 or correction artifact.
- Reviewed source: committed HEAD `effc89cacdadd1459b9c663c3c0ad074f9ffb788` plus the prepared post-CI delta; exact candidate source `fffc8dac670078c5b9e89fdb3840906a03843ddba3e95cfb956df22f4dff21c0`; executable source `47b7901279e56c9827f28ac9459010a24a3e81aad5a08e286e113ffa23e83a07`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T173826Z-2b8d7a2abd/package.json`, SHA-256 `6c78bb425db57984aa46932d30a77cb3220f01189a074c8674230485e3e0f6c0`; required-context SHA-256 `07c79d5092b1a60987b12efc8a131b7e1c35de0e9b663806e0c56a269bafac58`; verification-plan SHA-256 `ade191101b0857148ab57456420c4c01a044ca244473f3c56d0693129e3a3149`.
- Verdict: **APPROVED**. Complete findings list: **none**.

### Delta review

- The only behavior-affecting delta after the preceding APPROVED Gate 5 is in `tests/Verification/change_verification_semantic_closure_153_test.py:182-191`. After copying the shipped policy into its disposable repository, the fixture now appends its synthetic exact `FeedbackApplication` owner only when no shipped capability owner already explicitly contains that path.
- This is a setup correction, not an admission bypass. Without the condition, the newly shipped `feedback-application` owner and the unconditional fixture owner manufacture `PROTECTED_CAPABILITY_OWNER_AMBIGUOUS` before the intended semantic-closure assertion executes. With the condition, current repositories exercise the real shipped owner; historical/fixture repositories lacking it still receive the same synthetic owner and execute the same protected-path scenario.
- Planner ambiguity enforcement remains covered and unchanged: the fixture does not remove or coalesce actual owners, alter policy matching, inject verifier success, change semantic escalation, or weaken missing-owner/multiple-owner failures. `change_verification_placement_181_test.py` imports this fixture, so the same manufactured duplicate causally accounts for both CI consumers.
- The full current diff confirms no production, route, security, replay/history, build-reader, readiness, schema or runtime policy behavior changed after the approved candidate. Adding the two failed consumers to the verification input makes the refreshed plan explicitly schedule the affected regression surface.

### CI inventory and evidence

- Failed exact-source CI run `35758901037` remains a historical failure. The complete primary inventory contains exactly two `REGRESSION_FAILURE` consumers: `tests/Verification/change_verification_placement_181_test.py` and `tests/Verification/change_verification_semantic_closure_153_test.py`, both with the same fixture-created `PROTECTED_CAPABILITY_OWNER_AMBIGUOUS` cause. Aggregate `verify` failed consequentially; all other categories were GREEN. This review does not relabel that run.
- Independent Gate 3 approved the fixture delta with no findings. During this review the corrected consumers are GREEN: semantic closure 11/11; placement 22/22 with `VERIFY_OK`.
- Refreshed package evidence is exact-source GREEN for owner/application/HTTP/concurrency `1790098647849504000-febd0b42850c471aa7ce214cda8d67e1`, connected browser `1790098659872182000-d3f7295504c04cad92709aec7858335f`, and unchanged strict readiness `1790098680041924000-ab1d5902029d4a8eaed83848ae78043e`; all bind source `fffc8dac670078c5b9e89fdb3840906a03843ddba3e95cfb956df22f4dff21c0` and exit 0. `git diff --check` is clean.
- This superseding Gate 5 approval covers the reviewed exact delta. Because source changed after failed run `35758901037`, a new matching exact-source CI run is still required; CI/merge/deployment/live enforcement remain non-GREEN until their own evidence exists. The forbidden local full suite was not run.
