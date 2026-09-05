# Protected admission assertions — unapplied Gate 3 packet

Дата: 2026-09-05. Patch author: `/root`.
Authority: exact owner-approved admission revision 3, candidate hash
`c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`;
approval `owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`.

Patch: `protected-e2e-admission-assertions-v1-2026-09-05.patch`.
SHA256: `2fadffb1b9ca4269f03f090a8347155f5b9f005f6d1f3874ac43f758b3e9d33d`.
Before protected SHA256:
`a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`.
Proposed after SHA256:
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.

Ровно три stale assertions заменяются двумя вызовами одного reviewed oracle:
первый table-link assertion заменён на проверку actual `$queueAdmission['body']`;
повторные table headings и table-link assertions вместе заменены на проверку
actual `$queue['body']`. Oracle на обоих responses доказывает весь fixed
semantic-list contract. Вся прочая последовательность байтов сохраняется,
включая DOM setup, pefRedesignNoRaw, status assertion, actor19/missing-ID,
authority/revoke/negative-principal, snapshots, grant и transport equality,
redaction, полный downstream journey и finally cleanup.

Fresh mismatch и private response capture:
`protected-e2e-admission-oracle-green-2026-09-05.md`.
Независимый от renderer literal test matrix — 28 checks, Gate 3 APPROVED:
`reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`; focused GREEN сохранён.
Отдельный oracle Gate 5 проверяется до применения protected patch; этот пакет
не подразумевает его verdict.

`git apply --check docs/operations/protected-e2e-admission-assertions-v1-2026-09-05.patch`
успешен; patch **НЕ применён**. Distinct independent Gate 3 должен проверить
fixture-correction methodology, свежий HTTP 200/stale expected1 actual0, literal
sensitivity и exact byte preservation. До APPROVED protected file не меняется.

После допуска — apply exact patch, запуск полного protected verifier с сохранением
всех downstream failures, полный make verify на exact SHA, distinct Gate 5.
Ни старый manual-registration golden, ни дальнейшие failures не утверждены;
early return/skip/allowed-failure запрещены. Full VERIFY_OK по-прежнему требуется
перед Quality Graph/CI publication.
