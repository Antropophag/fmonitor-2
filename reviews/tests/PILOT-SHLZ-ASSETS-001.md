# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_assets_v2_test_rereview`
- Test author: separately tasked Gate 2 agent; reviewed commit `1cd7b78`
- Reviewed commit: `1cd7b78`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED, exit `255`; an overlapped mutation response was `200` with 6 MiB body instead of required redacted `503`.
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. The production source oracle imposes false broad bans

Section 3 forbids named mechanisms only when they provide cross-request graph coordination. `psaNoCoordinationSource()` instead rejects every occurrence of `guardian`, every listed APCu/SysV/opcache call, and a broad `static ... manifest|shlz` pattern anywhere under `app`, `bin`, or `public`, regardless of purpose. A legitimate unrelated APCu use, a guardian word in copy/comment, or a stateless `public static function shlz...` therefore fails although it does not retain graph identity, bytes, locks, or state. Conversely, a generic cache helper whose writer contains no nearby `manifest|shlz` token can coordinate the graph without matching the regex. The oracle is simultaneously broader than the normative prohibition and incomplete for indirection.

Replace the lexical ban with an oracle scoped to asset-request effects/state. If a source-membership guard is retained as supplemental evidence, restrict matches to actual coordination calls/state owned by the asset implementation and avoid banning unrelated production capabilities or identifiers.

### 2. The filesystem residue oracle is not sensitive to implementation-owned residue

`psaResidue($token)` only reports paths containing the test's random token. The application is never given that token, so an implementation can leave `shlz-manifest.cache`, `asset.lock`, or another fixed/generic cache path in the repository or scanned runtime directories and the before/after arrays remain equal. This does not satisfy section 7's prohibition or the prior requested fingerprint of task-owned runtime/repository locations.

Capture and compare the relevant directory entries/content metadata before and after requests independently of filename, while excluding the test fixture paths explicitly. The comparison must detect a newly created or changed generic cache/lock/temp/sentinel.

### 3. The timestamp assertion does not prove the claimed overlap

The worker/counter is a substantial improvement, and the counter advancing by at least 20 during the client lifetime makes overlap likely. However, `firstAt <= completed && lastAt >= sent` only says that some post-snapshot mutations lie somewhere inside or around the client interval; it does not show activity bracketing that interval or the server's capture. Both inequalities can pass when all recorded mutations occur in a short period after capture but before response completion. The test then requires `503` even though the specification permits a whole old/new response when replacement did not intersect capture.

At minimum, retain timestamped mutations already observed before request transmission and mutations observed after response completion, and assert those timestamps bracket the public request interval. Use the continuous counter as the no-pause witness and keep the exact `503` assertion. A production test hook or prohibited coordination remains unnecessary.

## Coverage retained from v0.1

The unchanged coverage still traces exact graph routes/bytes, import grammar, GET/HEAD parity, security/error priority, deduplication/cycles, identity aliases, member/depth/size limits, malformed and escaping imports, invalid UTF-8, collisions, symlink boundaries, owner/mode cases, and HTML stylesheet order. The sequential replacement fixture correctly distinguishes v0.2 from the former process-lifetime immutable graph. The focused run is honestly RED for missing v0.2 atomic revalidation rather than setup: it reaches the overlap case and receives `200` instead of `503`.

## Required changes

1. Scope the source oracle to forbidden asset graph coordination without banning unrelated production code.
2. Fingerprint residue locations independently of the random fixture token and detect generic created/changed residue.
3. Make timestamp evidence bracket the request interval before requiring the overlapped response to be `503`.
4. Re-run and capture the focused RED after correction, then obtain a fresh independent Gate 3 review.

Gate 4 remains closed.

## Verification evidence

- `git diff 331b8ac^ 331b8ac -- specs/PILOT-SHLZ-ASSETS-001.md` — reviewed normative v0.2 delta.
- `git diff 1cd7b78^ 1cd7b78 -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — reviewed corrective Gate 2 delta.
- `git diff --check 331b8ac^..1cd7b78 -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — intended RED, exit `255`: `expected 503, actual 200, body bytes 6291456`.
