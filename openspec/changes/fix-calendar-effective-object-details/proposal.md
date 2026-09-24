## Why

The registry and object card already resolve the current effective address, entrance and registration number after an authorized correction. The calendar reads those three fields directly from the legacy table, so the same object shows stale details there.

## What Changes

- Inspection, planned-start and planned-end calendar events use the existing effective-values SQL contract.
- Legacy fallback, explicit null/empty corrections and a later correction retain the established semantics.
- Event identity, dates, ordering, bounds, authorization and read-only behavior stay unchanged.

## Impact

The production change is bounded to `MariaDbYiiObjectQueue::readCalendar()`. There is no new resolver, writer, schema, import, date source, UI shell or deployment change.
