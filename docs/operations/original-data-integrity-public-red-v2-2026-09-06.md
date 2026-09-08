# DATA-INTEGRITY public RED/Gate3 completion v2 — 2026-09-06

The v1 archive/ledger remain unchanged. Independent review requested missing
negative-status composition query-echo axes and one-field lineage metadata leaks.
Root added only those cases; production, five shared helpers and the four other
approved scripts remain byte-identical.

New tests/source anchor: `14dece9` (full SHA in private manifest).
Private archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-data-integrity-public-red-v2-i4lew2ja`.
Final evidence SHA256:
`69130cd804ffa6b7d742a3f0ee01d86e7713d07f620111dc874c7ff3059d839d`.

- Composition92: 70 intended failures /22 controls; test SHA
  `01e3d41b3657fde35585c923c8c35c9a5f9e6a2a369e288f27a0ea71c2b91ad1`.
- Lineage106: 90 intended failures /16 controls; test SHA
  `651084f63e959f9c3cfcbbd30b959cee08289b3e7109e8b27f59e748dc79407c`.

Both scripts exited255 through collected assertion failures. The other four
scripts retain their exact v1 captures. The active public/scalar matrix totals
565 cases: 462 demonstrated failures and103 passing controls across these two
immutable captures, not a newly invented single aggregate run.

## Independent test approvals

All six public/scalar parts are APPROVED:

| Review | SHA256 |
| --- | --- |
| DATA-VALUES-001 v1 | e3d7cf6467ec3add24f61c637d2c9e597a22ac6ac82eded493a715f98760306a |
| DATA-COMPOSITION-001 v2 | 835e5e1414323b6328c1a6e49dc7a14eca996c523b25be30a4c3349b32770e07 |
| DATA-LINEAGE-001 v2 | 8ec3aa7d24fafa48012246d51f589a83c3d18944370d2dbbd36392b3d98ea3e1 |
| DATA-COMMIT-SCALARS-001 v1 | c6a41409e3e8af021001d4898b80730b3745010984b8d362fd1d81166060139a |
| DATA-RECOVERY-001 v1 | eecd3408cb1692238f08e293ed272f6eb89f96b92cd3598c799d7bc5161f939d |
| DATA-ATTEMPT-CLOCK-001 v1 | 4b7e40e8a740b6ab5bd7cd3772bd264c405b5a00ee38e9ba9d791dc5efc55a8b |

Each record is under `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-<review>.md`.
COMPOSITION/LINEAGE v1 CHANGES_REQUESTED records are retained unchanged.

Four earlier v1 review records contain an extra blank line at EOF and produced
preserved whitespace diagnostics when staged. Their reviewed bytes/hashes were
not edited and those diagnostics are not PASS. New v2 records were checked
explicitly against `/dev/null` before hashing because ordinary git diff does not
inspect untracked files; they use one terminal LF and no whitespace diagnostics.

Real MariaDB snapshot/rehydration/transaction/factory/worker proofs and exact old
fixture amendments remain separate required Gate3 parts. No data-integrity
production code has changed and no cumulative GREEN/Gate5/launch claim is made.
