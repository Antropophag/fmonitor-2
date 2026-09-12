# Gate 5 — YII2-PRODUCTION-WEB-CUTOVER-001

Status: `CHANGES_REQUESTED`.

- Independent reviewer: `/root/gate5_web_cutover` (`gpt-5.6-sol / low`); author of none of the reviewed specification, tests, or implementation.
- Exact reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T233103Z-a6e2e07046/package.json`.
- Reconstructible snapshot: base `4de37e17a5fc690349c695cd5c14c2020493a0c4`, patch SHA-256 `6290ac08e083a7dfda84718c9a0417507761ff07bddf66f9fc6725deae3d99f9`.
- Candidate source: `616ce9a6993214f015cbcf3f38cf6f5f5eec1f147b66a343cbafafac59e57708`; executable source: `4efdfed370e33d28d792aa6b21e22b960aed981f3345692a4b1cd3836f50c232`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T233103Z-a6e2e07046/verification-plan.json`, SHA-256 `fb2f445c6b4bbaa39f416e242789dc63b270557d23f2ba585068c1f3d0baa857`.
- Gate 3 approval: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T223423Z-b183e1216a/package.json`, plus the post-implementation oracle approvals recorded append-only in `reviews/tests/YII2-PRODUCTION-WEB-CUTOVER-001.md`.

## Evidence reviewed

The package contains nine mapped `GREEN` records. The same executable source also has the three remaining generated focused records outside the package's acceptance-only evidence list:

- `1789255607745065000-387ebf2c427845f5bd8604e9b9cd53db` — production process readiness;
- `1789255614243717000-bbfd779222ae48188312a0effce82ee8` — production browser and authenticated restart;
- `1789255676566202000-7283ef1a46e84d23a0b906a5f834e990` — settlement compatibility;
- `1789255684250612000-c3161e23c5ed49c28da10c764c8dc162` — runtime storage;
- `1789255687080926000-1493e29935334b5ead2785f37596888d` — complete public cutover matrix;
- `1789255694675804000-7a6c5018787b470b835196a1bbd1ecb4` — production dependency architecture frontier;
- `1789255695903839000-8c6dba28d2b248e1943832e92c2037ad` — authenticated production frontier;
- `1789255699066624000-15246dc11b6c4087899e4423510afb84` — architecture guard;
- `1789255717260690000-29da91cb4a494191be9ebbb508a56697` — verification inventory;
- `1789255749297010000-a42138234c6145a88eb823aaa494ff7e` — visual contract;
- `1789255750186291000-e4a89304bb554406ba94a80aba672907` — jobs Compose adjacency;
- `1789255802729955000-cbbbb605a2e54f3e9fb0e2ac0c17603f` — change-verification governance.

All twelve records are `GREEN` at source `0e922298830e6ab450d6129c386653cff79ca7ee862a96c799338f3cb0431af9` and the exact executable source above. The later candidate-source change consists of delivery/review records and task bookkeeping; executable bytes are unchanged. Every record reports `end_fixture: UNKNOWN`, which is not treated as fixture approval. Full exact-source CI, PR, and deployment are `UNKNOWN` and are not treated as GREEN or approval.

The reviewed runtime has one Yii application composition, and the public and authenticated include-frontier evidence finds no production `rapid-pilot/` reachability. The focused evidence also exercises exact route/asset responses, hostile and missing Host rejection, configuration/DB/storage/session failures, durable-close failure, active-session reuse after same-port restart, and whole-database/private-path invariance. Those properties are satisfactory for the reviewed executable source, subject to the contract regressions below.

## Findings

1. **High — object-queue authorization is broadened beyond the accepted contract.** `app/InstallationProcess/MariaDbYiiObjectQueue.php:22` now authorizes either `objects.read` or `access.administer`. `specs/YII2-OBJECT-QUEUE-001.md:30-32` requires exact `objects.read` and explicitly says neighboring access permissions do not grant queue access. The cutover specification also says roles and permissions are unchanged. An access administrator without `objects.read` therefore gains a protected process view. The authenticated-frontier expectation does not authorize changing this domain permission. Restore the exact accepted queue authorization and use a fixture identity with both permissions when the frontier needs one identity to visit admin and object routes.

2. **High — the inspection-schedule method contract is weakened.** `app/YiiRuntime/Controllers/ObjectQueueController.php:23-26` changes `schedule` from POST-only to `GET`, `HEAD`, and `POST`, while `config/yii/web.php:124` registers all three methods. The accepted executable contract in `tests/Yii2/yii2_object_queue_001_test.php:23` requires authenticated GET and HEAD to return `405` without facts; the normative queue seam defines this route as POST. With the new behavior, an authenticated GET/HEAD enters the state-changing action and evaluates request/domain data instead of failing at the verb boundary. Restore POST-only ownership and correct the cutover guest inventory expectation if needed; a migration test cannot redefine the inherited route contract.

3. **Medium — unrelated documentary-closure behavior is bundled into the runtime cutover.** `app/YiiRuntime/Views/completion.php:27-31` changes progress accessibility markup, pre-fills two domain dates with today, and changes the declaration button from “Добавить декларацию” to “Завершить работы”. These user-visible choices are absent from the cutover specification, whose scope preserves existing URL, data, document, and workflow behavior. Remove them from this slice or bind them to their own reviewed product contract and tests.

4. **Medium — the new public seam is unnecessarily hard to maintain.** `public/runtime.php:7-12,23-33,36-38,49-70` duplicates bootstrap and the complete safe-response header policy across branches, while `app/YiiRuntime/Controllers/PilotAssetController.php:13-39` compresses the entire asset policy into dense statements. The cutover acceptance test is likewise compressed into a few multi-kilobyte lines. This creates duplicated-policy and divergent-change risks at a security-sensitive boundary. Extract one safe rejection/error response policy, keep the pre-dependency Host ordering explicit, and format the asset/test matrices as named cases without changing behavior.

## Gate decision

`CHANGES_REQUESTED`. Findings 1 and 2 are observable authorization and method-boundary regressions against already accepted contracts, so this exact candidate cannot proceed to publication. Remove the unrelated UI delta, correct the regressions without weakening the Gate 3 oracles, rerun the affected focused contracts and the generated exact-executable-source matrix, prepare a fresh immutable reviewer package, and obtain a new independent Gate 5 decision.

## Gate 5 correction review — 2026-09-13

Status: `APPROVED`. This correction decision supersedes the blocking disposition above for the exact corrected candidate; the earlier findings remain append-only history.

- Independent correction reviewer: `/root/gate5_web_cutover_correction`; author of none of the reviewed specification, tests, fixture correction, or production implementation.
- Exact correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T234351Z-3e72e412d1/package.json`.
- Reconstructible snapshot: base `4de37e17a5fc690349c695cd5c14c2020493a0c4`, patch SHA-256 `7df77bfa3fb6d23495f82042ad96d37831e27dd7e070d151c793e972fee1de72`.
- Candidate source: `9a19475cd5a796dcc9d16b6278463c504fd9b153c5616fdf845f626597c3a6e5`; executable source: `a3701968eca6b1f161a2d1f991cf4ad9daba2691aaa5c496e2de58a81951ea6b`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T234351Z-3e72e412d1/verification-plan.json`, SHA-256 `4ef158c839bc562b07bb39954df57cd437d19d0832f489ad88cfa35594d23c0c`.
- Previous `CHANGES_REQUESTED` snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T233103Z-a6e2e07046/snapshot`; the bounded correction delta is the package's `delta.patch`.
- Gate 3 fixture correction approval is recorded append-only in `reviews/tests/YII2-PRODUCTION-WEB-CUTOVER-001.md` and binds package `20260912T233841Z-168048a34d`.

### Correction disposition

Both blocking regressions are fixed. `app/InstallationProcess/MariaDbYiiObjectQueue.php:22` again grants queue access only for the exact case-sensitive `objects.read` permission. The authenticated-frontier fixture now explicitly grants that permission to its existing administrator role, so its expected object-route `200` no longer broadens production authorization; the same identity still receives `403` at the independent OTIZ frontier.

`app/YiiRuntime/Controllers/ObjectQueueController.php:23-35` orders access before the verb filter and keeps `schedule` POST-only. Consequently guest requests retain the accepted `303 /pilot/login` return path, while authenticated `GET` and `HEAD` are rejected at the method boundary with `405`, `Allow: POST`, and no domain facts. The accepted focused object-queue oracle remains unchanged.

The conditional guest-CSRF guard in `config/yii/web.php:15-21` is limited to the production front controller by `FMONITOR_PRODUCTION_WEB_CUTOVER`; it protects production guest state-changing requests before the access redirect while leaving non-production Yii compositions and GET/HEAD routing unchanged. The full source review found one Yii application composition in `public/runtime.php`, no production include or dispatch path into `rapid-pilot/`, safe Host-before-dependency rejection, generic configuration/DB/storage/session failure responses, cleared partial redirects and cookies, durable session-close failure handling, and authenticated cookie reuse after a same-port process restart.

The two earlier medium observations are non-blocking for this cutover decision. The completion-view edits are pre-existing owner-scoped #76 WIP carried in the captured worktree and do not alter the corrected cutover boundary; their product acceptance remains owned by their documentary-closure slice. The compact front-controller/asset style remains a maintainability follow-up, with behavior constrained by the exact response, security-header, asset-byte and dependency-frontier oracles. Visual parity is GREEN against the corrected exact executable source.

### Exact GREEN evidence

All generated focused obligations are `GREEN` at candidate source `9a19475cd5a796dcc9d16b6278463c504fd9b153c5616fdf845f626597c3a6e5` and executable source `a3701968eca6b1f161a2d1f991cf4ad9daba2691aaa5c496e2de58a81951ea6b`:

- `1789256396132520000-43029b3be08c444b9358a0fa51bedfcd` — production process readiness;
- `1789256402712499000-8a12c5f35c4a4e548ae473cbfdfcfbc1` — production browser, authenticated flow and restart;
- `1789256459792487000-fd81a10a2f24435cb8fecdf79b011206` — settlement compatibility;
- `1789256467856936000-3fe1f98cdd8441d99af8070b83137ff7` — runtime storage;
- `1789256470395643000-83ad78014b824bc0bb4abba02c5c3d3b` — complete public cutover matrix;
- `1789256475927776000-b81fc8a602df4013afcac103a2268a5f` — production dependency architecture frontier;
- `1789256476805831000-4f91ed218b1a45288d399a03945e8065` — corrected authenticated production frontier;
- `1789256479549406000-1efe311e0f454a9ca166705c1486b4a2` — architecture guard;
- `1789256497233925000-684085202ffa4c75b7189f8504e43f1f` — verification inventory;
- `1789256528729292000-53571b3996ce4e43860acf4b662b2d4d` — visual contract;
- `1789256529650386000-12d51a6a7065417b90fcd17a015827b5` — jobs Compose adjacency;
- `1789256581305331000-d0dc8325f8b745099e22df81456cbbed` — change-verification governance.

Every record reports `end_fixture: UNKNOWN`; this is not treated as fixture approval. Full exact-source CI, PR and deployment remain `UNKNOWN` and are not treated as GREEN or publication approval.

### Gate decision

`APPROVED` for the exact corrected Gate 5 candidate. No blocking finding remains: exact object authorization, guest/authenticated schedule routing, production-only CSRF behavior, Yii-only runtime ownership, session failure/restart behavior, and visual parity conform to the accepted contracts and exact GREEN evidence. Publication still requires final committed-byte equivalence and the repository's authoritative full CI.
