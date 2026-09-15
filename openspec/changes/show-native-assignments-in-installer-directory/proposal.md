# Change: show native assignments in installer directory

## Why

Issue #38 reproduces after #40: the native application workflow records the current
crew in append-only assignment applications, while the installer directory still
reads the superseded registered-order projection. Applied assignments are therefore
missing from the directory.

## What changes

- Read current installer assignments from the latest native application per case.
- Preserve immutable application history and use legacy registered orders only for
  cases that have never entered the native application workflow.
- Keep explicit free-installer state and align summary/availability filters.

## Capabilities

- `runtime/yii2-installer-directory`: current assignment projection follows the
  native application owner while preserving pre-application compatibility.

## What does not change

No directory redesign (#19), engineer management (#52), checklist/offline (#131),
Bitrix (#15), OTIZ (#66), new assignment writer, or rapid-pilot/legacy truth owner.

## Contract

`specs/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md`.
