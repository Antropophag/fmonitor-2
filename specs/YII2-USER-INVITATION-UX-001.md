# YII2-USER-INVITATION-UX-001 — завершённый сценарий приглашения

## Простыми словами

Администратор получает готовую полную ссылку приглашения, может надёжно
скопировать её и передать пользователю вручную. Ошибка не стирает введённые
email и ФИО. Выпуск, перевыпуск, срок, одноразовость, активация, роли и права не
меняются; письма не отправляются.

## Scope and inherited behavior

Slice уточняет только Yii2 HTTP/UI поверх существующего `YiiUserAccess` из
`YII2-USER-ACCESS-001`. Public seams: POST `/pilot/admin/users/invite`, POST
`/pilot/admin/users/{id}/invitation`, GET `/pilot/admin/users` и существующий
GET/POST `/pilot/activate`. Trusted public origin берётся только из уже
проверенных `FMONITOR_TRUSTED_REQUEST_SCHEME` и
`FMONITOR_TRUSTED_REQUEST_HOST`; request `Host`, `Forwarded` и
`X-Forwarded-*` не являются источником ссылки. Existing production admission
отклоняет несовпадающий raw `Host` до owner mutation; при совпадающем trusted
`Host` forwarding headers всё равно не влияют на origin.

Разрешены `UserAccessController`, `users.php`, локальный user-access asset/helper
и focused tests. Не входят TTL, token generation/storage, reissue/activation,
roles/auth, schema, email delivery, общие shell/navigation/CSS, deployment и
другие product slices.

## Acceptance matrix

| ID | Условие / действие | Наблюдаемый результат |
| --- | --- | --- |
| full-link | Успешный create или reissue при пригодных trusted scheme+host | Flash один раз показывает и открывает exact absolute `{scheme}://{host}/pilot/activate?token=…`; fake Host/Forwarded не подменяет origin. Raw token не логируется и не сохраняется дополнительно. |
| unavailable-origin | Trusted scheme/host отсутствуют либо непригодны при bootstrap/request admission | HTTP503 safe configuration response до owner mutation; нет invitation facts, относительной/copyable ссылки или copy-success. Домен не угадывается и автоматического retry/reissue нет. |
| copy | Явный клик при успешном Clipboard API | В clipboard передаётся ровно отображаемая absolute link; только после resolved operation появляется status «Ссылка скопирована». |
| copy-rejected | Clipboard API отсутствует либо reject-ит | Поле остаётся readonly/selectable, получает focus/selection для ручного копирования; показана инструкция ручного копирования, но нет «Ссылка скопирована». Без JS видимы поле и инструкция. Keyboard и narrow viewport сохраняют доступность. |
| invite-reject | Owner возвращает известный `invalid` для email/ФИО/duplicate | После redirect форма содержит escaped исходные email и ФИО; понятная общая причина не придумывает duplicate, известные поля связаны с ошибкой через `aria-invalid`/описание, первое исправляемое поле получает focus. Факты отсутствуют. |
| pending | Первый submit ещё не завершён | Submit disabled на время запроса, повторный submit не инициируется; неизвестный исход не сообщает успех и не запускает автоматический reissue/retry. |
| recipient | Получатель открывает fixture-link в отдельной чистой browser session | Existing activation works; reissued old token rejected and new token activates. Остальные inherited activation/authorization/escaping assertions остаются в `yii2_user_access_001_test.php` и `yii2_user_access_edges_001_test.php`. Текст говорит о ручной передаче, не об отправленном письме. |

## Verification boundary

Focused real Yii HTTP test проверяет trusted-origin construction, rejection form
state, authorization/facts и отсутствие token в application/process output до
первой token-bearing navigation. Access-log redaction и deployment logging не
входят в этот slice: GET activation URL по своей природе содержит token. Real Chromium test
проверяет clipboard resolve/reject, manual/no-JS-visible fallback, narrow viewport,
double-submit guard и activation в отдельном context. Используются только
искусственные токены и disposable fixtures; реальных писем и пользователей нет.
