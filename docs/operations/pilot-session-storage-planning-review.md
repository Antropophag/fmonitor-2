# Independent planning review — PILOT-SESSION-STORAGE-001

Date: 2026-09-02  
Reviewer role: independent planning reviewer; no reviewed artifact, production,
test, task or spec edits performed by this reviewer  
Verdict: **CHANGES_REQUESTED**

## Scope and validation

Reviewed the OpenSpec proposal, design, delta spec and tasks against the current
`RapidPilotLocalAuth` and `RapidPilotUserAccessView` session paths, the approved
PILOT-HTTP-AUTH contract, CSP login verifier, router priority, Compose volume,
artifact/CSS ownership precedents and delivery gates.

Strict validation passes:

```text
Change 'define-pilot-session-storage-contract' is valid
```

Structural validity is not Gate 1 readiness. The following findings prevent an
executable, deterministic RED from being written without inventing security
semantics.

## Gate-blocking findings

1. **Configuration key, root meaning and default policy are not exact.** The
   artifacts do not name the environment key, say whether it denotes the
   persistent root, instance namespace or final PHP save directory, or define
   the deterministic instance namespace. The proposal promises a “production
   default”, while design decision 2 says absence is a configuration failure.
   Compose currently mounts `pilot-state` at
   `/home/fmonitor/.local/state/fmonitor2` but supplies no session-storage key.
   Gate 1 must choose one exact key, exact path derivation, exact absence/empty
   behavior and whether the current Compose path is compatibility input or a
   normative default.

2. **The ownership rule is impossible for an ordinary absolute path as
   written.** Requiring every ancestor to be owned by the current runtime identity
   rejects `/`, `/home` and normally the volume mountpoint. Define a trusted
   ancestor boundary and distinguish immutable trusted ancestors from the
   runtime-owned managed root/descendants. Specify exact accepted modes; “not
   group/world writable” permits 0711/0755 while creation mandates 0700, so the
   current test matrix has no unique answer for existing 0700, 0750, 0711 and
   0755 directories.

3. **The proposed ready-directory adapter cannot prove the stated TOCTOU
   guarantee with native file sessions.** `lstat/open/fstat/revalidation` can
   validate a descriptor, but `session_save_path()` followed by
   `session_start()` makes PHP reopen the pathname. A swap after adapter return
   remains possible. Gate 1 must either specify a descriptor-relative/custom
   `SessionHandlerInterface` seam whose opened object remains authoritative, or
   weaken the guarantee explicitly with an approved residual-risk boundary.
   “Identity revalidation before return/write” is not an implementable mapping
   until the write primitive and its identity are defined.

4. **Write/regeneration failure cannot currently be mapped to the promised
   exact 503/no-cookie response.** Native `session_start()` may emit a cookie,
   and `session_write_close()` or shutdown write occurs after application code
   has selected/emitted a success/redirect body. The spec groups start, write
   and regeneration failures but does not define primitive results, warning
   capture, header buffering/rollback, explicit commit timing, or what happens
   when a write fails after `session_regenerate_id(true)`. Define the public
   adapter operations and typed outcomes for open/read/start/regenerate/write/
   destroy/close, and the response-commit ordering that proves no `Set-Cookie`
   and no partial success response on each failure.

5. **There are two current native session owners, but the plan covers only
   LocalAuth.** `RapidPilotUserAccessView::resumeSession()` independently sets
   the same hard-coded save path and invokes native session primitives. A single
   Identity/Access boundary and the architecture ratchet must include this path;
   otherwise admin status/role commands retain an alternate session root and
   violate the new capability immediately.

6. **Route-priority and failure HTTP contracts are underspecified.** Current
   router assets return before LocalAuth, but unknown pilot routes reach
   LocalAuth and may redirect/start a session; the delta scenario says “Unknown
   asset route” while the tasks say “unknown route”. Enumerate the exact paths
   and methods that must read zero session configuration, including malformed
   URI/Host ownership by the outer HTTP boundary. For storage failures, enumerate
   the complete header set, `Content-Length`, `Retry-After`, HEAD parity and CSP
   value. “Safe security headers” is insufficient for an exact executable test,
   and current login responses do not share the PILOT-HTTP-AUTH shell header
   contract.

7. **Cookie/restart semantics need observable exactness.** Pin the existing
   cookie name derivation for no port and valid port, malformed/duplicate Host
   ownership, lifetime, Secure behavior behind the trusted proxy boundary,
   session-ID grammar, old-ID invalidation after regeneration, CSRF and
   `auth_return_to` persistence. Define “ordinary Compose restart”, session GC
   lifetime/clock assumptions, volume ownership provisioning and the exact
   proof that restart reuses committed session bytes without reseeding.

8. **Cleanup and concurrency ownership are not executable yet.** The design
   mentions attempt-all cleanup only for task-created objects, while the product
   spec forbids arbitrary cleanup and requires persistent restart. Define which
   actor creates production directories, whether production ever removes them,
   how concurrent `mkdir` distinguishes owned EEXIST from an attacker swap, and
   exact allowed outcomes for both requests. Test cleanup must be task-owned and
   must never be part of production adapter behavior.

## Gate and task corrections required

- Gate 1 must add the exact configuration/namespace/mode/primitive/HTTP tables
  above and cover both current session consumers.
- Gate 2 must demonstrate RED separately for pre-start validation, descriptor
  swap, start failure, regeneration failure, explicit write/commit failure and
  destroy failure; a generic injected exception is not evidence for all native
  phases.
- Task 3.2 must not claim “host+image GREEN” without a restart-with-cookie
  Compose verifier and an unprivileged host verifier using the same adapter.
- Task 4.1 must list the approved focused commands and distinguish Docker/setup
  failure from RED/regression failure.
- Gate 5 remains after full integration evidence; no review may approve its own
  authored correction.

## Reviewed hashes

```text
5c1f8a28cfcb0abb3dd91f9469dab26aa1589254b8546a156fb4f74b4a95099e  openspec/changes/define-pilot-session-storage-contract/proposal.md
d129a82e341d7e0d78b03bc41e1124fe24695c0a8e2c6125ce1060420c5997d6  openspec/changes/define-pilot-session-storage-contract/design.md
71b3e2695bdd6829da5926fdaef530b27a0f893069deb46e456303ce7368aac5  openspec/changes/define-pilot-session-storage-contract/tasks.md
4e46d9f4cf8c9ddf5fe5c03540c94ecaff03aa82aaf747cd5d26f68492dd9d07  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
d5c776a36a27377972c7b5f897ebcd95bead8154612cfdac8b1fbb98a869f406  rapid-pilot/LocalAuth.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
eec985d8181a590d9ff2c96aef50be56347b69737e5692b1bd217928bbf2dd75  tests/InstallationProcess/pilot_route_csp_login_001_test.php
b075db40047c604e5f71f992379e2caeafcf7f945acb80062d9b62b645008727  compose.yaml
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

Gate 1 draft is not ready for owner approval until all eight findings are
resolved coherently in proposal/design/spec/tasks and strict validation is run
again on the resulting hashes.
