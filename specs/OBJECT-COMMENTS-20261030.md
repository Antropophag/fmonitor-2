# OBJECT-COMMENTS-20261030

Employee requirement from Downloads “Требования по Fmonitor.docx”: text comment column for tracking reasons for delays. Owner2026-10-30 requests comments on every object.

- One current optional plain-text multiline comment per object, including not-opened, active and completed objects. Separate card section/form and final general-queue column with excerpt/link to full comment. No comment field mixed into equipment passport/technical modal.
- Existing `ObjectDetailsEditApplication` is sole writer; reuse JSON current projection, optimistic revision, request-id replay and append-only change events. No schema migration, separate comment writer, notifications, rich text, files or financial behavior.
- Normalize CRLF/CR to LF, trim edges; whitespace-only clears current text but history remains. Maximum2000 Unicode characters; invalidUTF8/C0/C1 controls rejected except tab/LF. Encode all rendered text and line breaks safely.
- Existing exact FKR operator/manager + objects.read + objects.details.edit policy retained. Other allowed readers see comment and author/time without edit control; crafted POST cannot bypass authorization/CSRF. All object lifecycle stages support the field.
- Comment-only form posts only comment plus CSRF/requestId/expectedRevision. It cannot accidentally change technical fields; success returns to #object-comment. 422/409 retain submitted text and show error/current persisted text; stale write creates no facts.
- Latest comment metadata uses the last comment event, not a later unrelated technical edit. History records old/new text, actor snapshot and time, remains immutable after later edit/clear. Queue uses existing joined projection, no per-row queries.
- Focused publicHTTP/browser tests cover save/edit/clear/replay, bounds/Unicode/markup, read-only/CSRF, conflict/recovery, unchanged unrelated data, metadata provenance and preopen/active/completed states. Desktop and narrow usability verified; existing object-details behavior retained.
