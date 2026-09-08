# Возврат в карточку после оригинала — 2026-09-07

Прямое замечание владельца: загрузчик не должен продолжать чужие действия
применения состава/открытия, после оригинала нужен возврат в карточку.
`OriginalUploadView` теперь задаёт card URL для существующего accepted/replayed
client redirect. Helper предлагает карточку; upload/correction API, CSRF,
проверки ответа и отдельные application/opening actions не меняются.

RED: view test ожидал card URL, получал originals/submit. GREEN: initial и
correction, сохранённые command endpoint/CSRF, доступная card link, отсутствие
execution link. Existing original-upload client tests PASS. Независимый review
APPROVED: `reviews/code/MANUAL-PILOT-ORIGINAL-UPLOAD-RETURN-2026-09-07.md`.

Root synthetic headless golden в private
`runtime/feedback-preview-and-return-20260907/` подтвердил реальные переходы
`uploadReturnedToCard=true`, `correctionReturnedToCard=true`, последующие
отдельные apply/reapply/opening,41 пункт/7 фото/7 разделов,85→100%, отсутствие
ошибок,9/9 отметок во время272 frames и pending reload. Даты ПТО/декларации
остались нетронутыми и были отправлены с сегодняшним default. Нет мутаций966.
