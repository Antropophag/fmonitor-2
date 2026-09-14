# Design

The existing Yii read model derives active assignments from the latest registered
`fm2_assignment_orders` version. Native selection/original application instead owns
current composition through append-only `fm2_assignment_order_applications`, whose
latest `application_sequence` contains immutable selected installer snapshots.

The directory query will build one bounded current-application projection per case,
decode and validate its selected snapshot in application code, and map selected tab
IDs to case/object descriptive data. Cases with any application row never fall back to
registered orders; cases with none retain bounded compatibility behavior. This keeps
one command owner and does not mutate history.

Summary, availability filters, page rows and assignment links consume the same
authoritative tabId set to prevent split read semantics. Invalid current snapshots
fail closed with the existing sanitized 503. The SQL budget may be adjusted only as
required by the verification planner and remains bounded with no per-row reads.

Schema: no migration. Deployment/rollback: application tables already exist on the
post-#40 frontier; rollback of this read-only code leaves all facts untouched. Backup,
restore, secrets, permissions and runtime readiness are unchanged. Adjacent workflows
remain outside scope.
