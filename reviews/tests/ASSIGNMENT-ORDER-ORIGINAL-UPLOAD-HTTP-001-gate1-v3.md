# Independent Gate 1 CSP amendment — ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not an author of the reviewed specification, tests or production implementation.
- Date: 2026-09-07
- Reviewed HEAD: `5d407ff57614e34040fccbc6e38ac7bc4545e2e8`.
- Reviewed specification: version 0.3, SHA-256 `19c42e9f68e3d0bc2204d6a2482c5cce595b9ce2106c66e4de05cd03968cb8a4`.
- Prior records: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-gate1-v1.md` and `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-gate1-v2.md` in this directory.

## Amendment and findings

No blocking findings in the narrow CSP amendment. Section 4 explicitly permits `script-src 'self'` and `connect-src 'self'` only for successful GET/HEAD submission forms at the route identified in section 1. This gives the specified external same-origin script and raw File upload an observable browser transport contract. It does not grant an exception to original POST results, redirects or failed form responses: these retain BASE CSP under sections 3 and 4. HEAD retains GET headers and emits no body.

The amendment preserves BASE restrictions and adds no explicit `worker-src`, blob source or `unsafe-inline` allowance. It must not reuse the broader CHECKLIST policy. Existing `PilotRouteCsp` classification and direct-header enforcement are relevant implementation boundaries: the real router response must preserve this exact narrow form exception rather than overwrite it with BASE or broaden other routes. These are verification obligations for Gates 2–5, not a claim that the implementation already satisfies them.

The rest of the v0.2 approval is reused. The amendment does not change transport framing, canonical metadata, CSRF/authorization ordering, original mutation ownership, replay/audit semantics, document dates or append-only history. General history/download, composition application and opening remain outside this child slice.

## Review evidence and limits

Read AGENTS.md, PRODUCT.md, CONTEXT.md, the pilot specification and data model, development-process.md, the normative v0.3 specification, both prior Gate 1 records and the OpenSpec design/delta specification. Inspected `app/PilotHttp/PilotRouteCsp.php` as existing boundary evidence. Confirmed HEAD and specification hash with `git rev-parse HEAD` and `shasum -a 256`.

Only this review record was created. No tests or production code were changed or approved. Gate 1 is **APPROVED**; independent Gate 3 and Gate 5 remain required, including actual HTTP header and browser evidence.
