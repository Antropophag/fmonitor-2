# Original HTTP v0.2 — transport error candidate

Date: 2026-09-05. Author: `/root`.
Base: `b8f892e4888890ed795dde52b935f2d92ff683be`.

The draft now fixes a distinct pre-command JSON error envelope, exact
HTTP/error-code mapping and admission order, including malformed/CSRF
distinction. Application command Result responses remain separate. The
inherited unknown-route behavior is unchanged. Private download Range and
conditional requests use full authenticated integrity-checked responses;
HEAD suppresses only the body.

These are proposed technical decisions for Gate 1 review, not approvals or
implemented routes. Remaining transport streaming/truncation bounds, opaque
URI grammar, object scope and resolver/read API contracts are explicit. The
render-free composition selection dependency remains under independent
read-only inventory.

Exact draft SHA-256:
`bb7fa50459ea0c4a8e1983d5c9193be4d026d11e25f97e224fb2b8ccbd871a3f`.
File: `specs/ASSIGNMENT-ORDER-ORIGINAL-HTTP-001.md`.
`git diff --check`: exit 0. No production/tests/config/grants changed.
