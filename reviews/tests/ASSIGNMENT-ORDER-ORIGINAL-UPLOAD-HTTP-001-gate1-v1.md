# Independent Gate 1 — ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/original_http_review`, separately tasked agent; author of no reviewed specification, tests, or production code.
- Date: 2026-09-07
- Reviewed HEAD: `adcef69dd413ad1809dba4cf0545b5036e2f26ed`
- Reviewed specification: version 0.1, working-tree SHA-256 `a9736da7d575822c26ffd1a5b91004f8ca82c46d1c86a69c8409f7f93c4bc12b`.

## Material finding

Sections 2, 3 and 6 need one explicit native transport boundary clarification before RED. They currently require exact application JSON for missing/invalid Content-Length and declared/received mismatch while prescribing a real PHP HTTP seam. Native server framing can reject or stop an incomplete/malformed request before the adapter executes; bytes after a declared HTTP body are not necessarily exposed as part of `php://input`. The adapter cannot promise its JSON envelope for requests that never reach it, or compare declared length with unavailable raw wire bytes.

Define “received” as the bytes exposed by the native request-body stream. Preserve bounded acquisition and exact declared/exposed-byte comparison for requests admitted to PHP. State that pre-PHP framing failures are characterized using real raw HTTP and the native server's actual outcome, with no application invocation, rather than assigning them an unattainable adapter response. The existing launch obligation for webserver buffering remains separate. No domain behavior expansion is requested.

## Reviewed scope otherwise coherent

The slice names a concrete public route, immutable order identity, native selected-original mutation owner, read-only submission-context owner and exact local admission grants; the production command repeats native process authorization. Current-action form grants do not replace POST mode authorization or the approved replay/audit policy. Validated lineage and composition remain under the original owner.

Canonical fixed-key base64 JSON, raw PDF bytes, bounded acquisition, CSRF priority, safe errors and unchanged production result encoding form a concrete adapter contract. Initial and correction UI behavior includes identity-safe retry, explicit confirmation, reason and document date, no-template parity, same-order template-date prefill, correction-date prefill, and public shlz components.

Configuration explicitly reuses password-file recovery and forbids runtime bootstrap or a fallback writer. Upload/correction neither applies composition nor opens work. General history/download and assigned-engineer read authority remain pending in the parent change; this child is not parent completion. Approved original parsing/binding/audit contracts were reused, not reopened for domain review.

## Artifact hashes

Paths below are relative to `openspec/changes/expose-assignment-order-original-upload-ui/`:

| Artifact | SHA-256 |
| --- | --- |
| proposal.md | `0c34ff35136feee2e76b62fed7184a4bea4652c12eca357b8ac8ae88297f9006` |
| design.md | `c5181beb9ff9d704a6654718d29edbace2ee6f8c5b5e3da297d3b4694c4ff33e` |
| tasks.md | `01c4828d7623190c7c36b7668f960cb9c5a8fe13d70dff3680a66663dddba890` |
| specs/pilot/assignment-order-original-upload-ui/spec.md | `32902b3228addd1b1729ce90a6f70d50bd200b1556a1415d1c33ac91b76ea845` |

Review used AGENTS.md, development-process.md, product/pilot contracts and the existing selected-original factory, stored reader, result encoder and recovery configuration as boundary evidence. No tests or production code were authored or changed. This is Gate 1 only; it does not approve tests, implementation or integration.
