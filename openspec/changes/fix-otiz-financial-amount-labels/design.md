## Context

См. [proposal.md](proposal.md). `MariaDbOtizSettlementView` уже отдаёт две разные величины: snapshot-owned `closed_before_cents` и live `global_closed_cents`. Первая сохраняется builder как сумма `paid_cents` closure evidence на момент расчёта; вторая вычисляется read-only проекцией как signed-сумма `paid_cents + discipline_cents + deadline_cents` всех операций объекта без ограничения snapshot.

## Goals / Non-Goals

**Goals:** исправить только semantic labels/copy в существующем drawer #228 и доказать соответствие пары label/value на desktop/mobile.

**Non-Goals:** любые изменения `Otiz` owner, SQL, persistence, formulas, commands, styles, XLSX, archive/history layout и business states.

## Decisions

1. Owning module финансовых данных остаётся `app/Otiz`; presentation меняется только в `app/YiiRuntime/Views/otiz-snapshot.php`. Новых dependencies и persistence owner нет. Альтернатива с новой агрегацией отклонена: обе требуемые суммы уже доступны.
2. `closed_before_cents` получает уточнение «сохранено в расчёте», но сохраняет слово «Выплачено»: текущий builder включает только `paid_cents`. Временную формулировку «до расчётной даты» не используем, поскольку UI-контракту достаточно snapshot semantics.
3. Пояснение `global_closed_cents` явно называет выплаты, удержания и сторно. Это соответствует signed-сумме уже созданных closure/reversal rows; UI не выводит компоненты и не вычисляет новый показатель.
4. Существующий registered authenticated Chromium test получает изолированные fixtures и scoped assertions внутри точного financial drawer. Тот же проход проверяет 1440/320 px; отдельный тяжёлый E2E не добавляется.
5. `rapid-pilot` не затрагивается: это Yii presentation defect, новый adapter/domain logic не нужен. Architecture-check impact отсутствует, кроме обычной проверки границ изменённых файлов.

## Risks / Trade-offs

- [Длинная подпись может переноситься на узком экране] → проверить видимость label/value и отсутствие horizontal overflow drawer на 320 px, не меняя общие стили.
- [Глобальный поиск текста даст ложный GREEN] → ограничить assertions конкретным drawer и парами `dt`/`dd`.
- [Тест случайно подтвердит арифметику реализацией] → fixture задаёт независимо рассчитанные literal суммы; отдельно сравнить набор видимых денежных значений и fingerprint таблиц.

## Migration Plan

Миграция данных не требуется. Rollback — возврат только presentation-copy и соответствующих assertions; финансовые записи остаются byte-equivalent.
