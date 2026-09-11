## ADDED Requirements

### Requirement: Yii2 SHALL own production jobs console transport

Production worker, scheduler and jobs health invocations SHALL enter through the shared Yii2 console application and SHALL delegate job semantics to the existing `app/Jobs` application owner without loading `rapid-pilot`.

#### Scenario: worker and scheduler restart safely

- **WHEN** production compose starts or restarts worker and scheduler with valid runtime configuration
- **THEN** both processes remain healthy, preserve durable facts and do not duplicate an already recorded schedule slot

#### Scenario: configuration is rejected closed

- **WHEN** required runtime or worker configuration is missing or invalid
- **THEN** the command returns the established non-zero exit and sanitized JSON without leaking secrets or creating job facts

#### Scenario: process receives termination

- **WHEN** the worker or scheduler receives the configured termination signal
- **THEN** the existing bounded shutdown and lease behavior is preserved
