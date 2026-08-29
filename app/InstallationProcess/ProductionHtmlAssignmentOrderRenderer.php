<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionHtmlAssignmentOrderRenderer
{
    /** @return list<array{type: string, filename: string, mediaType: string, bytes: string}> */
    public function renderAssignmentOrder(array $input): array
    {
        $this->validateInput($input);

        $version = $this->escape((string) $input['assignmentOrderVersion']);
        $orderDate = $this->escape($this->displayDate((string) $input['assignmentOrderDate']));
        $object = $input['installationObjectSnapshot'];
        $address = $this->escape((string) $object['address']);
        $entrance = $this->escape((string) $object['entrance']);
        $registrationNumber = $this->escape((string) $object['objectRegistrationNumber']);
        $plannedStart = $this->escape($this->displayDate((string) $object['plannedStartDate']));
        $plannedFinish = $this->escape($this->displayDate((string) $object['plannedFinishDate']));
        $organizationType = $this->escape($this->organizationLabel((string) $input['organizationType']));
        $engineer = $input['controlEngineer'];
        $engineerName = $this->escape((string) $engineer['fullName']);
        $engineerPosition = $this->escape((string) $engineer['position']);
        $appendixRows = [];
        foreach ($input['installers'] as $installer) {
            $installerTabId = $this->escape((string) $installer['tabId']);
            $installerName = $this->escape((string) $installer['fullName']);
            $installerPosition = $this->escape((string) $installer['position']);
            $installerStatus = $this->escape((string) $installer['status']);
            $installerSource = $this->escape((string) $installer['source']);
            $appendixRows[] = "<tr><td>{$address}; подъезд/секция {$entrance}; рег. номер {$registrationNumber}</td><td>{$plannedStart}–{$plannedFinish}</td><td>{$installerTabId} — {$installerName} — {$installerPosition}</td><td>{$installerStatus}; источник {$installerSource}</td><td>{$engineerName} — {$engineerPosition}</td></tr>";
        }
        $appendixRows = implode("\n", $appendixRows);

        $order = <<<HTML
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
<dt>Дата распоряжения</dt><dd>{$orderDate}</dd>
<dt>Адрес объекта</dt><dd>{$address}</dd>
<dt>Подъезд/секция</dt><dd>{$entrance}</dd>
<dt>Регистрационный номер объекта</dt><dd>{$registrationNumber}</dd>
<dt>Форма организации труда</dt><dd>{$organizationType}</dd>
<dt>Инженер строительного контроля</dt><dd>{$engineerName} — {$engineerPosition}</dd>
</dl>
</main>
</body>
</html>
HTML;
        $order .= "\n";

        $appendix = <<<HTML
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
<tbody>{$appendixRows}</tbody>
</table>
</main>
</body>
</html>
HTML;
        $appendix .= "\n";

        return [
            ['type' => 'order', 'filename' => "assignment-order-v{$version}.html", 'mediaType' => 'text/html', 'bytes' => $order],
            ['type' => 'appendix', 'filename' => "assignment-order-v{$version}-appendix.html", 'mediaType' => 'text/html', 'bytes' => $appendix],
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    private function validateInput(array $input): void
    {
        $object = $input['installationObjectSnapshot'] ?? null;
        $installers = $input['installers'] ?? null;
        $engineer = $input['controlEngineer'] ?? null;

        $valid = isset($input['assignmentOrderVersion'])
            && is_int($input['assignmentOrderVersion'])
            && $input['assignmentOrderVersion'] > 0
            && $this->isDate($input['assignmentOrderDate'] ?? null)
            && in_array($input['organizationType'] ?? null, ['individual', 'brigade'], true)
            && is_array($object)
            && $this->isNonblankString($object['address'] ?? null)
            && $this->isNonblankString($object['entrance'] ?? null)
            && $this->isNonblankString($object['objectRegistrationNumber'] ?? null)
            && $this->isDate($object['plannedStartDate'] ?? null)
            && $this->isDate($object['plannedFinishDate'] ?? null)
            && is_array($installers)
            && array_is_list($installers)
            && count($installers) > 0
            && $this->validInstallers($installers)
            && is_array($engineer)
            && isset($engineer['userId'])
            && is_int($engineer['userId'])
            && $engineer['userId'] > 0
            && $this->isNonblankString($engineer['fullName'] ?? null)
            && $this->isNonblankString($engineer['position'] ?? null);

        if (!$valid) {
            throw new \InvalidArgumentException('Invalid assignment order document input.');
        }
    }

    private function validInstallers(array $installers): bool
    {
        foreach ($installers as $installer) {
            if (!is_array($installer)
                || !isset($installer['tabId'])
                || !is_int($installer['tabId'])
                || $installer['tabId'] <= 0
                || !$this->isNonblankString($installer['fullName'] ?? null)
                || !$this->isNonblankString($installer['position'] ?? null)
                || !$this->isNonblankString($installer['status'] ?? null)
                || !$this->isNonblankString($installer['source'] ?? null)) {
                return false;
            }
        }
        return true;
    }

    private function isNonblankString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function isDate(mixed $value): bool
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function displayDate(string $value): string
    {
        return \DateTimeImmutable::createFromFormat('!Y-m-d', $value)->format('d.m.Y');
    }

    private function organizationLabel(string $value): string
    {
        return $value === 'individual' ? 'Индивидуальная' : 'Бригадная';
    }
}
