# Authorized attempt clock draft — technical prereview

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Status: **NOT READY FOR GATE 1 LINKAGE**.

This is a read-only prereview of the private candidate. No source, test,
specification, database or external system was changed.

## Exact inputs

```text
4ee1b4d1e5b53ed080b45afc7c3f07b25786ebf24debe16d990ebdd2d7a35387  /Users/antropophag/.local/state/fmonitor2-verification/original-authorized-attempt-clock-spec-draft.md
bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
```

## Coherent bounded behavior

The draft correctly preserves active execution precedence:

```text
shape -> authorization -> authorized terminal lookup -> composition/order lookup
-> one clock instant -> confirmation/date -> stream
```

An authorized terminal miss followed by composition NOT_FOUND can select
`REJECTED/ORDER_NOT_FOUND` without moving authorization or confidential lookup.
A FOUND composition rejected by the existing validator can similarly select
`REJECTED/INVALID_COMPOSITION`. Neither case needs to reopen composition, inspect
the upload or disclose another request.

Because both are valid-shape nonretryable business rejections, the parent section
11 requirement applies verbatim:

> Accepted initial/correction transaction сохраняет ровно один domain event и terminal request result вместе с revision. `REPLAYED` не создаёт event. Valid-shape `REJECTED`/`CONFLICT`, включая unauthorized attempt, атомарно сохраняют terminal request result и safe attempt audit в одной short transaction; failure этой audit transaction заменяет intended outcome на `FAILED/PERSISTENCE_FAILURE`, stored terminal result отсутствует и retry разрешён. Invalid shape до надёжной request/actor identity не пишет DB audit и даёт только best-effort aggregate metric без payload.

The draft's cleanup and lazy clock order is constructible: close the owned unread
stream once, preserve the selected rejection through the existing close/safe-log
isolation rules, then acquire the still-absent clock once and pass that exact
instant to `commitAttempt`. No stage, stream read, inspector, fingerprint,
lineage, ID allocation, accepted commit or delivery is introduced.

Explicit absence for “clock not acquired” is required. Exact
`1970-01-01T00:00:00Z` satisfies the existing canonical UTC-second grammar and
must be persisted as real data. It cannot remain a magic sentinel. A malformed
or throwing lazy clock gives retryable `FAILED/PERSISTENCE_FAILURE`, no terminal/
audit fact and no repeated stream closure.

The draft also correctly leaves denial behavior, denial audit cardinality,
audit-only STREAM/STORAGE persistence and generic audit-failure log names outside
this bounded package. No owner decision is needed for the authorized lazy-clock
behavior itself.

## Blocking ambiguity: terminal-attempt commit outcomes

Draft section 3 currently maps `ROLLED_BACK`, `CONFLICT`, `OUTCOME_UNKNOWN` and
`commitAttempt` Throwable together to `FAILED/PERSISTENCE_FAILURE`. The active
contracts do not justify that collapse.

`ROLLED_BACK` is the only listed outcome that positively confirms the attempted
terminal transaction did not commit. It can therefore implement the parent
section 11 “stored terminal result is absent” condition and map to
`FAILED/PERSISTENCE_FAILURE`.

`OUTCOME_UNKNOWN` does not confirm absence. Returning ordinary persistence
failure immediately would tell the caller that retry may create the terminal
fact even though the first transaction may already have committed. Generic
repository Throwable likewise does not prove rollback unless the repository
port contract expressly guarantees it was normalized only after confirmed
rollback.

Parent section 963 supplies a fresh-lookup recovery rule for repository
`OUTCOME_UNKNOWN`, but its surrounding context is the accepted commit/lease
protocol:

```text
OUTCOME_UNKNOWN -> one fresh findTerminalRequest(requestId):
FOUND resolves stored outcome; reliable NOT_FOUND -> PERSISTENCE_FAILURE;
lookup exception/UNAVAILABLE -> PERSISTENCE_OUTCOME_UNKNOWN.
```

Lifecycle amendment section 6 and its required recovery matrix also require
generic commit Throwable to be treated as an unknown outcome and resolved by one
fresh terminal lookup. Its pure lifecycle matrix demonstrates accepted-commit recovery. Neither paragraph
explicitly states that `commitAttempt(CONFLICT)` uses the same algorithm.

Accordingly, this prereview does **not** claim that the already approved
accepted-commit `CONFLICT` rule automatically mandates a fresh reader for a
terminal rejection transaction. Accepted `CONFLICT` is resolved through
fingerprint/current-lineage rereads; that source-specific rule cannot be copied
to `commitAttempt`.

The authorized-attempt executable contract must now pin, explicitly and without
analogy, what these terminal-attempt outcomes mean:

- confirmed `ROLLED_BACK`;
- `OUTCOME_UNKNOWN`;
- generic Throwable with and without a repository guarantee of confirmed
  rollback;
- `CONFLICT`, including whether it means a request-key race, another uniqueness
  conflict, or a generic failed terminal attempt.

If `OUTCOME_UNKNOWN`/unconfirmed Throwable use the existing fresh terminal
recovery pattern, the exact mapping should be:

- validated FOUND -> stored terminal outcome under normal replay rules;
- reliable NOT_FOUND -> `FAILED/PERSISTENCE_FAILURE`;
- UNAVAILABLE, getter Throwable or malformed stored result ->
  `FAILED/PERSISTENCE_OUTCOME_UNKNOWN`;
- no second clock, commitAttempt, stream operation or mutation retry.

`CONFLICT` needs its own exact rule. A typed request-race variant plus fresh
lookup is technically coherent, but it is not yet active authority. Alternatively
the repository contract may narrow `CONFLICT` to a proven noncommitted category;
that guarantee and its zero-fact evidence must be normative. Raw SQL error-code
guessing is insufficient.

## Dependency on data validity and fresh-reader ownership

Any fresh recovery requires a separately approved reader/data-integrity contract.
The current repository rehydration does not fully validate status/reason/retry/
evidence combinations or prove that accepted terminal evidence is backed by the
required root/revision/event facts. A clock slice cannot safely declare FOUND
recovery until the lookup returns a closed, validated stored result.

The next order should therefore be:

1. approve and implement the bounded fresh terminal reader/result validity
   package from the cumulative data-integrity audit;
2. revise this clock candidate with exact `commitAttempt` result semantics and
   dependency hash;
3. link the revised exact candidate into parent/OpenSpec;
4. obtain independent Gate 1 before RED.

The Gate 2 matrix must distinguish confirmed rollback from unknown acknowledgement
and exercise fresh FOUND/NOT_FOUND/UNAVAILABLE/malformed/getter-Throwable results
for every terminal-attempt outcome that uses recovery. It must prove one clock,
one commit attempt, at most one fresh read, already-closed stream, no blind
mutation retry and exact epoch preservation.

## Deferred owner decision remains separate

Repeated denial audit cardinality and denial against an already accepted request
remain deferred. This prereview neither chooses “every denied invocation” nor
“one denial per request/reason,” changes audit uniqueness, nor adds an audit-only
writer. Authorized ORDER_NOT_FOUND/INVALID_COMPOSITION handling can be specified
after the technical recovery dependency without resolving that product/audit
choice.

## Disposition

The lazy-clock and epoch correction is technically sound, but the private draft
is not ready for Gate 1 linkage until terminal-attempt `CONFLICT`,
`OUTCOME_UNKNOWN` and Throwable semantics are exact and backed by a validated
fresh-reader contract. No implementation or RED should start from the reviewed
draft hash.
