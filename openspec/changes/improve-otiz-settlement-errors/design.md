## Context

См. `proposal.md` и capability `otiz/settlement-form-recovery`. Текущий Yii2 controller разбирает только `digits.two-digits`, а любой local validation/domain отказ сворачивает в redirect `?error=closure`; view генерирует новый operation UUID при каждом render. `STALE_CALCULATION` complete-payment уже правильно исходит из существующего `OtizSettlement`, но controller возвращает plain text 409. Финансовые данные и object-wide history #248 принадлежат существующим owners и не меняются.

## Goals / Non-Goals

**Goals:**

- вынести строгий decimal-to-cents parsing и разрешённое состояние формы в малый локальный presentation model/helper, тестируемый независимо от owner;
- сохранить server-first POST/redirect/GET для известных validation/domain refusals и отдельный rendered 409 для stale calculation;
- сохранить operation identity между ошибкой и явной попыткой, различая server-confirmed refusal и unknown transport outcome;
- покрыть public HTTP и real browser desktop/narrow на изолированной disposable fixture.

**Non-Goals:**

- любые изменения `OtizSettlement`, формул, диапазона `1..1000000000000`, persistence/schema, ledger/history reader, snapshot publication, XLSX, ролей или routing;
- общий frontend form framework, общий flash-storage redesign, CSS/navigation redesign, deployment/stand work;
- автоматическая reconciliation, polling или retry финансовой команды.

## Decisions

### 1. Integer-only parser на строковых группах

Локальный parser принимает только заранее перечисленные grammar forms, удаляет только проверенные grouping spaces, разбивает целую/дробную части и строит cents через decimal-string bounds или checked integer arithmetic. Это исключает float и «санитизацию» произвольного ввода. Альтернатива `Number`/`float` отвергнута из-за округления; permissive regex с удалением punctuation — из-за неоднозначности.

### 2. Одноразовое server-side form state с allowlist

При известной validation/domain ошибке controller кладёт в Yii session flash структурированный payload: snapshotId, objectId, исходный operationId, только `discipline`/`basis`/`artifact`, field errors и marker для drawer/focus. Redirect содержит только стабильные ids/error marker. View потребляет flash ровно один раз, сверяет snapshot/object membership и HTML-escapes значения. Альтернатива query string отвергнута из-за утечки основания; повторное чтение POST без redirect — из-за refresh/resubmit и нарушения PRG.

Domain refusal, для которого owner гарантированно вернул ответ без принятия, считается known refusal и может показать form state. Transport exception/разрыв не объявляется отказом и не запускает redirect с утверждением «не сохранено».

### 3. Operation identity принадлежит форме-попытке

Отрисованная форма получает один operationId. Known pre-owner validation сохраняет его вместе с form state; исправленная явная отправка использует тот же id. Полученный domain response обрабатывается согласно существующему replay/conflict contract. JS хранит in-flight/unknown record только для соответствующей form identity и не создаёт UUID при transport uncertainty. Альтернатива «новый UUID при каждом retry» отвергнута как риск повторной финансовой команды.

### 4. Progressive enhancement только для submit lifecycle

Локальный JS listener на financial forms синхронно блокирует submit controls после первого принятого submit. Он не заменяет native navigation и не делает `fetch`, поэтому не вводит собственный retry. Browser `pageshow` восстанавливает controls после history navigation. Для диагностируемого client-side transport interruption допустимо только нейтральное сообщение «результат неизвестен» и сохранение operationId; отсутствие ответа не интерпретируется как rollback. Без JS действует server-first contract и owner idempotency.

### 5. Stale response — отдельное read-only представление

Controller сохраняет status 409 и renders локальный error view/partial с no-store, snapshot id и двумя GET links. Представление ничего не читает сверх уже известной безопасной snapshot identity и не вызывает calculation/settlement. Альтернатива redirect с 303 отвергнута, потому что acceptance требует сохранить семантику 409.

### 6. Владение и зависимости

Owning module финансовых facts — `FMonitor2\\Otiz\\OtizSettlement`; Yii2 controller/view/helper зависят от него только через существующие public calls. Persistence owner, migrations и rapid-pilot adapter не меняются. Architecture impact ограничен существующим YiiRuntime boundary; изменение controller требует обязательного `pilot_http_auth_001_global_calls_test.php` и `make architecture-check` в focused наборе, но не нового exception/policy.

## Risks / Trade-offs

- [Session flash может быть недоступен или потерян между запросами] → форма остаётся безопасно пустой с общей ошибкой; финансовая команда не повторяется и текст не переносится в URL.
- [Back-forward cache оставит disabled controls] → обработчик `pageshow` снимает только локальный in-flight marker после завершённой navigation, сохраняя operationId.
- [Очень длинный/вредоносный текст отражается в HTML] → length validation до flash, allowlist и обязательный framework escaping; browser test проверяет отсутствие executable nodes.
- [Несколько drawer forms на странице] → state связан одновременно с snapshotId/objectId/operationId; только совпавший drawer открывается и получает focus.
- [Неизвестный исход невозможно достоверно определить server-rendered формой] → UI не делает утверждений и полагается на явный replay с тем же operationId; автоматический retry запрещён.

## Migration Plan

DDL/backfill отсутствуют. Развёртывание — обычная замена Yii2 PHP/view/asset файлов после exact-source CI. Rollback возвращает presentation behavior; созданные существующим owner финансовые facts не откатываются и остаются append-only. Merge/deploy и рабочий стенд не входят в delivery #249.
