# Complete invitation flow delta

## Added requirements

### Requirement: trusted complete invitation URL

Successful create and reissue SHALL present a one-time absolute activation URL
whose origin comes exclusively from configured trusted scheme and host. Request
forwarding headers SHALL NOT influence it. Missing or invalid trusted origin
SHALL produce a safe actionable presentation error without automatic mutation.

#### Scenario: issued link uses trusted origin

- **WHEN** an authorized administrator creates or reissues an invitation while
  request forwarding headers disagree with configured trusted scheme and host
- **THEN** the displayed absolute activation URL uses only the configured origin

#### Scenario: trusted origin is unavailable

- **WHEN** the configured trusted origin is missing or invalid
- **THEN** no relative path is presented as an externally sendable link and no
  automatic retry or reissue occurs; request admission fails before owner mutation

### Requirement: truthful copy and recoverable form

Copy success SHALL be announced only after Clipboard API resolution. Rejection
SHALL retain a selectable manual-copy field without a success claim. Known invite
rejection SHALL retain escaped email/full name, mark and focus the actionable
field, and SHALL NOT invent a duplicate reason. A pending submit SHALL not be
submitted twice.

#### Scenario: clipboard rejects

- **WHEN** the administrator explicitly clicks copy and Clipboard API rejects
- **THEN** the URL field is focused and selected for manual copying and no copy
  success is announced

#### Scenario: invite is rejected

- **WHEN** the existing owner returns a known invalid invitation result
- **THEN** escaped email and full name remain in an accessible actionable form

#### Scenario: invite request is pending

- **WHEN** a submitted invitation request has not completed
- **THEN** a second form submission is prevented without an automatic retry

### Requirement: unchanged identity semantics

TTL, one-time use, activation, reissue rotation, roles, authorization and email
delivery behavior SHALL remain unchanged. Raw tokens SHALL NOT be logged or
persisted beyond existing invitation/session presentation behavior.

#### Scenario: recipient activates in a clean session

- **WHEN** a recipient opens the fixture link in a separate clean browser session
- **THEN** existing one-time activation works without role, TTL, auth or email
  delivery changes
