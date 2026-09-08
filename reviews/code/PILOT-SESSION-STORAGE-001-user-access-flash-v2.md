# PILOT-SESSION-STORAGE-001 v10 UserAccess flash + tokens — independent Gate 5 rereview v2

- Date: 2026-09-03T22:54:09+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate5_v2`
- Implementation author: not this reviewer
- Reviewed HEAD: `9d036da2a23f9e1886658c7b85c6f506531692c5`
- Reviewed implementations: `858a877c5148be1b5e4440fc7f9c723fcde0dd67`, `6ada05e7495f2797a1dbc9650c09e2403ca04ec2`
- Review base: `ae61ed32d4ebee1cd4db5181c7525cb00d9352a2`
- Gate 3 authorities: `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v7.md` and `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-tokens-v1.md` — both `APPROVED`
- Gate 4 evidence: `docs/operations/pilot-session-storage-user-access-flash-green.md` and `docs/operations/pilot-session-storage-user-access-tokens-green.md`
- Prior Gate 5: `reviews/code/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md` — `CHANGES_REQUESTED`
- Verdict: **CHANGES_REQUESTED**

## Closed prior finding and specification axis

UA-G5-01 from the prior review is closed. The owner-backed `session()` branch
feeds the same state used by `users()`, `token()` mirrors every generated token
into that state, and `ownerUserAccess()` performs `writeCommit` after every
successful UserAccess GET, not only when a success flash exists. The buffered
200 is returned only after that commit succeeds. A typed publication failure
instead discards the page and returns the section-6 503 response. The flash URL
is rendered and removed from the owner state before the same commit, so it is
one-shot and repeat GET omits it.

Both approved oracles are unchanged after their respective Gate 3 approvals.
The focused tests independently observe the exact rendered token in a freshly
owner-read payload, committed flash removal and repeat absence, and injected
`rename / committed / ordinal=1 / native_false` failures with buffered success
discarded and prior canonical bytes preserved. The empty-prefix relaxation in
`AccessPolicy` matches the configured unprefixed production namespace and
continues to reject all non-alphanumeric/underscore prefixes. The payload codec
diff only fully qualifies global functions and does not change decode behavior.

No specification-conformance defect remains in this narrow UserAccess GET
vertical.

## Blocking architecture finding

### UA-G5-02 — the implementation regresses the canonical architecture ratchet

At exact reviewed HEAD, `make architecture-check` exits 2 because the slice
adds two forbidden `session-owner` fingerprints to
`app/PilotHttp/PilotE2ECoordinator.php` and grows that baselined hotspot from
268 to 273 lines. `docs/architecture/guardrails.md` defines the checker as the
canonical current-state ratchet: existing debt may shrink but new debt is not
allowed, and hotspot files may not grow. The other reported fingerprints under
`rapid-pilot/` are pre-existing debt and are not attributed to this diff.

The new mutable `ownerSessionState` plus orchestration in `session()`,
`token()`, and `ownerUserAccess()` places session ownership and publication
inside an already oversized HTTP coordinator. Move the owner-backed UserAccess
transaction behind a focused named adapter/context so that the coordinator
does not acquire owner fingerprints and is no larger than its baseline. The
unused `ownerSessionId` field must also be removed. This production correction
must preserve the already approved tests and receive a fresh independent Gate
5 review; changing either oracle restarts Gate 2/3.

Judgment-call design observations supporting the hard checker result are
Divergent Change / Shotgun Surgery in the multi-workflow coordinator and
Primitive Obsession/Data Clump in the magic-key mutable session array. They are
not additional blockers beyond UA-G5-02.

## Independent verification at exact HEAD

```text
git rev-parse HEAD
9d036da2a23f9e1886658c7b85c6f506531692c5

php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff

php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess action tokens

for test_file in tests/InstallationProcess/pilot_session_storage*_test.php; do php "$test_file"; done
all 19 files PASS

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/process_user_directory_001_test.php
PASS: PROCESS-USER-DIRECTORY-001 production user directory

git diff --check ae61ed3..9d036da
PASS

make architecture-check
FAIL: two new PilotE2ECoordinator session-owner fingerprints;
      hotspot grew 268 -> 273 lines
```

Reviewed hashes still match the two Gate 3 records:

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
cff3d21dd30aaa7cfd91b374a254e9c4a463f0ae37675e14a94996478f9535b0  tests/Support/pilot_session_storage_user_access_router.php
18306b9015898a179a89961894011565f027e07c207f7f915afdca837313bc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
```

Gate 5 does not pass until UA-G5-02 is corrected and the canonical architecture
check is green.
