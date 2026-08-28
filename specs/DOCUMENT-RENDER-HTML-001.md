# DOCUMENT-RENDER-HTML-001 — сформировать печатные HTML-артефакты проекта распоряжения

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)`
- Внутренний production delegate: `ProductionHtmlAssignmentOrderRenderer`
- Утверждённый secondary adapter seam: `ProductionHtmlAssignmentOrderRenderer.renderAssignmentOrder(documentInput)`

## 1. Цель и честная граница формата

Сформировать для первого production-инкремента два самодостаточных печатных UTF-8 HTML-артефакта: проект распоряжения и приложение. Пользователь может скачать HTML, открыть его в браузере и напечатать стандартными средствами.

Артефакты не являются PDF, DOCX, подписанным документом или финальным файлом 1С ДО. UI/download metadata обязаны показывать расширение `.html` и media type `text/html`; label «PDF» недопустим. Нормативный PDF-пример № 12-Р остаётся основанием состава реквизитов, а не заявлением о текущем output format.

## 2. Предусловия

- используется successful example `ORDER-PREPARE-002` для объекта монтажа `4512`, монтажника `1042`, инженера `73` и версии `1`;
- assignment-order business date `2026-08-27`;
- object snapshot: адрес `Москва, ул. Примерная, д. 10`, подъезд/секция `2`, регистрационный номер объекта `77-000123`, planned dates `2026-10-05`–`2026-12-20`;
- organization form `individual`;
- installer snapshot содержит `1042`, `Иванов Иван Иванович`, `Электромеханик по лифтам`, status `employed`, source `one_c_zup_via_bitrix`;
- engineer snapshot содержит `Петров Пётр Петрович`, `Инженер строительного контроля`;
- `ProductionHtmlAssignmentOrderRenderer` является единственным renderer delegate; fallback test/PDF renderer отсутствует.

Окружение может быть in-memory: persistence HTML bytes не является целью этого renderer slice.

## 3. Контракт renderer

Renderer получает уже проверенный immutable document input от `InstallationProcess` и возвращает ровно два элемента в нормативном порядке:

```text
1. type = order
   filename = assignment-order-v1.html
   mediaType = text/html
2. type = appendix
   filename = assignment-order-v1-appendix.html
   mediaType = text/html
```

Каждый элемент также возвращает exact UTF-8 bytes разделов 5–6. `InstallationProcess` сохраняет только filename/mediaType/byte-size/SHA-256 как утверждено data model; хранение/повторная выдача bytes получает отдельный seam.

## 4. Детерминизм, нормализация и безопасность

- encoding — UTF-8 без BOM;
- line endings — только LF (`0A`), после закрывающего `</html>` находится ровно один завершающий LF;
- даты отображаются `DD.MM.YYYY`; диапазон соединён UTF-8 en dash `–` без пробелов;
- `individual` отображается русской меткой `Индивидуальная`;
- document status — точная метка `Проект`; renderer не присваивает регистрационный номер;
- все динамические текстовые значения перед интерполяцией проходят PHP-эквивалент `htmlspecialchars(value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` с `double_encode = true`;
- числа tab/version также преобразуются в decimal string и экранируются тем же правилом;
- порядок элементов, атрибутов, CSS declarations и пробелов фиксирован литералами;
- текущий clock, locale процесса, DB ordering и случайность не входят в bytes;
- HTML не содержит script, remote URL, external stylesheet/font/image, iframe, form или executable event attributes;
- print CSS встроен в каждый файл; сетевой доступ для просмотра/печати не нужен.

## 5. Exact bytes артефакта `order`

Следующий code block является точным UTF-8 содержимым; Markdown fence в bytes не входит, после последнего `</html>` входит один LF:

```html
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Проект распоряжения</title>
<style>@page{size:A4;margin:18mm}body{font:14px/1.4 Arial,sans-serif;color:#111}h1{font-size:20px;margin:0 0 18px}dl{display:grid;grid-template-columns:190px 1fr;gap:8px 16px}dt{font-weight:700}dd{margin:0}@media print{body{print-color-adjust:exact}}</style>
</head>
<body>
<main>
<h1>Проект распоряжения</h1>
<dl>
<dt>Статус документа</dt><dd>Проект</dd>
<dt>Дата распоряжения</dt><dd>27.08.2026</dd>
<dt>Адрес объекта</dt><dd>Москва, ул. Примерная, д. 10</dd>
<dt>Подъезд/секция</dt><dd>2</dd>
<dt>Регистрационный номер объекта</dt><dd>77-000123</dd>
<dt>Форма организации труда</dt><dd>Индивидуальная</dd>
<dt>Инженер строительного контроля</dt><dd>Петров Пётр Петрович — Инженер строительного контроля</dd>
</dl>
</main>
</body>
</html>
```

Независимые expected metadata:

```text
byteSize = 1093
sha256 = 682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928
```

## 6. Exact bytes артефакта `appendix`

```html
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Приложение к проекту распоряжения</title>
<style>@page{size:A4 landscape;margin:14mm}body{font:12px/1.35 Arial,sans-serif;color:#111}h1{font-size:18px;margin:0 0 14px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #333;padding:6px;vertical-align:top;text-align:left}th{font-weight:700;background:#eee}@media print{body{print-color-adjust:exact}}</style>
</head>
<body>
<main>
<h1>Приложение к проекту распоряжения</h1>
<table>
<thead><tr><th>Объект</th><th>Плановые даты</th><th>Монтажник</th><th>Кадровый факт</th><th>Инженер строительного контроля</th></tr></thead>
<tbody><tr><td>Москва, ул. Примерная, д. 10; подъезд/секция 2; рег. номер 77-000123</td><td>05.10.2026–20.12.2026</td><td>1042 — Иванов Иван Иванович — Электромеханик по лифтам</td><td>employed; источник one_c_zup_via_bitrix</td><td>Петров Пётр Петрович — Инженер строительного контроля</td></tr></tbody>
</table>
</main>
</body>
</html>
```

Независимые expected metadata:

```text
byteSize = 1262
sha256 = da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac
```

## 7. Публичный результат и проекция

Действие остаётся прежним:

```php
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
```

Command result совпадает с successful `ORDER-PREPARE-002`:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

Публичная проекция содержит ровно два artifacts:

```text
[
  {
    type: order,
    filename: assignment-order-v1.html,
    mediaType: text/html,
    size: 1093,
    sha256: 682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928
  },
  {
    type: appendix,
    filename: assignment-order-v1-appendix.html,
    mediaType: text/html,
    size: 1262,
    sha256: da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac
  }
]
```

Остальные order/process facts, snapshots, assignments, событие подготовки и закрытые work/checklist gates строго совпадают с `ORDER-PREPARE-002`; меняется только утверждённая реализация renderer и metadata её реальных HTML bytes.

## 8. Публичный seam теста

Тест собирает `InstallationProcess` с `ProductionHtmlAssignmentOrderRenderer` как единственным новым production delegate и вызывает только публичную prepare command/projection. Он не вызывает renderer напрямую и не читает его private/template methods.

Expected HTML, sizes и hashes берутся литерально из разделов 5–7. Тест не вычисляет expected SHA/size из production output. Production process самостоятельно вычисляет metadata из returned bytes, поэтому любая разница в escaping, field mapping, whitespace, LF, CSS, order или encoding меняет публичный hash/size и делает тест красным.

Отдельный malicious-text escaping example не входит в этот tracer; обязательное экранирование остаётся production invariant и проверяется Gate 5 review. Его отдельная чувствительная public test требует следующего SSD-slice с независимо заданными escaped bytes.

## 9. Secondary adapter seam и fail-closed input contract

`ProductionHtmlAssignmentOrderRenderer` является production adapter с самостоятельно защищённой публичной class boundary. Помимо process-теста раздела 8 разрешён прямой adapter test:

```php
$renderer->renderAssignmentOrder($documentInput);
```

Этот secondary seam предназначен только для доказательства fail-closed поведения повреждённого внутреннего input, которое невозможно надёжно подать через уже валидирующий `InstallationProcess`. Он не становится UI/HTTP seam и не разрешает callers обходить process-команды.

Перед созданием первой строки/буфера артефакта renderer проверяет минимальный точный shape:

- `assignmentOrderVersion` существует, является именно PHP `int` и `> 0`;
- `assignmentOrderDate` является string точного формата `YYYY-MM-DD` и реальной календарной датой;
- `organizationType` имеет точное значение `individual`; `brigade` этим первым шаблоном не поддерживается;
- `installationObjectSnapshot` является array с непустыми string `address`, `entrance`, `objectRegistrationNumber` и реальными exact `YYYY-MM-DD` `plannedStartDate`, `plannedFinishDate`;
- `installers` является list ровно из одного array; у элемента положительный integer `tabId` и непустые string `fullName`, `position`, `status`, `source`;
- `controlEngineer` является array с положительным integer `userId` и непустыми string `fullName`, `position`.

«Непустая string» здесь означает string, для которой `trim(value) !== ''`; renderer сохраняет/экранирует исходное значение и не использует trim как преобразование output. Array с associative/gapped numeric keys не является list. Дополнительные поля допустимы и игнорируются: renderer не сериализует input целиком.

При первом нарушении любого пункта renderer бросает до bytes единое исключение:

```text
InvalidArgumentException
message = "Invalid assignment order document input."
```

Исключение не содержит invalid value, filesystem path, имя шаблона или персональные данные. Метод не возвращает пустой/частичный список и не оставляет доступных artifact bytes.

Обязательные независимые rejected examples:

```text
A: assignmentOrderVersion = 0
B: assignmentOrderVersion = "../1"
C: assignmentOrderDate = "2026-02-31"
D: organizationType = "brigade"
E: installers = [{ tabId: 1042, fullName: "Иванов Иван Иванович" }]
```

Каждый отдельный прямой вызов получает exact exception выше и никакого результата. A/B доказывают, что version нельзя использовать как path-like filename component; C — что regex без календарной проверки недостаточен; D — что unsupported brigade не выдаётся за корректный документ; E — что отсутствующие nested fields не превращаются в notices, пустые ячейки или частичный шаблон.

Если то же исключение возникает при вызове renderer из публичной `prepareAssignmentOrder`, процесс наследует утверждённый `ORDER-PREPARE-007`: возвращает стабильный render-failure result, не сохраняет version/assignments/artifacts и не раскрывает exception details. Новый process reason этим срезом не вводится.

## 10. Аудит и неизменяемость

Успех добавляет прежнее одно событие `assignment_order_prepared`; renderer не добавляет самостоятельное событие. Event payload содержит утверждённые metadata/hashes, но не HTML bytes.

Подготовленные metadata неизменяемы вместе с версией. Повторное открытие projection не вызывает renderer и не меняет hashes. Фактическая regeneration/download bytes не обещается данным срезом: до отдельного storage/download seam система предоставляет файлы только в результате того production request, в котором renderer вернул bytes внутреннему caller.

## 11. Не входит в срез

- PDF/DOCX generation, headless browser и binary conversion;
- подпись, печать 1С ДО и финальный зарегистрированный файл;
- storage bytes, download/regeneration endpoint и retention;
- template version table/editor;
- multi-installer brigade layout и pagination beyond browser print;
- malicious dynamic-text escaping executable test;
- fonts/images/logos/remote resources;
- HTTP/UI и browser compatibility matrix.

## 12. Решения и доказательства

- нормативный пример `Распоряжение от 10.03.2026 № 12-Р.pdf` определяет нужные сведения, но не обязывает первый renderer выдавать PDF;
- `specs/ORDER-PREPARE-002.md`: public prepare seam, exact snapshots and two-artifact contract;
- `docs/fmonitor-2-pilot-data-model.md`: DocumentRenderer seam и хранение artifact metadata/hash отдельно от bytes strategy;
- `docs/order-template-required-inputs.md`: обязательные поля order/appendix.
- `specs/ORDER-PREPARE-007.md`: process-level mapping renderer exception без частичного результата.

## 13. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал self-contained production HTML renderer без ложной PDF-маркировки следующим единичным SSD + TDD-срезом; версия 0.2 повторно утверждена с прямым fail-closed adapter seam для повреждённого внутреннего document input.

Gate 2 разрешён для версии `0.2`.
