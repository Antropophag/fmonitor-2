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
