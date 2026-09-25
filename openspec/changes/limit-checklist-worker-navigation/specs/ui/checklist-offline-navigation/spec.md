# Checklist offline navigation delta

## ADDED Requirements

### Requirement: Offline ownership is limited to checklist documents

The checklist Service Worker SHALL respond to navigation fetches only for the
two canonical checklist document route shapes. It SHALL leave every other pilot
navigation unanswered so that the browser performs its normal network request.

#### Scenario: Ordinary pilot page remains browser-owned

- **WHEN** an installed checklist worker observes navigation to an ordinary
  `/pilot/` page
- **THEN** it does not call `respondWith`

#### Scenario: Either checklist alias remains offline-capable

- **WHEN** an installed checklist worker observes navigation to either canonical
  checklist alias
- **THEN** it controls that request and retains the existing user-bound offline
  document behavior

### Requirement: Queue prefetch remains available

The construction-control queue SHALL continue to register the `/pilot/` scoped
worker and send eligible checklist URLs for prefetching.

#### Scenario: Queue prepares checklist documents

- **WHEN** an authorized user opens the construction-control queue with an
  eligible checklist link
- **THEN** the queue registers the worker with scope `/pilot/` and the linked
  checklist document becomes available in the user-bound document cache

### Requirement: Existing clients receive the corrected worker

The changed worker SHALL activate without requiring users to close existing
tabs, claim open clients, preserve current-generation user-bound checklist
documents and remove stale-generation checklist caches.

#### Scenario: Installed worker upgrades in place

- **WHEN** a browser with a current-generation cached checklist installs the
  changed worker
- **THEN** install requests immediate activation, activation claims existing
  clients, the cached checklist remains available and stale-generation caches
  are removed
