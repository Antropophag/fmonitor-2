# Yii2 authentication — confirmed delivery checkpoint

PR [79](https://github.com/Antropophag/fmonitor-2/pull/79) is MERGED at
`d9811cdd5e4521457a1d61277069fe3d0793f3fe`. Exact candidate:
`d4a5049cffc760f4eeac96c1433ef47ba382f6c9`.

[Actions 34383652659](https://github.com/Antropophag/fmonitor-2/actions/runs/34383652659)
completed SUCCESS for fast, unit, both integration shards, e2e, governance,
verify and Quality Graph. Verify job `102591905168` printed literal `VERIFY_OK`
at 2026-09-09 18:25:08 UTC. This checkpoint was checked against GitHub and the
verify log; no duplicate local full run was performed.

Independent [Gate 5](../../reviews/code/YII2-AUTH-001-delivery.md) approves source
`694a2020d1d025dfa2351ab183b70c325d4539ed`; the diff to the candidate contains only
the review record and one lifecycle checkbox, with no code/test/config changes.
Gate 3 records are linked from that review.

Delivered: Yii login/logout, fresh identity/permission admission, standard session
and CSRF integration, persistence-failure handling, protected roles route and
its assets. Invitation/activation/user administration, other user routes and
stand cutover are not completed by this PR. Issues #71 and #76 remain open.

Next active delivery is the saved #70 settlement slice, as directed by the
[current delivery goal](current-delivery-goal.md). The authentication lifecycle's
unchecked delivery items are stale bookkeeping; archive/update them only with
this exact evidence and independent closeout review. This checkpoint does not
claim those lifecycle edits or a runtime switch.
