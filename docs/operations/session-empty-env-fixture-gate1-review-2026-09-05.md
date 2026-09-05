# PILOT-SESSION-EMPTY-ENV-FIXTURE-001 — independent technical Gate 1 review

- Review date: `2026-09-05`
- Reviewer: independently tasked technical Gate 1 reviewer
  `/root/session_env_fixture_review`; reviewer authored none of the reviewed
  specification, test, runtime, or OpenSpec artifacts
- Reviewed repository HEAD: `af75b2002766391ad0dce78bf609e4bbdeeadfa7`
- Exact-image evidence supplied for review:
  `tagfm2-session-check-3c9ce25b4a:af75b2002766`, whose 382 source files were
  reported byte-identical to that HEAD
- Verdict: **APPROVED**

## Determination

The draft is a narrow, constructible test-fixture correction. The inherited
configuration contract distinguishes an absent `FMONITOR_SESSION_STATE_ROOT`
from a present empty value: absence selects the compatibility default, while
the empty string is `CONFIGURATION_INVALID` and maps through the existing exact
HTTP 503 response. The current runtime preserves that distinction:
`ProcessEnvironmentSource::read()` returns native `getenv()`, and
`LazyPilotSessionStorage` falls back only on strict `false`.

The observed `[false,false]` from both native `getenv(name)` forms when the
current test passes an empty string solely in `proc_open`'s environment map
shows that the child did not receive the approved input. The same child invoked
with exact argv prefix `/usr/bin/env FMONITOR_SESSION_STATE_ROOT=` observes
`["",""]`. Because the exact image has a usable compatibility default, the
resulting HTTP 200 is consistent with the inherited absent-key behavior and is
not evidence for changing production behavior or the expected 503.

The amendment therefore adds no product outcome and requires no product-owner
decision. It supplies technical Gate 1 only. It does not constitute Gate 2,
Gate 3, GREEN, Gate 5, Compose restart evidence, or completion of parent tasks
2.3, 3.2, 4.1, or 4.3.

## Approved fixture boundary

Gate 2 may prepare an unapplied patch to
`tests/InstallationProcess/pilot_session_storage_protocol_001_test.php` with
only these effects:

1. When, and only when, `$extra` contains the exact known key
   `FMONITOR_SESSION_STATE_ROOT` with exact value `''`, prefix the existing PHP
   server argv with `['/usr/bin/env', 'FMONITOR_SESSION_STATE_ROOT=']`.
   Continue to pass the existing cwd and environment map to `proc_open`.
   Absent and nonempty cases retain the existing argv. No shell is involved.
2. Before that server start, run a setup probe with the same prefix, PHP binary,
   cwd, and environment map. Its child may inspect only the named session-root
   key through `getenv(name)` and `getenv(name, true)`. Exact success is exit 0,
   stdout `["",""]`, and empty stderr.
3. Treat a missing/different value, malformed or excess output, nonempty stderr,
   nonzero exit, timeout, or incomplete cleanup as `SETUP_FAILURE`. Such a
   result cannot qualify as a product RED and the HTTP assertions must not run.
4. Enforce the specified monotonic 3-second deadline, 4096-byte limit per
   output stream, TERM plus 500 ms grace, KILL if still live, and bounded reap
   within 2 seconds. Drain both pipes while supervising the child so the bounds
   and deadline do not depend on pipe-buffer behavior, and close all probe
   resources before starting the server.
5. Keep the existing server command after the approved prefix, readiness and
   stop behavior, raw GET/HEAD/POST 503 expectations, route/asset/Host/URI
   priority, positive HTTP/HTTPS cases, injected dependency cases, and cleanup
   assertions unchanged.

Passwords and other credentials remain only in the environment map; they must
not be copied to argv, probe output, diagnostics, or logs. The patch must not
add a production selector, test-only application handler, alternate runtime
configuration rule, E2E edit, or any form of the rejected safe-log verification
mechanism.

The reported exact-image suite split—19 of 25 session tests passing, five
blocked by database connection setup, and this protocol test returning 200
where 503 is expected—is coherent with this determination. The five database
setup failures are unrelated and cannot be counted as RED or GREEN evidence for
this correction. Gate 3 must independently review the exact patch, probe
bounds, native child observation, and preservation of the HTTP matrix before
the patch is applied.

## Exact reviewed SHA-256

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
0de0578ade9509923a322464c52e5958c5038a3a09ec3063b8be4a6de255918e  rapid-pilot/AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
3d8ee519176159808fede3c181bd5ec45c2b8419ab06713f5277400717b202bb  docs/fmonitor-2-session-handoff.md
d7c9a4acd71aaadd25c21bed1bc836b34ee8e61c64c6318877e44301711760cf  specs/PILOT-SESSION-EMPTY-ENV-FIXTURE-001.md
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
0f57d5a958ae5cc6cd1484325810d733a1ab1a162d2f257b3adcc87769990a3a  openspec/changes/define-pilot-session-storage-contract/.openspec.yaml
78d3ad3a82d4ac3a3ee80e72412a2aa31d101724b09e0fda5172ed0ba1ec1aef  openspec/changes/define-pilot-session-storage-contract/proposal.md
7c12ecc8c52f9ce411f57bf93270bc5fac09e35166e64482983e212122fb8ceb  openspec/changes/define-pilot-session-storage-contract/design.md
56297bf2c005f837366e745ba7072753b76b6e82e11541957b221850d687f252  openspec/changes/define-pilot-session-storage-contract/tasks.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
ee1e8eec0ae2b0044f76ea255033aec36b0edeeb699dd667fef093152058e1c5  app/PilotHttp/LazyPilotSessionStorage.php
```

## Final verdict

**APPROVED** for the exact current candidate and boundary above. An exact
test-only patch may now be prepared but must remain unapplied until a fresh
independent Gate 3 review approves the patch and its qualifying RED evidence.
