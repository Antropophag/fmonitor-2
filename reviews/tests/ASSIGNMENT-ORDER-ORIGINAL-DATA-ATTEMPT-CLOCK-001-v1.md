# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-ATTEMPT-CLOCK-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed test/source commit: `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `829476785531792f4da854050582e974bd4c44a9972d6184a3355e0f77743d05`
- Verdict: **APPROVED**

The reviewer authored neither test nor future implementation.

The 50-case public application test fixes lazy clock ownership for authorized
early ORDER_NOT_FOUND and INVALID_COMPOSITION outcomes without moving a
confidential read before authorization. It requires unread-stream closure first,
one canonical or epoch clock call, then one terminal attempt. Invalid/throwing
clock produces persistence failure with no invented timestamp or attempt.

Exact epoch is correctly treated as real data across confirmation, future date,
invalid PDF, stale correction, accepted result and terminal replay. Replay uses
the stored outcome before the new payload/date/clock, closes the supplied unread
stream once, and changes no evidence.

Attempt COMMITTED, confirmed ROLLED_BACK, CONFLICT, OUTCOME_UNKNOWN and thrown
acknowledgement paths are independently distinguished. Ambiguous/conflict attempt
uses one fresh reader, performs no blind attempt retry, snapshots the selected
winner, closes once and does not deliver a newly accepted fact. Close failure
preserves the recovered terminal result and emits the exact authorized-attempt
diagnostic. A stream-close diagnostic failure cannot suppress lazy clock or
terminal audit.

The deferred denial control is correctly negative: invalid shape, DENIED,
authorization unavailable/Throwable, terminal unavailable/replay and composition
unavailable acquire no new speculative clock or attempt. The test therefore does
not approve or invent the unresolved denial-audit policy.

Eight positive controls prevent clock/audit/fresh-reader reject-all behavior.
The RED contains 42 intended failures, eight controls and no setup/type/argument
failure. Real MariaDB atomicity, fresh connection identity and production wiring
remain separate required evidence.

Exact shared inputs:

```text
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
98b9e080f7dbd44bcd7b2f40d905a3aa9842ff807abe79dbeb78012884c15780  tests/Support/AssignmentOrderOriginalIntegrityResources.php
caaeeee7e756b70bfe0bed939858d3a46f6cacf5489b1639a86f5c40687f5907  tests/Support/AssignmentOrderOriginalIntegrityValues.php
dfbf0849e34ea941477b238049b29a7e3cdf3566b8bd8b3fdb9931ac14ee551e  tests/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php
41c3bba37d5ef5604b68f9e7c24c8540bf89c2ef54b57ead18440df7b3b7f313  docs/operations/original-data-integrity-public-red-v1-2026-09-06.md
6d3b170f2964b66746c3f0cd3230b9a78f4a81678edfe6618740839b3b33c0c3  private aggregate evidence.json
```

No changes required. Gate 4 may implement only this approved attempt-clock behavior.
