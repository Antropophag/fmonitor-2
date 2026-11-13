# Поиск стройконтроля и пароль — независимое итоговое ревью

Reviewer `/root/otiz_ui_review`, gpt-5.6-sol/low; не автор кода/тестов.
Verdict APPROVED по обоим slices29.10.2026. Root автор bounded search fix и всех
тестов; `/root/otiz_ui_executor` автор activation policy/UI.

Search: actual browser RED при первом символе (focus=false,caret0), затем GREEN
на реальной GET-навигации:фокус/выделение/дальнейший ввод,пустой поиск,фильтры,сброс,IME.
Offline/sync prefix byte-identical; scriptURL versioned. Проверка повторена на JS,
скачанном с production после выкладки. Независимое ревью approve с первого прохода.

Password: владелец выбрал10символов и4группы после чтения client generator pw.shlz.ru.
Подтверждены UTF8character10–200,ASCIIlower/upper/digit/punctuation, mismatch/email,
exact whitespace preservation,accessiblehelp,min/max,invalid/no-write,single-use,
Argon2id,existinglogin и concurrentactivation/reissue. Root исправил три пробела
первогоtestreview и устаревший positive race password. Independent final APPROVED.
Focused tests: newsearchbrowser,newpasswordHTTP,existinguser-accessowner/HTTP,
existinguser-accessconcurrency — PASS. makearchitecture-check8rules+HTTPqualification
PASS. Full local suite запрещён и не запускался; fullCI отложен по owner shortcycle.

## Одобренные runtime SHA256

- `da19eb9d193686869c0efc08961b5d3f185e98c3da7dbfd60481e97de6ea35ce` app/YiiRuntime/Assets/control-queue.js
- `d5e839b5341b4467452418d7e24ff18cca5d45f0bcd05338742d4c6144c864f6` app/YiiRuntime/Views/construction-control.php
- `bc1ac8aa95b793686e5e17437c95bc43a29465bc042bac28e1dee226ccf6afe7` app/IdentityAccess/MariaDbUserActivation.php
- `51086c2f8ae0bc733f492bf7ad19a193422580194987df37cdea358cce537fd2` app/YiiRuntime/Views/activate.php

## Production

Search batch20:25МСК; password batch20:31МСК. Exactreviewedbytes совпадают наhost
и вPHPcontainer. До записи провереныbaselinehashes, rollbackcopiesсохранены.
Никакие реальные пользователи/пароли/приглашения не изменялись. Postdeploypassword
pure-validator smoke принимает10,отклоняет9/неполныегруппы; UImin10 подтверждён.
Предыдущая production поставка ОТИЗ и другие файлы не затронуты.
Evidence/sourcepage/captures/rollbackmanifests внеGit:
`/Users/antropophag/.local/share/fmonitor-2/control-search-focus-20261029/`.
