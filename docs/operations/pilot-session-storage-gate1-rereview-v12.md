# PILOT-SESSION-STORAGE-001 v9 unknown-route amendment — independent Gate 1 re-review

Date: 2026-09-03  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification amendment, owner decision, OpenSpec artifacts, tests, or implementation  
Change: `define-pilot-session-storage-contract`  
Owner decision: `docs/operations/pilot-session-storage-unknown-route-owner-decision-2026-09-03.md`  
Verdict: **APPROVED**

## Reviewed artifact hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
76a786103463ea99321438ed037c9cc020ad0275e409e341c63126af904a03ef  openspec/changes/define-pilot-session-storage-contract/proposal.md
2d2fb80278c694d69009ad9c598fcdb703bdf487a6b8e3cce2cec0191592ff04  openspec/changes/define-pilot-session-storage-contract/design.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
484b22b9a48219d9640a1881e6983339339c669058f8e4fcd29615789bf98118  docs/operations/pilot-session-storage-unknown-route-owner-decision-2026-09-03.md
```

These hashes identify the current uncommitted planning artifact bytes reviewed here. Any subsequent change to them invalidates this approval for Gate 2.

## Findings

- The amended normative outcome is exact: every unrecognized path under `/pilot/*`, including an unknown non-asset path, returns the inherited `404`, `Content-Type: text/plain; charset=UTF-8`, exact body `Not found.\n`, matching `Content-Length`, the inherited security/cache headers, and an empty body with GET-equivalent length for HEAD. It emits no `Location` and therefore cannot redirect to `/pilot/login`.
- Route matching precedes session environment/configuration, session filesystem/primitives and authentication. The OpenSpec scenario strengthens this to zero session environment, filesystem and primitive access. Both anonymous requests and requests carrying a valid authenticated session receive the same 404, preventing identity-dependent disclosure of route existence.
- The amendment does not move route matching ahead of the outer request-integrity boundary. Malformed/duplicate Host and malformed URI remain rejected by the inherited outer boundary. For a valid normalized request, the established `PILOT-HTTP-AUTH-001` priority remains: route recognition before method and authentication; therefore an unknown route is 404 rather than 405, 401, 403, 503 or 303.
- Known route behavior remains distinct. Exact known and unknown `/pilot/assets/*` paths retain their asset-first handling without session access. Only a known login-required application route may enter LocalAuth and produce `303 Location: /pilot/login`. Known public/login routes are not swallowed by the unknown-route fallback.
- This resolves the previous ambiguous “unknown non-assets retain predecessor auth behavior” language consistently across the executable spec, proposal, design, delta specification and task 2.3. No contradictory fallback remains in the reviewed change artifacts.
- The owner explicitly approved the anonymous/authenticated pre-session 404 and retained the known-route-only login redirect. The amendment changes no storage persistence, authorization, audit or append-only domain behavior.

## Verification

```text
$ openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

$ git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
    openspec/changes/define-pilot-session-storage-contract \
    docs/operations/pilot-session-storage-unknown-route-owner-decision-2026-09-03.md
(no output; exit 0)
```

## Gate decision

The amendment has one observable route seam, an independently determinable exact result, explicit anonymous/authenticated cases and a fail-closed dependency-call boundary. It is compatible with `PILOT-HTTP-AUTH-001` and preserves outer Host/URI plus known-route priority. Gate 1 passes for the reviewed hashes. Gate 2 must prove the exact inherited 404 and zero session-env/config/filesystem/primitive/auth calls for both anonymous and authenticated requests; it must also retain known asset and known login-required route controls so a broad 404 short-circuit cannot pass.
