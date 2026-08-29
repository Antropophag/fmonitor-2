# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh independent re-review
- Reviewer: separately tasked agent `/root/bootstrap_test_rereview`
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `81339d8a43150a047aa1e62c3a2ef4bcb5f2371f`
- Specification commit: `71e5e5066a26b3a7d9e5d2ee08b27f14e205ed94`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]` process, its printed loopback URL, and browser-shaped HTTP requests
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

This record supersedes the prior Gate 3 rejection for test commit `562f36455e52c3de3134ec9900f7ab528a8b8b03`. The new test was reviewed independently against the approved specification rather than against a planned bootstrap implementation.

## Findings

1. **Wrong/missing shlz CSS sensitivity remains absent.** Sections 7 and 8 require startup failure before the banner when the sibling built CSS is missing or incompatible. The new success-path byte equality at line 146 proves only that a correctly configured server serves the current file. The test never makes the source missing, unreadable, wrong, or otherwise incompatible before `start`; a bootstrap that ignores the sibling asset while serving copied or fabricated bytes can still pass. Add an isolated adversarial probe that changes only the CSS availability/identity observed by the public CLI, expects nonzero/redacted/no-banner failure, and restores the shared dependency unconditionally without changing repository bytes.

2. **Foreign marker/prefix conflict sensitivity remains absent.** Section 8 separately requires `foreign marker/prefix`; section 3 says a prefix may never be reused without its matching marker. Lines 179–180 create an unrelated `fm2d_deadbeef_g99_foreign` table and unrelated directory outside any generation. That covers cleanup containment, but it does not place a foreign/mismatched owner marker or a conflicting table under the exact next generation prefix and prove fail-closed provisioning. A bootstrap that accepts a foreign marker or reuses occupied `fm2d_<this fingerprint>_gN_` rows can pass. Add public-CLI cases for both mismatched ownership marker and exact candidate-prefix collision, with preservation oracles.

3. **The production composition boundary is still not independently protected.** The prior finding required sensitivity to replacing production `InstallationProcess`, `AssignmentOrderArtifactService`, migrations/importer, or real storage with a self-contained fake bootstrap/HTTP state machine. Lines 145 and 150 only reject a response literal and inspect table names/counts; a fake implementation can create those eleven shaped tables and satisfy the browser journey without composing the required production services. The test does not run inherited production composition/E2E suites, pin and probe the production entrypoint/dependencies, or otherwise make such substitution fail. Add a composition oracle at public production seams (or explicitly run the relevant inherited approved tests as part of this Gate 2 test) without deriving business expected values from production internals.

4. **The claimed public-seam/isolation boundary is inaccurate and overbroad.** The header says SQL only creates/removes the database and business facts are exclusively observed through HTTP, while lines 150, 174 and 177 query every process table, assert the process-event count, snapshot every row, and compare those rows after reset. Section 8 permits bootstrap-owned table-catalog inspection for isolation, not arbitrary process-fact observation. Limit SQL to catalog/ownership containment facts needed to prove generation isolation, and prove old-generation recoverability through the public `start`/HTTP seam (or narrow the specification before retaining row-level SQL). This also avoids coupling the test to incidental row representation and all future production tables.

## Resolved since the prior review

- Unknown, repeated, and extra CLI arguments plus canonical port validation are now covered.
- Occupied-port startup, no-banner behavior, and redaction are covered.
- Launch smoke now checks stylesheet links, card state/prepare link, enabled submit, and repeated queue GET stability.
- Spoofed identity headers, non-imported object `4999`, immediate/restart artifact immutability, and restart persistence are covered.
- Running-server reset/cleanup refusal, interrupted inactive generation numbering, previous-generation preservation, and cleanup preservation of unrelated path/table sentinels are covered.

## RED evidence

Commands run from repository root:

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: extra CLI argument exact redacted rejection
Expected: [64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ""]
Actual:   [1, "", "Could not open input file: bin/fmonitor2-pilot-demo.php\n"]
```

Exit status: `255`.

The RED is deterministic and reaches the first executable expectation. Its cause is the absent approved public CLI file, not a PHP syntax, MariaDB, fixture, or HTTP setup failure. The exact first assertion does not yet distinguish missing CLI behavior from a missing file, but both are within the wholly absent Gate 4 bootstrap seam; after the file exists, all adversarial cases must remain independently sensitive.

## Required changes

Add the four missing protections above and request another fresh Gate 3 review. Gate 4 must not begin on test commit `81339d8a43150a047aa1e62c3a2ef4bcb5f2371f`.

## Reviewed artifact manifest

```text
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
f956fb98f20fd0c61b196d48b1ff24d6cd413eed4bcf637dca5b4464a5746ca9  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Any change to the specification, executable test, test bootstrap, or relevant support-oracle set invalidates this verdict and requires a fresh independent review.
