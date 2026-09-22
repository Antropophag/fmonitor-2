## Context

См. `proposal.md`. На baseline PR #226 проекция карточки материализует `order` только для принятого оригинала/применения и тем самым выбрасывает последний selection без оригинала. Шаблон использует `order === null` как одновременно признак отсутствия состава, документов и следующего действия. Это объединяет разные факты и после открытия рискует подменить применённую бригаду более новым selection.

Существующая страница selection уже читает последний состав и знает `orderId`, installers и наличие accepted original. Existing completion projection уже определяет `Монтажные работы`, `Документарное закрытие`, `Работы завершены` и следующий недостающий completion fact.

## Goals / Non-Goals

**Goals:**

- Добавить явный read-model ожидающего состава, не меняя семантику `order`, `confirmedOriginal` и `opened`.
- Свести выбор главного действия в детерминированную presentation-модель, основанную на status/facts/capabilities.
- Сохранить применённую бригаду главным составом открытого дела и показать новый selection только как ожидающий.
- Проверить regressions PR #226 и неизменность construction-control consumer.

**Non-Goals:**

- Новые статусы, команды, permissions, DML, migrations или общий workflow engine.
- Изменение completion owners/forms, readiness/opening rules либо queue projections.
- Изменение layout, shared styles, ОТиЗ, #171 или переносов сроков.

## Decisions

### 1. Отдельное поле `pendingComposition`

`InstallationProcess` read projection добавит nullable presentation field `pendingComposition` из последнего selection: identity/order/version, installers, engineer и признак отсутствия принятого оригинала. Оно не будет присваиваться `order` и не станет источником `confirmedOriginal`.

Для неоткрытого дела без оригинала поле даёт карточке видимый сохранённый состав. Для открытого дела с новой selection `order` остаётся применённым snapshot, а `pendingComposition` показывает только ожидаемую замену. Если latest selection уже соответствует принятому/применённому основанию, отдельный ожидающий блок не нужен.

Альтернатива — заполнить существующий `order` selection-данными. Она отвергнута: `order` сейчас означает основание с реальным original artifact, и такое заполнение заставило бы вкладку документов изображать отсутствующий PDF существующим и подменяло бы applied crew.

### 2. Переиспользование selection read semantics

Чтение members/engineer/order identity будет следовать существующему механизму selection page, с теми же current-selection и integrity assumptions. Не создаётся вторая доменная модель или новый query endpoint; допустимо вынести общий read helper в `InstallationProcess`, если это уменьшает расхождение между двумя экранами.

Альтернатива — читать DOM/route страницы selection либо дублировать её presentation array в controller. Она отвергнута из-за расхождения integrity и смешения SQL с HTTP controller.

### 3. Явная матрица presentation action

Проекция/контроллер передадут шаблону необходимые независимые facts (`pendingComposition`, current status, confirmed original, opened, completion facts) и capability booleans, включая существующий `canUpload`. Шаблон выберет единственный action в следующем порядке:

1. `Требуется изменение` — informative state, без обычного checklist CTA.
2. `Работы завершены` — completion state, без continue CTA.
3. `Документарное закрытие` — anchor к существующей форме недостающего ПТО или декларации при соответствующем grant; иначе informative state.
4. Открытые монтажные работы — checklist link при read grant; иначе informative state.
5. Принятый оригинал до открытия — existing open form при `installation.open`; иначе readiness state.
6. Ожидающий selection без оригинала — upload link при `assignment_order.original.upload`; иначе waiting state.
7. Нет состава — selection link при existing select grant; иначе informative state.

Такой порядок защищает terminal/exceptional stages от маскировки более общими условиями. Completion anchor использует существующий `#completion` и существующие forms; новых POST routes нет.

### 4. Документы и команда основаны на разных сущностях

В «Команде» applied `order.installers` показывается как действующая бригада, а `pendingComposition.installers` — как ожидающий состав с явной подписью. В «Документах» строки signed original строятся только из `order.artifacts`/`confirmedOriginal`; pending selection даёт лишь waiting copy и допустимую upload link.

### 5. Владение, зависимости и архитектура

Owning module read-model — `InstallationProcess`; Yii controller только получает capability/read access и передаёт данные, Yii view рендерит presentation. Persistence owners selection/original/opening/completion не меняются. `rapid-pilot` остаётся неизменяемым oracle/adapter. Новых зависимостей и architecture exceptions не требуется; bounded architecture check должен подтвердить отсутствие SQL/DML в view и новых runtime связей.

## Risks / Trade-offs

- [Latest selection и applied order относятся к разным распоряжениям] → хранить и рендерить их независимыми полями, покрыть открытое дело с pending replacement.
- [Шаблон снова свяжет документы с наличием состава] → тестировать отсутствие artifact/date/history до принятия original при видимом составе.
- [Главное действие перескочит terminal/change-needed stage] → проверять приоритет полной матрицей status/action и negative assertions на checklist CTA.
- [Права UI разойдутся с сервером] → передавать exact booleans из существующих access seams; серверные owners остаются финальной проверкой.
- [Регрессия #226 из-за правок крупного шаблона] → отдельные focused assertions/browser flow для modal editor и history cursor.
- [Тесты заденут параллельные данные] → уникальные prefix/database/session/storage paths и teardown только task-owned ресурсов.

## Migration Plan

Миграция данных и schema не требуется. Candidate создаётся от merged PR #226, проходит focused tests, независимые planner-required reviews и один exact-source CI run. Rollback — откат read-model/view/controller commit; persisted facts отсутствуют.
