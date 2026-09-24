# Design

Контроллер собирает public origin только из уже валидируемой runtime пары
`FMONITOR_TRUSTED_REQUEST_SCHEME`/`FMONITOR_TRUSTED_REQUEST_HOST`, а не из
request headers. Invitation flash хранит presentation payload ровно до
следующего GET. Rejected invite flash хранит только введённые не-secret email и
ФИО и общий безопасный error code/message.

Локальный `users.js` владеет progressive enhancement: disable-on-submit,
Clipboard Promise feedback и selection fallback. HTML остаётся usable без JS.
Token не попадает в application/process error output, telemetry или storage.
Focused oracle читает process output до первого token-bearing activation GET;
web-server access-log redaction относится к deployment logging и не меняется
этим UI slice. No schema, owner or shared-shell changes. Existing disposable
`UserAccessFixture` remains isolated.
