# Test review: CHECKLIST-HTTP-AUTHORIZATION-001

- Reviewer: Codex independent reviewer `/root/review130`
- Test author: root agent
- Reviewed source: base `44880bbe4df579789a094fda526e6a4415598b29` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T155852Z-24db2e3fdf/snapshot`, patch SHA-256 `5b8025f0efa662652246bc06a63a20797231aaf4abf191379770476fc95a6487`
- Candidate source: `d80677ff318e7505e0917fdc8100b2cf6214e8184fcf79ca06c24be66afa502c`; executable source `b066fd008d1e9c99d44278d9401fbfeef98194e30e6eb4f1e9caa26a63d76e09`
- Agreed review scope / prior findings disposition (for rereview): Initial Gate 3 review for issue #130. The package contains the normative specification, the delta to the existing Yii2 inspection journey, the generated verification plan, and intended-RED evidence. `docs/operations/issue-130-delivery.md` was added after capture as documentation only and is not part of the reviewed executable source. No Makefile, legacy-import, integration-config, or `openspec/changes/yii2-imports-workforce` change is present.
- Specification: `specs/CHECKLIST-HTTP-AUTHORIZATION-001.md`, A1-A6
- Public seam: real Yii entrypoint `public/yii.php`; canonical and construction-control alias POST routes for checklist operations and photos
- Red command and intended failure: `php tests/Yii2/yii2_inspection_journey_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789401513224323000-89db8af30b054a18b6b112c2fabcf21e.json` is `INTENDED_RED` (exit 255): the authenticated read-only fixture's real HTTP mutation returned 422 where the new exact authorization contract requires 403.
- Verdict: `APPROVED`

## Findings

None.

- Traceability and seam: the test cites A1-A6 and drives authenticated requests through the real Yii HTTP server/entrypoint with session cookies, valid CSRF, JSON/photo transports, and both canonical and construction-control aliases. SQL is confined to deterministic fixture setup, permission changes, and independent facts/audit inspection; it is not used as the submitted command seam.
- Authorization and disclosure sensitivity: `checklistSafeDenial()` requires exact 403 and typed `rejected`, and rejects projection/revision, checklist content, crew/personnel, identifiers, and photo-name leakage. The no-read/no-action actor covers existing replay identity, fresh input, stale revision, and invalid crew. The photo-revoke-only actor covers the separate duplicate, conflict, and business-rejection paths without gaining checklist read access, so a controller that appends projection after those domain outcomes will be caught.
- Positive and adjacent behavior: the authorized actor has independently asserted accepted revision/actor/installers and retained projection for duplicate, conflict, and business rejection. Existing correction, retraction, section, photo upload/replay/revoke, alias, refresh, restart, and rollback paths remain exercised.
- Rejections and preservation: read-only denial is exact 403; no-read operation and photo denials preserve the complete fact inventory and private artifact hashes. Guest and deactivated sessions preserve 303 login redirection, disclose no protected content, and add no facts. Existing CSRF, method, malformed JSON, media type, body-size, and photo-size semantics remain asserted.
- Expected-value independence and determinism: expected statuses, revisions, actor 73, installers 7001/7002, protected markers, and business outcomes are literal contract values. The fixture uses isolated database/artifact/session namespaces, fixed command identities and payloads, and cleanup in `finally` blocks.
- RED evidence: the failure occurs at the new exact-403 assertion after successful fixture setup, not from environment/setup failure. It is sensitive to the missing behavior and precedes implementation.
- Scope completeness: the generated plan binds all A1-A6 acceptance to the selected real HTTP journey and retains the applicable focused consumer/governance/integration checks for Gate 4. No requested behavior or material adjacent flow is missing from Gate 2.

## Required changes

None.

## Correction Gate 3 review — after initial Gate 5 return

- Reviewed correction source: base `44880bbe4df579789a094fda526e6a4415598b29` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T162016Z-16f5e4118a/snapshot`, patch SHA-256 `6baf1715530d487580e90efedca7160a7094f98424b6f4755356a9509aa74e31`
- Candidate source: `786436cc51e7ffa292268f7612878f2ee7d3162bda974bfda3f556f06ec22946`; executable source `d0343a4612697b54088578febe5227c4aef07500d48cd492e0d142abcb9b4c1d`
- Correction scope: Both blocking findings in the initial Gate 5 review: sensitivity to removal of controller read admission and exact 403 precedence for every read-only action denial, including item 42 and completion retraction. Production files are unchanged in this correction package.
- Verdict: `APPROVED`

### Findings

None.

- A2 now exercises one authenticated read-only actor across ordinary item completion, item 42, installer correction, section completion, completion retraction, photo revocation, and the existing photo upload case. Every denial requires exact 403/`rejected`, no protected response fields, and an unchanged complete fact inventory. The authorized item-42 409 and exact documentary message remain independently asserted, so authorization precedence cannot be satisfied by deleting the business rule.
- A4 now temporarily makes actor 96 the formally assigned engineer and gives only `inspection.photo.revoke`, while its real GET remains exact 403 because it has no construction-control role, `checklist.read`, or item-completion grant. Both `photo_revoked` and `completion_retracted` are then sent with existing replay identity, excessive revision conflict, and business-invalid targets. Assignment and capability therefore cannot mask deletion of the controller read gates; without those gates the owner can independently produce duplicate/conflict/rejected outcomes that the safe-denial assertions catch.
- The assignment and temporary capability are fixture-only setup, not a submitted command or role-policy change. The pre-mutation fact/artifact snapshot is taken after setup, every request is made through the real Yii HTTP seam, and `finally` restores both permission and original formal engineer. Fixed identities and isolated fixture state keep the matrix deterministic.
- Actual-candidate RED is retained in record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789402797479876000-d0f0b02a38b64b9589c1c2593d21349b.json`: exit 255, expected 403 and actual 409 at the new reader item-42 assertion after successful setup and preceding behavior.
- The earlier private read-guard mutant evidence remains relevant sensitivity evidence: removing only both controller read-denial lines from the first Gate 5 source allowed the former test to pass. The strengthened A4 matrix directly closes that demonstrated gap. The mutant is fault-injection evidence only and is not the intended final production source.
- Traceability, expected-value independence, rejection/no-facts coverage, aliases, transport behavior, guest/inactive behavior, and adjacent authorized synchronization flows remain intact from the initial approved Gate 3 candidate.

### Required changes

None.
