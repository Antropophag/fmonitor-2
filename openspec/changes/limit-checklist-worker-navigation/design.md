# Design

The registration scope remains `/pilot/` because a Service Worker must cover
both checklist aliases and is registered by the construction-control queue.
Scope does not imply ownership of every fetch: the fetch listener calls
`respondWith` only when `request.mode === "navigate"` and `checklistPath()` is
true. Asset caching and logout cache purging retain their existing branches.

The public test seam is the active worker's fetch event. It proves unrelated
pilot navigations do not receive a worker response, while both checklist aliases
do and retain their offline fallback. This is client-only; persistence, audit,
authorization, replay, schema, backup and restore are unaffected.
