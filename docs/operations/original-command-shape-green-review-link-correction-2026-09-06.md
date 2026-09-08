# Shape GREEN review-link correction

The initial reviewed GREEN record hash is `f458a7f705ff7f5e217f50e860a117c5ff8042832b9d0035481df958da4eda95`.
Commit b94dba7 added a post-review approval sentence to that record; it changed
only the narrative link, but no longer matched the GREEN hash frozen in Gate5.
This correction restores the exact reviewed GREEN bytes. Approval lives in the
separate immutable review and tasks disposition; no source/test/log/exit changed.
The intermediate commit is retained in append-only Git history.

Scoped Gate5 APPROVED at bca89b4853: review hash
`9ad8dbe9c0f0d46fab0e18a50bfb25e7e7eaa27c97edd501a624d4e1f46fe559`.
