<?php

declare(strict_types=1);

$missing = [];
foreach (['FMONITOR_SOURCE_USER', 'FMONITOR_SOURCE_PASSWORD'] as $name) {
    $value = getenv($name);
    if (!is_string($value) || $value === '') $missing[] = $name;
}
if ($missing !== []) {
    fwrite(STDERR, 'Не заданы переменные read-only подключения к production: ' . implode(', ', $missing) . PHP_EOL);
    fwrite(STDERR, 'Экспортируйте их в текущей оболочке; секреты не сохраняются в репозитории.' . PHP_EOL);
    exit(2);
}
echo "Production import environment: OK\n";
