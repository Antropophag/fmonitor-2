# Session consumer/image evidence accounting

Date: `2026-09-05`. Reviewer: independently tasked evidence-accounting agent
`/root/session_env_fixture_gate5`. Repository HEAD observed at review execution:
`46415f0589c7871f643fc644d265d164541a7dcc`.

This is a read-only accounting determination. It is not a new code approval,
does not edit an OpenSpec checkbox, and does not declare the parent change or
launch complete.

## Determination for task 3.2

OpenSpec task 3.2 says:

> Подключить оба consumers, response buffering, Compose compatibility config
> and task-owned harness; protocol suites host+image GREEN.

Every requirement stated by task 3.2 is now evidenced. Task 3.2 is therefore
substantively complete on the current source and may be checked by the task
owner without treating tasks 2.3, 4.1, or 4.3 as complete.

The phrase `Compose compatibility config` in 3.2 is an implementation/config
requirement. The successful actual Compose stop/start proof is stated
separately and explicitly in tasks 2.3 and 4.1, with final status/Done guarded
by 4.3. Reading stop/start success into 3.2 would duplicate those explicit
requirements and collapse the task boundary. Task 3.2 does not contain an
unreachable Compose-proof gap.

## Requirement mapping

| Task 3.2 clause | Evidence at current source | Determination |
| --- | --- | --- |
| Both consumers connected | `reviews/code/PILOT-SESSION-STORAGE-001-consumers-v2.md` independently approves the LocalAuth and UserAccess migration through one canonical request owner. `reviews/code/PILOT-SESSION-STORAGE-001-user-access-local-profile-v3.md` independently approves the later owner-backed UserAccess local-profile correction. Current `rapid-pilot/LocalAuth.php`, `ProductionPilotHttpEntrypointFactory.php`, and `PilotE2ECoordinator.php` retain those paths. | Evidenced |
| Response buffering and fail-closed publication | The consumers v2 Gate 5 records that storage completion precedes cookie/redirect publication, publication faults discard buffered success, and flash/action-token state commits through the same owner. The UserAccess local-profile v3 Gate 5 confirms no change to response publication and cites the focused flash/token oracles. | Evidenced |
| Compose compatibility config | Current `compose.yaml` fixes `FMONITOR_SESSION_STATE_ROOT=/home/fmonitor/.local/state/fmonitor2`, `FMONITOR_SESSION_INSTANCE=pilot`, and mounts `pilot-state` at that same root. `LazyPilotSessionStorage` retains strict absent-key fallback and present-invalid fail-closed handling. The clean Compose diagnostic built the exact current-source image and reached only unrelated bootstrap prerequisites, rather than a session config mismatch. | Evidenced as configuration; runtime restart proof remains elsewhere |
| Task-owned harness | The approved consumer Gate 5 covers task-owned session roots and focused raw-HTTP harnesses. The exact-image run mounted tests read-only, used an isolated internal DB and task-owned tmpfs, bounded every child, published no HTTP listener to the host, and removed its test container. The clean Compose diagnostic separately records exact project ownership and removal of only its own containers, volumes, network, and worktree. | Evidenced |
| Host protocol GREEN | Consumers v2 records all then-current 23 session-storage tests passing on host plus the codec/request-owner and relevant HTTP/architecture checks. The later explicit-empty-root fixture correction passed the focused raw HTTP protocol test on host and received independent Gate 5 approval at current HEAD. | Evidenced |
| Image protocol GREEN | The exact production image ran the current 24 `pilot_session_storage_*_001_test.php` tests plus codec/request-owner in one bounded run: 25/25 exit 0 and `SESSION_IMAGE_FAILURE_COUNT=0`. This includes both consumer, buffering, configuration, inspector, lifecycle, fault, and corrected explicit-empty protocol coverage. | Evidenced |

## Exact evidence and hashes

```text
46415f0589c7871f643fc644d265d164541a7dcc  reviewed repository HEAD
sha256:b98963779a006f167986f082f7c7ff78f28e9fc4e30d66a6e11f9d8ecb7613d8  exact image ID
84f2383250eb07eb4727502ed83c7453593a0e394632e2210c2dfab587d7ec8e  compose.yaml
d8d825a9f9583be7bbdabd4913ee12c35cd85d48069afe0547ce9fa3d17ac857  reviews/code/PILOT-SESSION-STORAGE-001-consumers-v2.md
ce69f0f5c3b371836cfbedb321269e614e99c678aeb28d21949eb8a1ecffedc8  reviews/code/PILOT-SESSION-STORAGE-001-user-access-local-profile-v3.md
0d1def203b9e65b2565db8b0284cd3f38b2fd526b3e44648a7d2905aa9a60f49  reviews/code/PILOT-SESSION-EMPTY-ENV-FIXTURE-001-v1.md
66ee5da2bb572d725d7f9b7a838ca11cc89e2d30a483ed355584be37694efcda  docs/operations/session-clean-compose-prerequisite-proof-2026-09-05.md
5e1a93e11918de8355c4cfb2f4990fb85caf2e950c00325cde920ce5c0a6bff5  docs/operations/session-empty-env-fixture-green-2026-09-05.md
13026b57c5fc6fc2e2329d4f3fb3f535acf80b48d0cc998a258829093e0e394b  private session-final-inputs.json
d0fd77d84b85f74a8c2f5f8a67c166878a47295b4058afb8809f9d4616384bdc  private session-image-final.log
82709d917c428bee89e31f53f092b81809b201f2723edbb4739ed683464f9d09  private evidence-manifest.json
```

`session-final-inputs.json` records `imageSourceMatches: true`, pins all 25 test
hashes, and identifies the input commit before the GREEN evidence-only commit.
The protocol test hash is
`fef3f2c0b5308d9b445e2e3cc023321606f72cca9b19a1d1b36a4dbc6d40c174`.
The image log contains an exit-zero result for every pinned test and terminates
with `SESSION_IMAGE_FAILURE_COUNT=0`. The explicit-empty correction's Gate 5
also independently reran that protocol test on the host and the same exact
network-disabled image.

## Requirements that remain open

Task 2.3 still requires the approved real HTTP/inspector package as a complete
Gate 2/3 unit and successful actual Compose stop/start preservation. Task 4.1
still requires the broader exact login/user-access/CSP/RBAC host/image ladder,
actual Compose stop/start cookie proof, lint, architecture, fresh lifecycle,
full verification, and cleanup proof. Task 4.3 still requires runbook/status and
Done only after every gate and safe persistent restart.

Those Compose requirements remain blocked by the recorded clean-startup
prerequisites: canonical migrations reach terminal version 12, but the
configured legacy object table and generation sentinel are absent, the active
generation manifest is not published, bootstrap returns
`{"ok":false,"reason":"MIGRATION_FAILED"}`, and the pilot never becomes
healthy enough for login or cookie stop/start verification. The image-only
25/25 run and task 3.2 accounting do not replace that proof.
