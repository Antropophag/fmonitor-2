# Owner approval — PILOT-ROUTE-CSP-001

Date: 2026-09-02  
Decision: **APPROVED**  
Owner response: `Согласовано` after plain-language CSP explanation  
Reviewed executable SHA-256:
`47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`

Approved contract: same-origin external JavaScript only on exact successful
script-enabled HTML route/result tuples; no inline/eval/third-party scripts;
strict errors/redirects/assets/script-free pages; checklist-only worker/connect/
blob extensions; bounded successful scripted `POST /pilot/login` exception;
CompletionFlow inline behavior externalized without changing progress semantics.

Gate 2 RED is authorized. Production changes remain prohibited before
independent test approval.
