# Completion form recovery

## ADDED Requirements

### Requirement: rejected completion input remains correctable

The Yii application SHALL render recognized validation and domain rejections in
the submitted completion form on the same object card, preserve that form's
values, expose accessible errors, and retain the original non-success status.

#### Scenario: invalid correction reason

- **WHEN** an authorized user submits a completion correction with an invalid reason
- **THEN** the response is 422 with the matching correction form open
- **AND** its date, details and reason remain in that form only
- **AND** the reason error is accessible and focusable

### Requirement: unconfirmed browser results are not reported as success

The completion client SHALL prevent simultaneous duplicate submits of one form
and SHALL preserve its DOM values when a request outcome cannot be confirmed.

#### Scenario: network response is unknown

- **WHEN** the completion POST rejects at the network boundary
- **THEN** no automatic repeat occurs and the entered values remain
- **AND** the user is told to inspect current documents before retrying
- **AND** the same form becomes editable and submittable again
