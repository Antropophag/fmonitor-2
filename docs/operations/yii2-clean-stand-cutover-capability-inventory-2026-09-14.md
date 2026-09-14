# Yii2 clean stand capability inventory — 2026-09-14

Owner decision: issue #76 closes through clean Yii2 stand provisioning/acceptance, not legacy-state restore. Read-only inventory was taken from fetched `origin/main` `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`, not from OpenSpec task checkmarks. Quality Graph Publisher runs `34782665605`, `34783485266` and `34784178537` for that exact commit are successful.

| Capability | Landed evidence | Current code evidence | Clean-cutover role |
|---|---|---|---|
| Yii2 foundation | PR #77 merge `8b810490…`, Quality Graph SUCCESS | `bin/yii`, `config/yii/*`, Composer bootstrap | required |
| Authentication | PR #79 merge `d9811cdd…`, Quality Graph SUCCESS | Yii login/logout/roles routes and tests | required |
| User/access | PR #85 merge `907cb0a3…`, Quality Graph SUCCESS | IdentityAccess owner, user administration, activation tests | required |
| Initial owner provisioning | present and tested on exact main | `bin/fmonitor2-provision-initial-admin.php`, `tests/Runtime/initial_owner_provisioning_001_test.php` | required; idempotency re-proved integrated |
| Jobs console/worker/scheduler | PR #96 merge `a567818d…`; constituent checks SUCCESS, historical aggregate Quality Graph FAILURE | `jobs/worker`, `jobs/scheduler`, `jobs/health`, Compose healthchecks and DB tests present; exact main Quality Graph later SUCCESS | required; fresh integrated proof mandatory |
| Canonical migrations | PR #98 merge `adf30398…`, Quality Graph SUCCESS | `schema-migrate/run`, migration service and canonical ledger tests | required |
| OTIZ workflow | PR #103 merge `b6fe81c3…`, Quality Graph SUCCESS | Yii HTTP workflow and runtime-boundary tests | representative golden flow |
| Production web cutover | PR #108 merge `1ce54b04…`, Quality Graph SUCCESS | `public/runtime.php`, live/ready, Yii router closure | required |
| Production image cleanup | PR #109 merge `687f23e7…`, Quality Graph SUCCESS | runtime recipe excludes `rapid-pilot`; image test present | required runtime closure |
| Target/Compose | PR #113 merge `c43d7fc8…` and PR #120 merge `925ec85d…`, Quality Graph SUCCESS | exact target validator and `deploy/runtime/compose.yaml` topology | required |
| Stand backup | PR #119 merge `c5041e21…`; GitHub exposes no PR check rollup | Yii `stand-backup/create|verify` present on main | optional offline; not acceptance gate |
| Stand restore | PR #124 merge `e5a420e0…`, Quality Graph SUCCESS | Yii restore owner/ledger/lease present | optional offline; not invoked |

Normal production Compose, `public/runtime.php`, Yii console and jobs do not call `RuntimeRecovery`. The explicit `bin/fmonitor2-runtime-recovery.php` caller, v22–v24 compatibility and historical recovery tests remain offline compatibility and a separate retirement backlog.

No integrated exact-candidate clean provisioning/acceptance reporter exists on main. Gate 2 therefore targets the external root-owned acceptance harness and real disposable proof; no production application change is assumed.
