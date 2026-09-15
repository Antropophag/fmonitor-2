## Context

См. `proposal.md`. На актуальном main selection получает engineer ID из HTML form, а current engineer в native карточке/очереди читается из последнего `fm2_assignment_order_applications`. Application появляется только после принятия и применения оригинала, поэтому не может владеть независимой оперативной заменой. Решение владельца разрешает ровно один самостоятельный append-only owner и bounded bootstrap из native application; `rapid-pilot` и legacy исключены.

## Goals / Non-Goals

**Goals:**

- один узкий owner для command/read/history current engineer assignment;
- неизменяемая lineage и optimistic concurrency/idempotency;
- server-owned engineer resolution в preparation и immutable document snapshot;
- минимальное подключение карточки и существующих current-engineer projections.

**Non-Goals:**

- generic assignment/event framework, окружное распределение и массовая миграция;
- изменение composition/application/original lifecycle кроме замены источника current engineer при создании нового snapshot;
- изменение installer ownership #38, checklist/offline, календаря, IAM redesign или `rapid-pilot`.

## Decisions

### Отдельная append-only таблица истории с текущим чтением по sequence

Owning module располагается в native InstallationProcess boundary и владеет одной таблицей фактов. Строка хранит case/object, monotonic sequence, new engineer, nullable previous fact/engineer, request identity/fingerprint, actor и UTC timestamp. Current read выбирает единственную максимальную sequence с проверкой lineage/coherence.

Альтернатива — mutable current row плюс audit — отвергнута: она создаёт два согласуемых источника. Расширение `fm2_assignment_order_applications` отвергнуто: assignment больше не требует документа.

### Один public application seam для изменения

Команда принимает request ID, object ID, engineer ID, expected revision и actor ID. Owner сам проверяет native exact permission, active engineer directory, case/object linkage, request replay и current revision внутри транзакции с case lock. Yii2 controller только нормализует transport и отображает безопасный outcome.

Альтернатива — запись из controller — нарушает единый application seam и server-side ownership.

### Bootstrap остаётся read provenance, а не скрытой записью

При отсутствии standalone rows reader берёт только единственный latest coherent confirmed native application и возвращает revision 0/provenance `native_application_bootstrap`. Первый command записывает sequence 1 и lineage, включающую bootstrap engineer/provenance, после чего applications больше не определяют current engineer.

Это избегает runtime migration writes на GET и массового backfill. Неоднозначность/corruption возвращает unavailable; отсутствие application — missing.

### Preparation не принимает engineer authority от клиента

Selection portal возвращает один current engineer вместо каталога engineers. Форма сохраняет только installer inputs. Selection command owner под lock перечитывает current assignment и создаёт selection snapshot с найденным engineer; stale current revision даёт conflict. Устаревшие engineer form fields не становятся источником выбора.

Альтернатива — hidden engineer ID — отвергнута, поскольку допускает stale/client-controlled authority.

### Исторические snapshots не перепроецируются

Existing orders, applications, originals и checklist evidence продолжают читать сохранённые snapshots. Только новый selection/order snapshot получает current engineer. #40 current-engineer projections переключаются на reader точечно; #38 installer application projection не меняет источник монтажников.

### Schema и deployment

Минимальная migration регистрируется существующим штатным migration catalogue/runner способом. Runtime DDL запрещён. Backup/restore охватывает таблицу через существующий schema inventory только если текущая registration mechanism требует явного перечисления; harness/CI policy не меняются.

Architecture check impact ограничен новым owner namespace/dependency и Yii route/controller registration. `rapid-pilot` adapter отсутствует и не меняется.

## Risks / Trade-offs

- [Bootstrap application может быть повреждён или неоднозначен] → fail closed `unavailable`, без fallback и без GET mutation.
- [Замена конкурирует с preparation] → обе операции используют case/current revision locking; документ получает один подтверждённый snapshot либо conflict.
- [Новый permission отсутствует после deploy] → feature fail closed для mutation; migration не раздаёт права скрыто, fixture/deployment registration выполняется существующим IAM способом.
- [Переключение #40 раздувает scope] → менять только shared current-engineer read; при необходимости redesign остановить delivery с blocker.

## Migration Plan

1. Применить additive schema migration и зарегистрировать exact permission штатным способом без автоматического grant неизвестным ролям.
2. Развернуть owner/read seam и Yii2 card action.
3. Переключить preparation и bounded current-engineer consumers.
4. Existing objects bootstrap read-only из latest confirmed native application; standalone row появляется только после explicit mutation.
5. Rollback application code сохраняет таблицу и историю; destructive rollback данных не выполняется.
