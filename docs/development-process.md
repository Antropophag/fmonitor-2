# SSD + TDD delivery process

This is the mandatory path from a product decision to merged FMonitor 2.0 code. SSD means Specification-Driven Development.

## Unit of work

Deliver one vertical behavior slice at a time. A slice crosses the real public seam—from an accepted command or user action to its observable result—and is small enough for one red-green cycle. Each slice has a stable specification identifier such as `ORDER-PREPARE-001`.

Before tests are written, record and confirm the public seam. Tests exercise that seam and remain valid when internals are replaced.

## Gate 1: executable specification

Write or amend the normative specification before code. Each behavior must state:

- specification identifier and user/actor;
- preconditions and input;
- command or action;
- observable result and persisted facts;
- rejected cases and exact business reason;
- authorization and audit requirements;
- examples with independently determined expected values.

The gate passes when ambiguities affecting behavior are resolved and every acceptance statement is observable at the confirmed seam.

Approved cross-cutting invariants are inherited by every slice and are not resubmitted to the product owner as separate behavior for each command. A slice specification cites the inherited invariant and asks for a new decision only when it introduces an exception or a user-visible outcome not already covered by the shared contract.

## Gate 2: red test

Write the smallest test that proves one acceptance statement. Run it before implementation and retain the command and relevant failure output in the test-review record.

The test must:

- cite its specification identifier;
- use a public seam rather than private methods or database side channels;
- derive expected values from the specification or a worked example;
- fail for the missing behavior, not for broken setup;
- be deterministic and isolated from production systems.

The gate passes only when the test is demonstrably red for the intended reason.

## Gate 3: independent test review

A reviewer other than the test author reviews the specification and test without relying on planned implementation details. Record the review in `reviews/tests/<spec-id>.md` using the template in that directory.

The reviewer checks traceability, seam choice, sensitivity, expected-value independence, rejected cases, determinism, and the captured red result. `APPROVED` advances the slice. `CHANGES_REQUESTED` returns it to Gate 1 or 2.

## Gate 4: minimal implementation

Write only enough production code to make the independently reviewed test pass. Run the focused test after each change, then the relevant suite. Record the commands and results for code review. Refactoring beyond the slice waits for review or a separately specified slice.

The gate passes when the reviewed test and relevant regression suite are green with no changes to the approved expectation.

## Gate 5: independent code review

A reviewer other than the implementation author reviews the specification, approved tests, production diff, and verification output. Record the review in `reviews/code/<spec-id>.md`.

The reviewer checks specification conformance, invariant enforcement at every entry point, audit/history behavior, security, integration boundaries, maintainability, and whether the test would catch a plausible regression. Test changes discovered here restart at Gate 2 and require a new independent test approval.

The slice is complete only with an `APPROVED` code review and green relevant tests. A review record names the reviewer, reviewed commit, verdict, findings, and verification evidence; approval cannot be inferred from silence.

## Independence

An independent review is performed by a different human or separately tasked agent that did not author the reviewed artifact. The reviewer receives the normative specification and the artifact under review, forms findings independently, and records a verdict. Self-review and a second pass by the same author are useful preparation but do not satisfy either review gate.
