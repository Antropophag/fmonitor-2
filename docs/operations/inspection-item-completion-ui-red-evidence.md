# INSPECTION-ITEM-COMPLETE-001 UI RED evidence

Date: 2026-09-01

Проверка фиксирует последний пользовательский разрыв capability-only доступа
через executable client interaction, заменив отклонённые test-review проверки
текста JavaScript:
активный инженер с exact capability `inspection.item.complete`, не назначенный
на объект и не имеющий legacy `checklist.edit`, должен видеть доступными только
42 одиночные кнопки отметки пунктов. Фото, изменение исполнителей и массовая
отметка/завершение раздела остаются недоступны.

Первый запуск без test database был отброшен как setup failure. После
`make test-env-up` выполнена точная команда:

```text
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
```

Наблюдаемый qualifying result:

```text
TestFailure: Item-only UI/client contract:
- root exposes item-only completion capability
- item completion controls are not disabled by an inert ancestor
- photo upload controls stay disabled
- installer correction controls stay disabled
- bulk/section completion controls stay disabled
- item click observably enqueues only item_completed
- item click observably sends item_completed
RED_ASSERTION: expected failing behavior observed
```

Проверка достигла реальной HTTP-страницы на isolated migrated database, успешно
разобрала HTML, исполнила отданный production `checklist.js` в детерминированном
browser/IndexedDB harness и активировала доступный одиночный toggle. Наблюдение
ведётся по persisted operation и `fetch` request, а не по исходному тексту
клиента. Попытки активировать disabled photo, installer и bulk controls не
порождают legacy operations. Штатные cleanup/absence probes завершились;
failure относится к production UI contract, а не к setup или fixture.

Artifact hashes:

```text
b3fdcd6c8116043e722d54f85896c190c8db8487f765606e190652c06c3f82c9  tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
92c675e810acf7c6ddbb34271838b21935b3aba4ca7df64327ceb3bcf0d3924e  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
a1846b2ff624e16cc44023a76f86875de98f8dee6982af6f5619640c40908e2d  tests/InstallationProcess/support/inspection_item_complete_ui_browser.js
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd  tools/verification/run.sh
```

Production code и test expectation при фиксации evidence не изменялись.
