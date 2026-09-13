# TEMPLATE-OFFER-REVEAL-001 — предложение PDF после готового состава

## Простыми словами

Сначала сотрудник ФКР выбирает монтажников и подтверждает инженера. Только когда форма готова, рядом появляется необязательный следующий шаг с PDF-шаблоном; изменение состава снова убирает предложение и не позволяет сформировать документ по старому составу. Срез не меняет выбор инженера, сохранение состава, PDF и серверные права.

## Actor и публичный seam

Actor — активный пользователь, которому действующая Yii2 selection route уже разрешает выбирать состав. Нормативный seam — HTML/DOM и клиентские события страницы `GET /pilot/objects/{objectId}/assignment-order/selection`, а для отрицательной границы — существующий `POST /pilot/objects/{objectId}/assignment-orders/{orderId}/template`.

Источник ожидаемого поведения — issue #53, `PRODUCT.md` и действующий pilot contract необязательного шаблона. Решение #52 не реализуется: текущие radio выбора инженера и checkbox подтверждения сохраняются.

## A1. Начальное скрытое состояние

HTML SHALL отдавать helper предложения с `hidden` и `inert`, если текущая форма не подтверждает готовность. Готовность требует одновременно:

1. минимум один нормализованный положительный `installerTabIds[]`;
2. один выбранный `controlEngineerUserId`;
3. checked `controlEngineerConfirmed=yes`.

Скрытый helper и вложенные ссылки/кнопки MUST отсутствовать в последовательности табуляции. Начальный HTML MUST исключать вспышку видимого блока до выполнения script.

## A2. Динамическая видимость

Vanilla JS SHALL пересчитывать готовность после применения выбора монтажников, удаления монтажника, смены engineer radio и переключения confirmation checkbox. Переход не перезагружает страницу и не меняет текущий фокус.

- false → true: снять `hidden` и `inert`, показать helper;
- true → false: установить `inert`, затем скрыть helper;
- одинаковый ввод повторно: состояние DOM остаётся тем же.

## A3. Exact сохранённый состав

Server-rendered DOM SHALL содержать безопасный snapshot идентичности актуального сохранённого состава: order ID, отсортированные уникальные installer IDs и engineer ID, без персональных данных сверх уже показанных на странице.

Действие template POST SHALL быть доступно только когда:

- форма готова по A1;
- актуальное сохранённое распоряжение существует;
- нормализованные installer IDs и engineer ID формы точно совпадают со snapshot.

Для новой либо изменённой, но не сохранённой формы helper SHALL объяснять необходимость сначала сохранить состав и SHALL NOT предоставлять действующий template POST старой/несуществующей версии. После серверного сохранения и повторного GET новая exact identity становится основанием действия.

Прямой устаревший/поддельный POST по-прежнему проверяется существующим Yii controller/application owner. Отказ MUST не создавать `assignment_order_template_generated` или другие новые process/template facts.

## A4. Необязательность и соседние маршруты

Появление helper SHALL NOT автоматически отправлять форму, создавать PDF, сохранять состав, открывать работы или перемещать пользователя. Пользователь может сохранить состав и сразу перейти к загрузке оригинала, не формируя шаблон. Действующие upload/original/opening routes и их authorization остаются неизменными.

## A5. Motion, narrow layout и отказоустойчивость

При обычной настройке движения helper SHALL использовать короткий CSS transition opacity/небольшого вертикального смещения без layout overlay. При `prefers-reduced-motion: reduce` transition и transform MUST быть отключены.

На viewport шириной 360 CSS px helper, текст и действие MUST укладываться без горизонтального overflow страницы. Ни show, ни hide не вызывают `focus()`.

Если JS не выполнился, основной server-side submit состава остаётся доступен; скрытый helper не даёт ложного template action. Серверная авторизация и валидация не зависят от JS.

## A6. Данные, история и повторяемость

Show/hide — read/presentation behavior. До явного сохранения или template POST строки БД, PDF bytes, process events и audit/history MUST не изменяться. Повторение одной последовательности form events SHALL давать одинаковое DOM-состояние.

## Не входит

- #52 и изменение назначения/отображения инженера;
- новый frontend framework;
- изменение template renderer/application seam;
- изменения `rapid-pilot`, migration/schema, Compose, deployment, backup/restore или общей verification policy.
