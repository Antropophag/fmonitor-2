# Code review: OBJECT-IDENTITY-PRESENTATION-049

- Reviewer: `/root/issue49_final_review` (independent; did not author tests, policy, or production code)
- Test/setup author: `/root`
- Implementation author: `/root/issue49_author`
- Reviewed source: exact candidate `551b3059459a2da59d58994fa2bf9a29c81d59aa8b6d75e26c863d2434eb8ebb`; delivery base `b509e9147b78b6a68008c287e8f4323e9879230f`; retained correction snapshot base `592d5ee6a2ef575d7bf51e37ac83d5fc95c836b7`, patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T192044Z-7ba057f2df/snapshot/source.patch`, SHA-256 `4ce4f711e6d21164846450ba7cc0379429f6c8c6bb38ecb06455a84988dfe114`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T192044Z-7ba057f2df/package.json`; required-context SHA-256 `1a69b717ab6fe4b3686415a29c7a973a7f0f0ca073753fce2f7db1da091612ce`; context-manifest SHA-256 `e4ddd1370401c2c38ae1c1eabe8c1dfad32dae7fe25ba753800d885e7fc8c02b`
- Specification: `docs/fmonitor-2-object-card-spec.md`, bounded issue #49 identity-presentation slice
- Agreed scope: common native Yii object list and object-card identity header; preserve internal IDs/routes/actions/data attributes, auth, state/provenance validation, filters and pagination; no schema/domain/calendar/report/document change
- Gate 3 test/policy re-review: `APPROVED` for this exact source (record `reviews/tests/OBJECT-IDENTITY-PRESENTATION-049.md`)
- Gate 5 verdict: `APPROVED`

## Findings

None.

The complete diff against `b509e9147b78b6a68008c287e8f4323e9879230f` implements the slice on the authoritative Yii path. The old PilotHttp renderer/reader files and their old focused test have zero diff. `ObjectQueueController` and `ObjectCardController` continue through `InstallationProcessFactory` to `MariaDbYiiObjectQueue` and `MariaDbYiiObjectCard`, then render the Yii views.

`MariaDbYiiObjectQueue` adds `l.zavnumber` to the existing paged SELECT and maps it from the same row, so no per-object query or N+1 path is introduced. `MariaDbYiiObjectCard` already selected `l.zavnumber` and now maps that selected value. Both readers permit absent registration/factory numbers while retaining required address/entrance, identity linkage, authorization, process/opening tuple, provenance/detail hash, uniqueness, and fail-closed state checks.

The views use registration number as the primary identifier and factory number as secondary, with explicit “не указан” text and no fallback to object ID. Internal IDs remain in exact hrefs, scheduling action paths and `data-object-id`; action labels use the business identity. Filters, pagination and status behavior are unchanged. The search placeholder now truthfully advertises only registration number, address and entrance, although the pre-existing server-side ID query remains available; it does not promise unsupported factory-number search.

The verification policy owns exactly the two changed MariaDB Yii adapters with exactly the two real Yii tests. No classifier or check-skipping algorithm changed. There are no migrations, writes, permission changes, domain transitions, calendar semantics, report changes, or document changes in the candidate.

## Evidence and prior correction

- Exact-source queue, card and governance checks are `GREEN`: records `1789672662650638000-870be2d04fcd440c88deb33133bd9459`, `1789672688231144000-d0f8bdde9e6a4e1e8f075ba46d36fc9c`, and `1789672718420708000-fc95033513df4f6ba870da074b47c945`.
- Exact-source `make architecture-check` is `GREEN`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789672747039486000-c9bbed46f455490fac386646af07c560.json`.
- The prior PR #187 CI attempt (run `35258534936`) remains a failure with complete failed-job inventory `fast`, `verify`, and `Quality Graph`. The correction removes the reviewed-but-nonauthoritative PilotHttp implementation delta, exercises and implements the native Yii seam, and adds bounded capability ownership. This review does not relabel the prior run or current CI as GREEN; an exact committed-source CI rerun remains outside this verdict.

## Required changes

None.
