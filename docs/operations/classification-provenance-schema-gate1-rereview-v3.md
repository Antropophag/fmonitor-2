# CLASSIFICATION-PROVENANCE-SCHEMA-001 — independent Gate 1 rereview v3

Date: 2026-09-02  
Reviewer: separately tasked independent agent `/root/grill009_session_update`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests or production code. This append-only record is the
only review change.

## Exact reviewed hashes

```text
a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
cf5f382a0e2ccac75bebbb65cbfe0f92a8be59d5f6c336e5c2a810b3762103d3  openspec/changes/canonicalize-classification-provenance-schema/proposal.md
4c427f1b820a1d1e1ceefb52475a06e7da3cfba5ab063653feb7802973ab1aae  openspec/changes/canonicalize-classification-provenance-schema/design.md
d2f0458ff9e2fd398c1904c8fa8955ad1cfc13b0b933db7a64faa2a0ee4485e7  openspec/changes/canonicalize-classification-provenance-schema/specs/persistence/classification-provenance-schema/spec.md
b5e1781c16f7967d3886485bcbfedb75168fa2bc65bc718cbc5d5ce6538acfc4  openspec/changes/canonicalize-classification-provenance-schema/tasks.md
```

## Blocking finding — executable contract was not amended

The OpenSpec proposal, design and delta spec coherently introduce an injected
verifier-only coordinator barrier. They place each verifier-composed real
subprocess at the barrier only after its semantic preflight has observed the
v11 target absent and immediately before its plain `CREATE TABLE`. They also
make production composition no-op and inaccessible through CLI argv,
environment or supported configuration, and explicitly forbid `GET_LOCK`,
`SLEEP`, durable/ephemeral ledger and other hidden serialization.

However, `specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md` is byte-identical to
the pre-GRILL-009 owner-approved executable spec. Its section 4 still says that
“Two public runners” in the bounded race MUST produce exactly one winner and a
loser with exit 70 after losing `CREATE`; it contains no injected barrier, no
arrival/release timing, no verifier-only composition boundary, no production
no-op/inaccessibility rule and no explicit prohibition of lock/sleep/ledger/
serialization.

That omission is normative, not editorial. Under ordinary production
scheduling the second runner may start or complete preflight after the first
runner has created the exact table. It must then be the already-approved
ordinary exact repeat with exit 0 and `appliedVersions=[]`, not a loser-after-
`CREATE`. Only the amended verifier composition can force both processes to
observe absent before either executes plain `CREATE`. The current executable
spec therefore remains nondeterministic and contradicts the amended OpenSpec
package's separation of:

- ordinary production repeat: exact table observed, exit 0, empty applied list;
- verifier-controlled race loser: absent observed, barrier arrival recorded,
  simultaneous release, plain `CREATE` loses, exit 70 exact failure.

Task 1.2 correctly remains open, confirming that the executable amendment and
fresh Gate 1 acceptance are unfinished.

## Gate reset and prohibited mechanisms

The planning package correctly states that the former Gate 1 hash and Gate 3
approval do not carry forward. Tasks 2.1 and 2.2 require a replacement RED and
fresh independent test review only after approval of the amended exact hash.
The package also consistently rejects production `GET_LOCK`, `SLEEP`, a
migration ledger and hidden serialization. These points are acceptable, but
cannot compensate for their absence from the executable contract that Gate 2
must implement and verify.

## Required correction

Amend executable section 4 so that it:

1. limits the exact winner/loser outcome to two verifier-composed real
   subprocesses sharing the injected coordinator;
2. fixes barrier timing after each process has completed absent-v11 semantic
   preflight and immediately before plain `CREATE TABLE`, with both arrivals
   observed before simultaneous release;
3. states that production CLI/catalogue/factory always use no-op and provide no
   argv/environment/config activation path;
4. explicitly prohibits production `GET_LOCK`, `SLEEP`, durable or ephemeral
   ledger and any hidden serialization;
5. preserves ordinary exact repeat exit 0/empty applied versions when a runner
   observes the already exact table.

Then update the executable version/hash, reconcile any resulting artifact hash
changes and request another fresh independent Gate 1 review. Do not update RED
or production under the current package.

## Verification

```text
openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

git diff --check -- <reviewed executable spec and OpenSpec package>
exit 0, empty output
```

Structural validation and diff hygiene pass, but they do not close the
normative executable-spec mismatch. Verdict: **CHANGES_REQUIRED**.
