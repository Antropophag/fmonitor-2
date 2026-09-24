# CALENDAR-EFFECTIVE-OBJECT-DETAILS-001

## Простыми словами

После разрешённого исправления реквизитов календарь должен сразу показывать тот же адрес, подъезд и регистрационный номер, что карточка и реестр. Исправление не меняет даты или события и не создаёт новый механизм хранения либо вычисления реквизитов.

## Actor and public seam

Actor: active user with `objects.read`.

Public action: authenticated `GET` or `HEAD` of `/pilot/calendar` for a valid selected date/range. Regression seams are authenticated reads of `/pilot/objects` and `/pilot/objects/{id}`.

## Preconditions

- A legacy installation object has address, entrance, registration number and one or more existing calendar dates.
- It may have the single current edit row governed by the existing object-details contract.
- Inspection events retain their schedule identity; planned events retain their object/type identity.

## Normative acceptance

### A1 — one effective-values contract for every event type

For `inspection`, `planned_start` and `planned_end`, the observable event label/details contain the values resolved by `MariaDbEffectiveObjectDetails::sqlValue()` for `address`, `entrance` and `regnumber`. No second resolver, copied correction table or synchronization is introduced.

### A2 — established fallback and explicit-value semantics

For each field independently:

- an absent edit key displays the legacy value;
- an explicit JSON `null` displays an empty value, not legacy;
- an explicit empty string displays an empty value, not legacy;
- a non-empty corrected value displays that value.

Whitespace trimming remains the existing calendar display behavior.

### A3 — corrections are current on the next read

Replacing the current correction with a later revision changes all three event types on the next HTTP/browser reload, without reimport and without changing legacy rows, dates, schedules, historical facts or documents.

### A4 — identity, ordering and bounded reads

The correction does not change event count, schedule/object/type/date identity or the existing order. Both source queries retain their 5000-row bounds and combined overflow behavior. Resolution is performed in the bounded source queries; no per-object/N+1 reads are allowed.

### A5 — authorization, errors and read-only behavior

Existing authentication, `objects.read`, GET/HEAD validation, safe errors and cache behavior are unchanged. Repeated calendar, card and registry reads create no DB/history/job/outbox records.

### A6 — neighboring projections do not regress

For the same object, the card and registry continue to render and search by the effective values. Calendar correction must not alter their query/filter/pagination behavior.

## Rejected cases

Invalid date/query shape, absent permission, incompatible schedule schema and source overflow retain their existing exact HTTP behavior. This slice adds no mutation command and therefore no new rejection reason.

## Independently worked example

Legacy values are `Legacy address`, entrance `2`, registration `LEG-4512`. Current JSON is `{"address":"Current address","entrance":"","regnumber":null}`. Every existing calendar event for the object displays `Current address`, empty entrance and empty registration. After revision 2 becomes `{"address":"Next address","entrance":"7","regnumber":"CUR-4512"}`, the next reload displays those three values for inspection, planned start and planned end while their dates and identities are byte-for-byte unchanged.
