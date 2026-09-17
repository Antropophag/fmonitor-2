## ADDED Requirements

### Requirement: Obligation placement preserves stronger local reasons
The existing planner SHALL place checks selected only by semantic integration closure in exact-source CI while retaining checks with acceptance, changed-test, boundary, or known-consumer reasons in local focused execution.

#### Scenario: Semantic-only integration verifier
- **WHEN** a verifier is selected only by conservative semantic integration closure
- **THEN** the plan marks it CI-only and focused execution does not run it

#### Scenario: Multiple reasons for the same command
- **WHEN** the same argv has both semantic-closure and direct local reasons
- **THEN** it remains local, executes once per level, and its reasons are preserved

### Requirement: Review and CI remain fail closed
Prepared reviewer packages SHALL expose completed local obligations and pending CI obligations without fabricated evidence, and the existing CI aggregate SHALL reject missing, failed, skipped, or cancelled mandatory CI jobs.

#### Scenario: CI-only failure
- **WHEN** local obligations pass but a CI-only consumer fails
- **THEN** local status is not represented as overall success and final CI rejects the candidate
