## ADDED Requirements

### Requirement: Operator original submission
Portal SHALL follow ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 through approved selected
original application, with explicit CSRF/capability and bounded binary transport.

#### Scenario: Direct original
- **WHEN** ФКР выбрал состав и отправил signed PDF без шаблона
- **THEN** original accepted with documentDate and uploadTime separately; no composition application/opening

#### Scenario: Correction
- **WHEN** ФКР отправляет новый PDF/date с exact leaf и причиной
- **THEN** native revision дополняет историю; предыдущий original не переписывается

### Requirement: Honest upload UI
UI SHALL preserve retry identity until intent changes and use last template date
only as initial suggestion for the same order.

#### Scenario: Template prefill
- **WHEN** selected order имеет successful generation date
- **THEN** form предлагает её для подтверждения по original; template не обязателен
