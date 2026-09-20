## Context

См. `proposal.md` и capability spec. Сейчас глобальный Yii handler пишет только event/status, внешний bootstrap catch не пишет причину, а три controller boundary возвращают 503 до глобального handler. В проекте уже есть строгий safe-log owner для доменного журнала; его правила allowlist, append-only handle ownership и подавления деталей служат шаблоном, но доменный журнал распоряжений не является владельцем runtime failures.

## Goals / Non-Goals

**Goals:**

- Один небольшой runtime-owned механизм создаёт ID, allowlisted record и заголовок для пяти разрешённых entry points.
- Реальные HTTP и journal effects доступны для изолированной fault injection.
- Bootstrap fallback не требует Yii и БД; Yii path использует штатный logging sink, если это возможно безопасно.
- Категория поступает как enum/явный аргумент от boundary либо из закрытого map известных exception classes/codes.

**Non-Goals:**

- Унификация всех `catch`, tracing, metrics, dashboards или внешняя доставка логов.
- Изменение domain/application seams, schema, retries, release configuration или debug mode.
- Повторное использование correlation ID доменного original safe log: у него иной owner и назначение.

## Decisions

### 1. Runtime-owned deep module с узким API

Добавить небольшой framework-independent модуль в существующей runtime-boundary `app/Runtime` с операцией вида `report(Throwable, component, categoryHint): errorId`, которая сама генерирует криптографически случайный фиксированно закодированный ID, строит запись только из allowlist и поглощает сбой sink. Controller/handler/bootstrap передают только enum component и явный category hint; request и exception message не передаются в сериализуемые поля.

Альтернатива — вставить `Yii::error` в каждый catch — отвергнута из-за дублирования, невозможности bootstrap до Yii и риска расхождения privacy policy.

### 2. Один emission owner на каждом выбранном пути

Controller catch создаёт запись и маркирует response заголовком; обработанное исключение дальше не выбрасывается. Необработанный Yii failure записывает `SafeErrorHandler::logException`, сохраняет ID внутри handler на время render и после `removeAll()` восстанавливает header. Внешний bootstrap catch владеет только сбоями до/вне Yii. Так один failure имеет одного owner без глобального dedup registry.

### 3. Закрытые component/category values

Component — константа из набора `bootstrap`, `yii_error_handler`, `execution_controller`, `original_controller`, `checklist_controller`. Category — `configuration`, `database`, `dependency`, `storage`, `unexpected`; известные infrastructure exception types/codes проверяются через `instanceof`/stable code, а boundary может передать только доказуемый hint. Любой unmatched случай — `unexpected`.

### 4. Sink и build version

Оба пути используют штатный process error sink `error_log()` в одном структурированном JSON-line формате; контейнер уже направляет его в основной runtime journal, и он не требует Yii или БД. Sink вызывается внутри неперехватываемого наружу guard. Build version читается только из существующего trusted server-side revision environment/config source, если он есть на актуальном main; иначе literal `unknown`. Новых manifest/release inputs нет.

### 5. HTTP header

Использовать фиксированный `X-FMonitor-Error-ID` с безопасным ASCII token. Header добавляется только к выбранным 5xx. `SafeErrorHandler` после очистки заново устанавливает его перед `send`; bootstrap устанавливает после `header_remove`. Никакие JSON schemas не расширяются.

### 6. Verification ownership

Root создаёт normative spec и container-focused executable test с синтетическими canaries и управляемыми fault seams. Тест проверяет wire response и прочитанные записи, включая logger failure и отсутствие дубля. Разрешены адресная запись в `tools/verification/suites.tsv`/timings и capability ownership существующего container profile; harness/FAST/CI algorithms не меняются. Planner определяет lane и Gates 3/5.

Persistence owner отсутствует: runtime record — append-only operational log, не domain fact и не БД. `rapid-pilot` не меняется. Architecture check должен подтвердить отсутствие запрещённых boundary dependencies; изменение `app/PilotHttp` не планируется.

## Risks / Trade-offs

- [PHP `error_log()` может маршрутизироваться средой по-разному] → тестировать существующий container sink и фиксировать только контракт одной записи, не вводя новый сервис.
- [Header может исчезнуть при response sanitization] → wire-level тест глобального handler после `removeAll()`.
- [Один exception может быть записан дважды] → явное владение emission по boundary и тест точного количества по ID.
- [Слишком широкая классификация раскроет детали или соврёт] → закрытый enum и default `unexpected`; никаких regex/message heuristics.
- [Logger сам бросит/предупредит] → catch-all вокруг sink без повторного логирования и без изменения response path.
- [Build revision недоступна] → `unknown`, без зависимости от #172.

## Migration Plan

Сначала доставить spec/RED/review, затем минимальный модуль и пять integrations. Deployment не требует schema/data migration. Rollback — удалить integrations и общий модуль одним revert; прежние статусы/envelopes остаются совместимыми. Merge/deploy в этом поручении не выполняются.
