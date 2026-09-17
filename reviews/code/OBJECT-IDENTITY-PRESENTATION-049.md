# Code review: OBJECT-IDENTITY-PRESENTATION-049

- Reviewer: `/root/issue49_final_review` (independent; did not author production code or tests)
- Implementation author: `/root/issue49_author`
- Reviewed source: candidate source `300abdb2efa0850035459b5826a40433783cfddfdd87ebed24de36ebbf40deb9`; base `b509e9147b78b6a68008c287e8f4323e9879230f`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T181505Z-52efc9c4ac/snapshot/source.patch` with SHA-256 `3b7031d2394beb7a7519bba55f52e98313ba91655ed6c08c762b262a3fda7621`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T181505Z-52efc9c4ac/package.json`; required-context SHA-256 `3fd4a882dc9583da46aeb10d3925503289ea2ee6b5e86d7cd487b20fd5e2bfb8`; context-manifest SHA-256 `9fc5ec55dcd94c1e36ac0e23540f037eb1b999eab521987b8b3623f553065f43`
- Agreed review scope: bounded issue #49 slice for the common object list and object-card identity header only; preserve internal IDs, exact routes/actions, filters and pagination; no schema, authorization, domain-rule, calendar, report or document changes
- Specification: `docs/fmonitor-2-object-card-spec.md` (`regnumber` and `zavnumber` in the header; legacy `id` restricted to technical details)
- Approved test review: not required by the planner-selected `COMPACT_MAINTENANCE` route; this is the single independent final review
- Verdict: `APPROVED`

## Evidence reviewed

- Executable RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789668242437711000-d6a3c0040a0143b7906311f6f9274d12.json` — `INTENDED_RED`, failing on the missing registration-number primary identity before implementation.
- `php tests/InstallationProcess/object_card_registration_identity_test.php` — `GREEN` for exact candidate source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789668659814347000-e127c50dda3c431c805afff00753eb05.json`.
- `php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php` — `GREEN` for exact candidate source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789668666467927000-678bd31f4f9c4c78a589c60839e4c176.json`.
- `python3 tests/Verification/change_verification_001_test.py` — `GREEN` for exact candidate source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789668666469157000-9300cc7c693d44698a2f8b3a2e9f3037.json`.
- `python3 tests/Verification/architecture_guard_001_test.py` — `GREEN` for exact candidate source; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789668709007632000-e6facb493056432caefc120762a0aa04.json`.

## Findings

None.

The reviewed source presents a filled registration number as the primary identity and the existing `zavnumber` projection as the secondary factory number in both bounded views. Native rows and retained migrated baseline payloads project both values; absent values remain absent and render explicit “не указан” text rather than falling back to an internal ID. The visible object-ID title and related shell/breadcrumb labels are removed, while list links and card actions retain the exact internal-ID routes.

The diff does not change pagination construction, origin filters, actions, authorization, persistence, schema, domain rules, calendar, reports or documents, and it adds no identification mechanism. It also adds no unsupported search hint. The regression test covers filled and missing numbers, rejects visible system-ID labels/substitution, and checks exact list/card navigation routes; source inspection covers the reader projection from `zavnumber` for both native and migrated data.

Exact-source CI remains a separate required publication gate and was `UNKNOWN` in the prepared package; this review does not report it as GREEN.

## Required changes

None.
