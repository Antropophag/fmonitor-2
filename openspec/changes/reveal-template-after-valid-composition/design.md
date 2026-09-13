## Context

См. `proposal.md`. Текущий Yii2 selection screen серверно рендерит helper всегда: без сохранённого состава — информационный текст, с сохранённым — отдельную POST-кнопку шаблона. Vanilla JS уже владеет интерактивным выбором монтажников, а production PDF route и application owner проверяют сохранённое распоряжение.

## Goals / Non-Goals

**Goals:**

- Сделать один клиентский owner готовности формы и синхронизировать с ним `hidden`, `inert` и доступность действия.
- Отличать клиентски готовую форму от exact сохранённого состава, для которого разрешена существующая template-команда.
- Сохранить server-rendered fallback и существующие security/domain boundaries.

**Non-Goals:**

- Не менять persistence owner, composition/template application seams, маршруты или аудит.
- Не реализовывать #52 и не переносить выбор инженера в другой экран.
- Не добавлять frontend framework или новую библиотеку движения.
- Не менять `rapid-pilot`, Compose, deployment и verification policy/catalog без отдельной необходимости Gate-плана.

## Decisions

1. **Владельцем поведения остаётся `app/YiiRuntime`.** View выдаёт семантические data-атрибуты и безопасное начальное скрытое состояние; существующий `preopening.js` вычисляет готовность по текущим form controls. Альтернатива с новым модулем отклонена как лишний seam для одного экрана.
2. **Готовность формы и применимость POST разделены.** Helper появляется при валидных installer/engineer/confirmation controls. Кнопка шаблона доступна только если нормализованные текущие installer IDs и engineer ID совпадают с server-rendered snapshot актуального распоряжения. Новая форма показывает следующий шаг сохранения, но не фабрикует order identity.
3. **Скрытие задаётся HTML до выполнения JS.** Это исключает мерцание и убирает элементы из accessibility tree; script атомарно снимает/возвращает `hidden` и `inert`. Без JS предложение остаётся скрытым, тогда как основной submit и отдельный template route после сохранения остаются серверно работоспособными.
4. **Движение принадлежит CSS.** Короткий opacity/translate transition применяется только к видимому helper; media query `prefers-reduced-motion` убирает transition/transform. JS не использует таймеры и не перемещает фокус.
5. **Проверка проходит через публичный HTML/DOM seam.** RED фиксирует server-rendered hidden/inert contract, динамику полей, exact-snapshot disabling, reduced-motion CSS и narrow-layout overflow. Существующие template HTTP authorization/negative tests остаются regression controls.

Persistence owner отсутствует: изменение read/presentation-only. `rapid-pilot` не получает адаптеров. Architecture-check impact ограничен существующими Yii runtime boundaries; общий HTTP qualification остаётся regression control.

## Risks / Trade-offs

- [Расхождение клиентской и серверной нормализации состава] → Передать snapshot как data-значения и сравнивать отсортированные уникальные IDs; сервер остаётся окончательным authority.
- [Скрытый helper мерцает при загрузке] → Рендерить `hidden` изначально и анимировать только после первого client reconciliation.
- [JS отключён] → Основной сценарий сохранения состава остаётся доступен; шаблон доступен после сохранения через повторное открытие экрана, но enhanced предложение не показывается.
- [Параллельный #76 меняет общие verification inventories] → Не редактировать их без требования generated plan; при неизбежности перебазировать после соответствующего #76 среза, не смешивая изменения.
