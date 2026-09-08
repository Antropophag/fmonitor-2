## ADDED Requirements

### Requirement: Public native selection and optional PDF
Portal SHALL follow ASSIGNMENT-ORDER-COMPOSITION-HTTP-001 через authorized native
application seams и public shlz-ui, без скрытого prepare и применения состава.

#### Scenario: Fresh selection
- **WHEN** authorized ФКР сохраняет состав
- **THEN** создаётся native identity, GET показывает snapshot, PDF остаётся optional

#### Scenario: Pending replacement
- **WHEN** ФКР заменяет pending состав с exact revision
- **THEN** новый immutable order сохраняется, старый и opening/checklist facts не меняются

#### Scenario: Template download
- **WHEN** ФКР отдельно запрашивает PDF текущего выбранного order
- **THEN** bytes выдаются без file/version storage; native owner сохраняет date/audit

### Requirement: Closed fresh mutation surface
Fresh portal SHALL закрывать predecessor prepare/registration/direct-engineer/open
writers и наследовать exact session/capability/CSRF/security contracts.

#### Scenario: Legacy write in fresh mode
- **WHEN** клиент вызывает прежний POST writer
- **THEN** получает410 без domain mutation
