# Local Users503 — trusted scheme binding

Source a5419effa8a2da44edd216da0f5fc99d64bd978c. Preview remains exact old image
fmonitor2-local-preview:1eba93cf966d. Fresh synthetic login proved roles/objects200,
users503. Runtime getenv trusted request scheme=false. Read-only native directory
(1user/8roles) and renderer10864bytes succeeded on actual preview data. No preview
configuration or data changed during diagnosis, aside from ordinary login sessions/audit.

PILOT-LOCAL-TRUSTED-SCHEME-001 v0.2 Gate1 initial/v2/v3 APPROVED; final spec
SHA91eec5258b9d55b31fc4fc3688338b3c26c4dffa886b0569d9be43cc62ee3bc6.

## Intended RED

Command: `php tests/InstallationProcess/pilot_local_trusted_scheme_001_test.php`
with explicit synthetic FMONITOR_TEST_DB_HOST127.0.0.1/PORT23306/ADMIN_USERroot/
ADMIN_PASSWORDfmonitor2_test_root_local, PATH Homebrew/Docker. Docker Compose
config uses only synthetic bootstrap env and `/dev/null` envfile, never deployment.

Final external users-preview-20260907/red-v3.log: both native login+authorized
roles prerequisites healthy; config-derived Users GET503 instead expected200.
`INTENDED_RED: effective local Compose scheme must permit native Users GET`.
Missing scheme+forwarded-http control PASS exact503; both fixture cleanup OK, exit1.
No config/production change before this RED.

Earlier red.log was missing explicit OriginalRuntime test prerequisite; red-v2
was roles404 setup failure because PHP proc_open drops empty environment entries.
Neither is intended RED. SelectionHttpFixture now uses native `env KEY=` argv for
explicit empty values before launching unchanged real rapid-pilot/router.php.
This preserves supplied empty prefix configuration; no function interception or
reconstructed application graph. Existing original HTTP flow regression follows.

Primary scripts/raw responses/privatecookie stay external0600/0700:
/Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907.
Direct diagnostic v1–v3 had standalone bootstrap omissions (DEMO environment,
process prefix, renderer include); onlyv4 valid. Excluded from product failure claims.

Gate3 and GREEN/5 remain. Ops recovery must retain sameimage, volumes, all DB
DDL/rows except one existing sentinel nonce matching active.json, all prior
sessionbytes; post-recreate snapshot BEFORE new smoke-login. No newsource deploy,
newmigration/productionimport or remote mutation. FullVERIFY remains open.

## GREEN and operational recipe

Gate3 APPROVED in reviews/tests/PILOT-LOCAL-TRUSTED-SCHEME-001.md. One-line
compose.yaml literal http -> native compose and missing controls both PASS,
2setup/cleanup, exit0, green.log. Session token and flash/fault regressions PASS;
original HTTP flow fixture regression PASS. Architecture7 and changed PHP lint/
diff PASS. An attempted filename user_access_flash_001 did not exist and is not
verification evidence; actual existing user_access_fault_001 ran PASS.

Further read-only startup inspection found that ordinary IdentityBootstrap also
updates auth/user/role timestamps and role AUTO_INCREMENT, exceeding the strict
v0.2 preservation result. The nonce-only allowance will NOT be widened.
Instead candidate external override mounts a small read-only runtime/start.sh
that repeats existing image transport startup (both socat listeners) and execs
existing rapid-pilot/start.php, omitting all bootstrap/migration/import calls.
This retains the exact image, same volumes, old approved healthcheck mount and
existing ready manifest. All DB/manifest values should therefore remain exact,
a stronger outcome than the allowed optional nonce refresh. No application
source mount or new image is involved.

Candidate Compose config parsed with synthetic credentials; sh -n PASS.
Actual recovery waits for Gate5. Before/after native read-only snapshots contain
all SHOWCREATE and sorted rows, manifest and old session hashes; raw snapshots
remain external0600. Fresh smoke-login occurs after preservation comparison.
Runtime recipe SHA256 a2b0f21a60ce63b865d0cb05a99bb857d486697d2909d8bd230ae23465a5667d.

## Actual preview recovery

Independent Gate5 APPROVED in reviews/code/PILOT-LOCAL-TRUSTED-SCHEME-001.md
for source87c618d4eba3b69b06b36a4d3556eb7d5f55a920 and pinned external recipe.
Only pilot service recreated with --no-deps --no-build --pull never; same image
sha256:8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b,
same named pilot-state volume and existing readonly healthcheck mount. MariaDB
service/volume untouched. External compose.override.yaml now persists runtime-only
entrypoint mount, without bootstrap/migration/import calls.

Before operation: private full-state tar, exact all-table rows/DDL snapshot and
container config +oldoverride backups. After recreation BEFORE smoke:
51tables exact rows/DDL, active manifest exact (no nonce refresh),3308old session
files exact,0new sessions, healthy. No restore or data rewrite was needed.
External preservation-summary.json and state-after-before-smoke.json prove this.

Fresh task-owned authenticated smoke: login200→objects200, roles200, users200
(12456bytes actual page), objects200. All3308preexisting sessions remain exact.
After normal login all DB rows remain exact; the sole DDL metadata difference is
AUTO_INCREMENT on auth_attempts, whose before/after rows both remain empty.
No domain/auth rows were overwritten or added by recovery. smoke-summary.json
and routes-before-recovery.json/routes.json retain the observed503→200 result.

Source reference-reader and original HTTP features are NOT deployed by this
configuration recovery; preview still runs oldimage. No fullVERIFY/launch claim.
