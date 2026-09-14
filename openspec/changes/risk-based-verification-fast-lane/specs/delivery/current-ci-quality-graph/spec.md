## MODIFIED Requirements

### Requirement: One authoritative verification run
CI SHALL сохранять существующий full/docs/harness routing и добавлять exact-source FAST mode, выбранный только machine-readable планом текущего candidate. Quality Graph SHALL публиковать результаты plan, selected verification nodes и verify/admission, не запуская checks второй раз. FAST SHALL выполнять только selected applicable checks; policy-unselected nodes SHALL иметь отдельное terminal значение, отличимое от failure, cancellation, dependency interruption и unexpected skip. Текущий exact head SHALL иметь один однозначный authoritative admission result.

#### Scenario: Full verification
- **WHEN** STANDARD или CRITICAL code PR проходит успешные plan, fast, четыре категории и verify
- **THEN** текущий полный Quality Graph сохраняет прежние обязательства и VERIFY_OK

#### Scenario: Documentation only
- **WHEN** план выбрал docs-only и fast/verify успешны
- **THEN** четыре категории отображаются policy-unselected для docs-only, остальные обязательные nodes успешны; результат остаётся DOCS_VERIFY_OK, не full proof

#### Scenario: Bounded FAST verification
- **WHEN** exact-source plan доказанно классифицировал candidate FAST и перечислил applicable checks
- **THEN** CI выполняет ровно selected checks и authoritative aggregate без unrelated full integration/e2e categories

#### Scenario: Failed or cancelled category
- **WHEN** selected check failed, cancelled, missing или unexpectedly skipped
- **THEN** authoritative FAST admission ненулевой и не смешивает outcome с policy-unselected

#### Scenario: Minimal F12 selected outcomes
- **WHEN** exact-current-SHA FAST plan перечислил check как selected
- **THEN** success принимается, failure/missing/unexpected skip блокируют admission, а check not-selected-by-policy нейтрален

#### Scenario: Stale selected success
- **WHEN** selected success относится к другому SHA
- **THEN** текущий head не получает FAST GREEN

#### Scenario: Independent job reconstruction
- **WHEN** plan, selected-check execution и admission работают на одном exact HEAD
- **THEN** каждый job самостоятельно находит ровно один conventional input и получает canonical-equivalent FAST contract без plan artifact transport
