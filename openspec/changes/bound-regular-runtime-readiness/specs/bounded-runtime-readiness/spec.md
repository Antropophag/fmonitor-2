## ADDED Requirements

### Requirement: Bounded fail-closed regular readiness

FMonitor SHALL follow `RUNTIME-READINESS-LOAD-001`: full schema compatibility is
checked once in the deployment startup chain, while each HTTP readiness probe
performs only bounded live DB/local checks and validates the exact startup result.

#### Scenario: Current startup then steady probes
- **WHEN** current migrations and startup-check succeed for the exact DB/build
- **THEN** readiness succeeds with a fixed small SQL count and no fingerprints

#### Scenario: Current dependency failure
- **WHEN** DB becomes unavailable or startup evidence is missing/mismatched
- **THEN** readiness returns 503 while liveness remains DB-independent

#### Scenario: Deployment failure
- **WHEN** migration or full compatibility check fails
- **THEN** php is not admitted and no stale success is accepted

#### Scenario: Concurrent probes
- **WHEN** four readiness requests execute concurrently
- **THEN** none initiates a deep schema check or external integration call
