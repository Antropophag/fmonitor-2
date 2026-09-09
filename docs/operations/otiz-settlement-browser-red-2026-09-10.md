# OTIZ-SETTLEMENT-001 browser RED — root-authored

Root authored the browser specification supplement, tests and registration after
the owner's explicit authorship correction. Implementation remains delegated and
reviews independent. Normative example is `specs/OTIZ-SETTLEMENT-001.md`.

Command: `php tests/Yii2/yii2_otiz_settlement_browser_001_test.php`.
Exit: `255` (browser child exit `1`). Real Chromium reached the protected snapshot,
completed both Yii login steps with the disposable user's valid credential, then:

```text
AssertionError: login returns to the requested snapshot
actual: /pilot/objects
expected: /pilot/otiz/snapshots/301
```

This is missing requested-route return, not setup failure. Runtime uses a new
DML-only account; schema setup and cleanup use the isolated admin fixture. The
fixture database, account, server and credential config are cleaned up even on
RED. Private evidence retained outside git:
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-yii-otiz-browser-e27766173aab`
and `/tmp/fm2-root-browser-red-20260910.log`.

Frozen WIP production hashes used for the run (not a delivery approval):

| File | SHA-256 |
| --- | --- |
| config/yii/web.php | c7cc17bd3789f2a853dab40edb55bf54a5bbd8c467722eafb1d60ce1ea3e2dfe |
| app/YiiRuntime/Controllers/OtizSettlementController.php | 7a23b9e79f72a0986892e4be5b34b86774496f51aca1a17dd4bdf2d7c1c8f175 |
| app/Otiz/MariaDbOtizSettlementView.php | 6b23a97eb21ff49fe9d3477fc72f338115c740c0b9a16acc698959f8f49f30d3 |

Downstream assertions submit actual forms (native clicks, their generated UUIDs
and CSRF), verify return messages/history and exact monetary facts. The independent
literal example is accrued100000, discipline10000, remaining paid90000, reversal
-10000 linked to the unchanged discipline. Exactly3 receipts and4 events must
persist. Accepted snapshot/object rows remain byte-for-byte equal as DB values.
The browser is bounded by per-action and process deadlines; child output uses
files, preventing pipe deadlocks. Screenshots are private diagnostic artifacts.

The HTTP supplement checks the previously approved no-change owner outcome through
its retained redirect/message: a new no-op appends only a `no_change` receipt.
It also verifies retained success/duplicate/error messages. It does not change
money rules or the visible artifact contract.

Required plan was generated/read before writing the browser tests and recomputed
for registration; required categories now include e2e, governance, integration,
unit, plus mandatory full CI. Inventory initially failed only on the expected new
e2e membership; adding explicit membership preserved its historical digest.
`python3 tests/Verification/verification_inventory_001_test.py`: all15 PASS.
