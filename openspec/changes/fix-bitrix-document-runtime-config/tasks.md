## 1. Gate 1 — contract and verification plan

- [x] 1.1 Root creates `specs/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md` with public staging seam, acceptance matrix, security/rejection cases, compatibility boundaries and synthetic examples; verify every OpenSpec scenario maps to an observable normative assertion.
- [x] 1.2 Root creates `verification-input.json`, runs `python3 tools/delivery/harness.py prepare` for the exact base/contracts/boundaries, and verifies every mandatory Quality Graph obligation has an executable owner before Gate 2.

## 2. Gate 2/3 — executable RED contract

- [x] 2.1 Root strengthens focused tests for complete `.env` quoting → atomic staged config → real worker bootstrap origin/user/token/cleanup, private input modes and marker-secret redaction; verify intended RED against the rejected implementation.
- [x] 2.2 Root adds deployment/Compose regression assertions that document integration has no `/dev/null`/host token mount, duplicate token environment or decorative metadata contract and that disabled workforce-only startup remains valid; verify intended RED.
- [x] 2.3 Root adds focused synthetic delivery coverage proving the configured root reaches the existing read-only API seam without credential output; verify existing publication/error semantics remain unchanged.
- [ ] 2.4 An independent `gpt-5.6-sol/low` reviewer evaluates the complete corrected spec/tests/RED evidence for planner-required Gate 3, records `reviews/tests/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md`, and Gate 4 restarts only after `APPROVED`.

## 3. Gate 4 — minimal implementation

- [ ] 3.1 A separate `gpt-5.6-sol/low` executor preserves one atomic staged `bitrix-config.json` and makes the existing worker bootstrap its actual origin/user/token source; verify the real bootstrap probe passes without changing workforce semantics.
- [ ] 3.2 The executor enforces private input and fail-closed atomic config replacement, preserving previous bytes and cleaning temporary token files; verify security/failure cases pass.
- [ ] 3.3 The executor removes token `/dev/null`/host/subpath mounts, direct duplicate origin/user inputs and unused runtime metadata while preserving enabled and disabled worker topology; verify rendered Compose and Jobs contract tests pass.
- [ ] 3.4 The executor reconciles operator documentation with `.env` as the only input and one internal private config; verify commands use placeholders and never print secrets.
- [ ] 3.5 Run only planner-selected bounded local checks plus relevant architecture/security checks, record elapsed time and outcomes, and do not run local full `make test`/`make verify`.

## 4. Gate 5 and publication readiness

- [ ] 4.1 Capture the exact candidate source/package required by the harness and have an independent `gpt-5.6-sol/low` reviewer record `reviews/code/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md`; resolve every finding and obtain explicit `APPROVED` for the final exact source.
- [ ] 4.2 Run the selected existing GitHub CI consumer once for the exact committed source, collect the complete failure inventory if non-green, and report CI/PR/deployment separately; production fetch, published rows and card display remain `UNKNOWN` until safely observed.
- [ ] 4.3 Mark tasks complete only from retained evidence and verify OpenSpec strict validation, focused checks, required reviews and exact-source CI satisfy the Done definition without merge or deployment.
