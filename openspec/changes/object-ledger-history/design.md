## Context

См. `proposal.md` — Why и `specs/otiz/object-ledger-history/spec.md`. Действующий snapshot read model уже вычисляет `global_closed_cents` по всем closure rows объекта, тогда как projection `closures` ограничен выбранным snapshot. Изменение должно добавить объясняющее чтение, не менять owner финансовых фактов и не загружать ledger для всех объектов существующего экрана.

Owning module — `app/Otiz`: существующий settlement view/read model владеет SQL-чтением. Yii controller и view только принимают пагинационные параметры, передают projection и безопасно отображают его. Persistence owner не меняется: таблица closure ledger остаётся append-only и читается без schema/migration. `rapid-pilot/` не изменяется и не получает новой логики. Разрешены только существующие Yii/MariaDB зависимости; новая библиотека не нужна.

## Goals / Non-Goals

**Goals:**

- Запрашивать историю лениво только после явного перехода пользователя для одной подтверждённой пары snapshot/object.
- Вернуть одним read-model вызовом страницу, полное количество и полные signed-итоги в стабильном порядке.
- Дать view достаточно структурированных данных для типов, компонентов, источников и reversal relation без финансовой арифметики в HTML.
- Сохранить существующую авторизацию и безопасный return path.

**Non-Goals:**

- Реконструировать ledger на дату старого snapshot или переопределять `closed_before_cents`.
- Добавлять writer, action-кнопки, schema, route верхнего уровня, navigation либо общие CSS/assets.
- Перестраивать snapshot reader, object register или финансовую модель ради общей оптимизации.

## Decisions

### 1. Локальный режим существующего snapshot route

История открывается в существующем route детализации snapshot с явным object context и page parameter (либо локальным fragment-mode того же action). Controller сначала получает авторизованный snapshot projection и подтверждает, что object принадлежит ему, после чего отдельно вызывает object-history read method. Это не создаёт нового глобального route или navigation и сохраняет действующее право/404 поведение.

Альтернатива — вложить историю каждого объекта в основной projection/drawers — отклонена: она читает ledger всех объектов при каждом открытии snapshot и нарушает ограничение задачи.

### 2. Отдельный пагинируемый read-model метод

Settlement view получает метод для одной пары object/snapshot, который выполняет aggregate/count и page query внутри согласованного read-only transaction snapshot. Фильтр всегда находится в SQL по `object_id`; rows упорядочены по дате записи и устойчивому `id` tie-breaker. Page size фиксирован и bounded, page нормализуется или отклоняется одинаково во всех слоях.

Альтернатива — выбрать весь ledger объекта и пагинировать PHP — отклонена из-за памяти, latency и потери server-side pagination. Отдельный summary cache/materialized table отклонён, потому что потребовал бы schema и нового владельца итогов.

### 3. Ledger semantics принадлежат projection, не шаблону

Read model возвращает исходные signed `paid_cents`, `discipline_cents`, `deadline_cents`, вычисленный presentation-kind из существующей строки, source snapshot metadata, `reverses_payment_closure_id` и разрешённую связь с original row того же объекта. Полный итог равен SQL-сумме трёх signed-компонентов всех строк объекта, как у существующего `global_closed_cents`. View форматирует и экранирует значения, но не пересчитывает финансовый смысл.

Альтернатива — выводить одну net-сумму и угадывать тип по знаку — отклонена: смешанная запись может содержать несколько видов компонентов, а reversal должен сохранять связь и структуру.

### 4. Сохранённое и текущее показываются раздельно

Header истории повторяет идентичность выбранного snapshot/object и сохранённые snapshot amounts только как snapshot facts. Отдельный блок «Учтено сейчас по всем расчётам» использует current aggregate. Текст прямо сообщает, что список текущий и не реконструирует прошлый cutoff.

Альтернатива — датировать ledger по report date и выдавать отфильтрованный результат за прошлый срез — отклонена: контракт не гарантирует исторический cutoff и такая реконструкция была бы недостоверной.

### 5. Проверка через публичный HTTP/browser seam

Root-авторизированные tests создают отдельный префикс/БД fixtures: A payment, B deductions, C reversal, соседний object, XSS basis и больше одной страницы. HTTP проверки используют действующую session/auth fixture; before/after inventory подтверждает отсутствие записей. Browser smoke выполняется на disposable Compose project/ports/volumes и проверяет desktop/narrow layout без рабочего стенда.

Архитектурная проверка должна подтвердить отсутствие новых global routes/navigation/CSS и writer dependencies. Если изменится `app/PilotHttp/*.php`, дополнительно обязателен `pilot_http_auth_001_global_calls_test.php`; предпочтительный Yii path не требует такого касания.

## Risks / Trade-offs

- [Aggregate и page могут разойтись при конкурентной записи] → читать count/sums/page в одной consistent read transaction и тестировать контролируемую concurrent insert boundary.
- [Связь reversal может указать чужой object] → join и link разрешать только при совпадающем object id; неконсистентную связь показывать безопасно без раскрытия чужой строки.
- [Исходный snapshot может стать недоступен] → строить ссылку только через существующий авторизованный route; отсутствие доступа не заменять утечкой metadata.
- [Большой offset деградирует] → bounded page size и существующие индексы; новые индексы/schema исключены, измеримый bottleneck остаётся отдельной задачей.
- [Общие test registries могут пересечься с #241/#30/#243] → добавлять только минимальную строку без сортировки и регенерации соседних записей, интеграцию делать после fresh-base проверки.

## Migration Plan

Schema/data migration отсутствует. После focused checks, независимых reviews и exact-source CI код публикуется обычным application deployment. Rollback — возврат application commit; ledger и snapshots не изменяются. Merge и deployment в этом поручении запрещены.
