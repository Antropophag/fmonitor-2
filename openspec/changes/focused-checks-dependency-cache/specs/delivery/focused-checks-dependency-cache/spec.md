## ADDED Requirements

### Requirement: source-only rebuild preserves dependency cache

The existing focused-check image build SHALL bind the final image to the exact
executable source while keeping unchanged OS packages, PHP extensions, Composer
dependencies and uv dependencies reusable when only executable source changes.

#### Scenario: source A changes to source B

- **WHEN** the public focused route builds A and then B with identical recipe,
  runtime pins and lockfiles but distinct honest executable-source digests
- **THEN** B's dependency-installation steps are served from BuildKit cache
- **AND** the executed image and source label identify B, never A

### Requirement: dependency inputs remain cache keys

The correction MUST NOT exclude dependency inputs from their owning layers.

#### Scenario: lock input changes

- **WHEN** a disposable fixture changes a canonical lockfile while source and
  runtime pins remain controlled
- **THEN** its corresponding dependency layer is invalidated and rebuilt
- **AND** a stale dependency image is not accepted

### Requirement: cold and shared-profile compatibility

The same recipe SHALL remain valid without a pre-existing cache and SHALL retain
the common-stage contract used by governance, integration and browser profiles.

#### Scenario: no matching cache exists

- **WHEN** the image is built with a fresh isolated BuildKit cache
- **THEN** dependencies install successfully and identity checks still pass

### Requirement: bounded evidence

Verification SHALL include a cheap structural guard and a bounded real Docker
A/B witness. External wall time SHALL be measured around the whole public
command and SHALL NOT change the `RUN_IN_PROFILE_RESULT` schema.

#### Scenario: delivery evidence is reported

- **WHEN** the candidate is prepared for review
- **THEN** evidence names platform, Docker/BuildKit versions, A/B source
  identities, cache decisions, image IDs and external wall times
- **AND** a single-machine sample is not presented as a stable percentage claim
