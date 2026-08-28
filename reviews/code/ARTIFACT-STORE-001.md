# Code review: ARTIFACT-STORE-001

- Reviewer: `Codex agent /root/migration_code_review` (independent, security-focused Gate 5 reviewer; did not author specification, approved tests, or implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Reviewed scope: artifact store, renderer/download service, config/factory integration, approved v0.2 spec/tests/reviews
- Previous superseding verdict: `CHANGES_REQUESTED` for applying shard ownership/mode rules to configured root and protected ancestors
- Final superseding verdict: `APPROVED`

## Standards and security

`APPROVED`. Configuration has exactly three mandatory non-null strings, both factory seams validate and construct the required store before connection initialization, and no non-storing fallback remains.

Filesystem roles are now separated correctly. Trusted home/intermediate ancestors must be real non-symlink directories with no group/other write bits but need not have the effective owner. The configured root additionally requires effective-UID ownership, remembered device/inode, access checks, and permits protected mode `0755`. Store-managed shards require effective ownership and mode no wider than `0750`; existing and opened blobs require effective ownership, regular-file identity and mode no wider than `0640`.

Root/chain validation remains around mkdir, temporary write, publication, existing-target verification, open and final return. Root swaps fail by device/inode. Concurrent mkdir is accepted only after validation; same-leaf randomized temp, flush/fsync and no-overwrite hard-link publication remain atomic and idempotent. Reads are bounded to expected size plus one and verify inode, size and hash. Authorization is first, paths derive only from validated metadata, errors are redacted, storage failure leaves only the required rejection audit, and cleanup is exact.

No blocking standards, security, portability or maintainability finding remains within the approved trusted-account threat model.

## Spec

`APPROVED`. The allowed `0755` root works through both direct store and production factory while managed children retain stricter ceilings. Unsafe writable chains, `0755` shards, `0666` blobs, symlinks, root replacement, corrupt/missing/oversized content, concurrent first publication, invalid configuration and unauthorized download all fail through the specified boundaries. Required config, both pre-SQL factory seams, exact artifact persistence/download, transaction/audit behavior and external-free reload match v0.2.

## Verification

```text
artifact_store_001, production_composition_001, order_prepare_007,
persistence_prepare_001, document_render_html_001
# focused/related PASS

PHP syntax: PASS
InstallationProcess suite: 36/36 sequential, 36/36 parallel PASS
scoped git diff --check: PASS
find .test-artifacts -mindepth 1 -maxdepth 1 -print
# no children
```

The intentionally dirty handoff has no usable commit fixed point; unrelated changes were excluded.

## Findings

None.

Gate 5 is approved.
