# Delta: verification FAST classification

## ADDED Requirements

### Requirement: mechanically proven server-rendered presentation

The planner SHALL implement `specs/FAST-SERVER-RENDERED-PRESENTATION-001.md`
without weakening existing sensitive or semantic escalation.

#### Scenario: closed presentation owner with public oracle
- **WHEN** all effective owners are registered server-rendered presentation or its existing CSS companion
- **THEN** the plan is FAST and explains the class, reason, oracle and negative checks

#### Scenario: mixed or unproved owner
- **WHEN** any effective owner is sensitive, semantic, policy, runtime, product/spec or unknown
- **THEN** the plan is not FAST or fails closed
