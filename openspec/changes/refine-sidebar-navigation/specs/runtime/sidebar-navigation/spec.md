## ADDED Requirements

### Requirement: Sidebar SHALL expose the approved information architecture

The shared authenticated Yii shell SHALL group permitted links as specified by `YII2-SIDEBAR-NAVIGATION-002` and omit empty groups.

#### Scenario: Full-permission actor sees all groups
- **WHEN** an authenticated full-permission actor opens any shared Yii screen
- **THEN** the sidebar shows `Монтаж`, `Справочники`, `ОТиЗ`, and `Администрирование` in that order with their specified children

### Requirement: Sidebar controls SHALL use coherent accessible icons

Navigation and collapse controls SHALL use pinned `shlz-ui` SVG assets and SHALL keep the collapse affordance visible in both desktop states.

#### Scenario: Collapsed desktop sidebar
- **WHEN** the user collapses the desktop sidebar
- **THEN** a 44×44 or larger right-chevron control named `Развернуть меню` remains visible and keyboard-operable

### Requirement: Feedback SHALL be a floating utility action

The shared shell SHALL render feedback outside the main navigation as one fixed bottom-right accessible action preserving the current path.

#### Scenario: Feedback from a work screen
- **WHEN** an authenticated actor opens a work screen
- **THEN** exactly one floating feedback action links to `/pilot/feedback` with that screen in `from` and does not obscure navigation or primary actions
