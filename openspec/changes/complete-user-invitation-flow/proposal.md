# Change: complete user invitation flow (#250)

Администратор должен получить безопасную полную ссылку и сохранить введённую
форму при отказе. Изменение ограничено Yii2 user-access UI и наследует
`YII2-USER-ACCESS-001`; roadmap parent — #169.

Не меняются IdentityAccess owner, token TTL/one-time/reissue/activation, роли,
auth, schema, письма, deployment и соседние #249/#258/#260.
