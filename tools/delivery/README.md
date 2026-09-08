# Delivery governance

Run `uv sync --frozen`, then `make governance-test quality-graph-validate`.
`make delivery-evidence-check` validates opted-in receipt chains offline against
actual Git HEAD. Historical records without receipts remain outside enforcement.
The schema, metadata fields, exact Git sets and chronology are defined by
[QUALITY-GRAPH-GOVERNANCE-001](../../specs/QUALITY-GRAPH-GOVERNANCE-001.md).
Review templates live in `reviews/tests/TEMPLATE.md` and `reviews/code/TEMPLATE.md`.

Change `quality-graph.yml`, then run `.venv/bin/qg generate`. Preserve the generated
publisher in `.quality-graph/generated-publisher-v0.1.7.yml`; the deployable publisher
is its exact privilege-removal transform: remove the issue_comment trigger, the
command job, and the publish job's issues/pull-requests write permissions.
`make quality-graph-validate` verifies both compiler output and this transform.

`make ci-setup` prepares a dedicated GitHub runner with the same pinned TCPDF,
public SHLZ build and browser channels as the pilot. It refuses occupied dependency
destinations. It does not start or reset the pilot. The retained baseline workflow
and graph both call `make fresh-test-verify`; only the disposable test DB is torn down.
Both remain present until actual phase A and phase B parity are established.

Current post-review evidence is restricted to these exact paths:

- `docs/operations/quality-graph-governance-final-verification-2026-09-08.md`
- `docs/operations/quality-graph-representative-pr-phase-a-2026-09-08.md`
- `docs/operations/quality-graph-publisher-phase-b-2026-09-08.md`

The publisher Python adapter exercises the upstream artifact-validation boundary
offline. Real trusted publishing remains an independent phase B check against
base-branch topology; offline tests cannot establish that deployment result.
