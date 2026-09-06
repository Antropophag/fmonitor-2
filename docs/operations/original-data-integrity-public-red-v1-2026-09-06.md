# DATA-INTEGRITY public/adapter-scalar RED v1 — 2026-09-06

Exact test/source HEAD: `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`.
No data-integrity production implementation has begun. The PDF implementation
remains the independently approved e9aca37 correction.

Normative DATA-INTEGRITY-001 v0.6 SHA256:
`c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`.
Parent v71 SHA256:
`4f5be0695a95fc4d912261647579ad4fb214df5dd0da90edeb6f38fe11f6b3fc`.
Independent Gate1 v06 APPROVED review SHA256:
`ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`.
Earlier rejected/clarified versions remain immutable; no old approval is applied
to a changed target-order or conflict-classification clause.

## Authoritative capture

Private archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-data-integrity-public-red-7rfr5tyq`.
Final evidence.json SHA256:
`6d3b170f2964b66746c3f0cd3230b9a78f4a81678edfe6618740839b3b33c0c3`.
HEAD and afterHead match. The manifest pins all Original production PHP files,
the six new scripts, five independent helpers and both normative specifications.
Every script runs with explicit `php -d memory_limit=256M` and exits255 through
its collected TestFailure, with no skipped case or accepted failure allowance.

| New script suffix | Cases | Intended failures | Passing controls | Log |
| --- | ---: | ---: | ---: | --- |
| data_attempt_clock | 50 | 42 | 8 | 00.log |
| data_commit_scalars | 134 | 129 | 5 | 01.log |
| data_composition | 76 | 62 | 14 | 02.log |
| data_lineage | 86 | 70 | 16 | 03.log |
| data_recovery | 50 | 47 | 3 | 04.log |
| data_values | 133 | 84 | 49 | 05.log |
| Total | 529 | 434 | 95 | |

All11 new PHP files lint clean. Captured logs contain no undefined symbols,
PHP warnings, invalid declarations, ArgumentCountError or TypeError/setup abort.
Missing new public interfaces/factory declarations are explicitly asserted as
INTENDED_RED acceptance failures, while foreign old-compatible values allow the
remaining behavioral cases to exercise the actual current public application.

## Fixture independence and scope

The helpers own literal canonical request/order/actor/revision/PDF values and
pure recording ports; they do not import the older Initial/Dynamic/Lifecycle
fixtures. The canonical PDF is a fixed literal327-byte document with SHA256
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
The composition JSON/hash is pinned by the normative literal; the test-only
encoder follows that mathematical format without calling production hashing.
The SQL scalar fixture counts/denies public mysqli methods and proves no calls
before invalid DTO rejection. Its valid controls must reach the sentinel so
reject-all cannot pass. Real MariaDB atomicity/snapshot/rehydration/fresh-reader
proof is a separate required test part, not replaced by this fake.

New optional interfaces use test-only conditional marker interfaces: when the
real API exists they extend it; otherwise they extend existing base interfaces
or remain empty. No production symbol is defined or aliased by tests. Explicit
API assertions fail when real declarations are absent. The old userland
Dependencies constructor ignores its extra positional factory argument; after
implementation that same position is the real typed trailing dependency. Exact
reflection and open/read/close counts prevent the compatibility fixture from
passing with the dependency still absent or ordinary writer rereads retained.

Accepted/attempt call logs are separate from confirmed fake facts. Failed or
ambiguous writes do not automatically become fake durable rows. Fresh values
may independently model a competing terminal winner; tests assert no second
write, no ordinary repository reread, and once-only close/release/delivery.
Pure storage identity remains valid synthetic `private-content-0001`, as the
approved lifecycle contract permits; only real MariaDB fixtures bind digest IDs.

## Preliminary author diagnostics retained

Earlier private `original-data-*-author-diagnostic.log` files were drafting aids,
not Gate2 evidence. The first values draft used a different generated327-byte
PDF than the fixed example; the mismatch was identified by its literal hash
control and replaced with the correct literal before this capture. Initial
lineage drafting followed the old post-finalize implementation; independent
reviews clarified parent step11 and the fixed v06 tests now require pre-finalize
normal validation and only reachable post-CAS states. Those preliminary failures
are not counted as authoritative RED and no historical log was overwritten.

Independent Gate3 is still required for each artifact. The real database,
production fresh-factory/worker proofs and separately reviewed old-fixture patches
remain required before minimal implementation and cumulative Gate5. No combined
command, launch or VERIFY_OK claim follows from this record.
