# Limit checklist worker navigation

## Why

Checklist offline support currently intercepts every `/pilot/` navigation. A
network exception inside the worker therefore replaces unrelated application
pages with a local 503 response.

## What changes

Only the two checklist document routes remain worker-controlled. The
construction-control queue still registers the worker and prefetches checklist
documents. All other pilot navigation is left to the browser network stack.

## Impact

The active Yii checklist worker and its focused behavior oracle change. No
schema, domain facts, permissions, HTTP routes, queue operations, cached document
format or offline checklist behavior changes.
