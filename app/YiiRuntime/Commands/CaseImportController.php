<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\YiiRuntime\CaseImportConsole;
use yii\console\Controller;

final class CaseImportCommand extends Controller
{
    public function actionRun(): int
    {
        $selected = $this->selectedIds(array_slice($GLOBALS['FMONITOR2_RAW_ARGV'] ?? [], 1));
        if ($selected === null) return $this->finish(['ok' => false, 'reason' => 'CONFIGURATION_INVALID'], 64);
        $outcome = CaseImportConsole::run($selected);
        return $this->finish($outcome['result'], $outcome['exitCode']);
    }

    /** @param list<string> $arguments @return null|list<int> */
    private function selectedIds(array $arguments): ?array
    {
        if (($arguments[0] ?? null) !== 'case-import/run' || end($arguments) !== '--interactive=0') return null;
        $objectArguments = array_slice($arguments, 1, -1);
        if ($objectArguments === [] || count($objectArguments) > 100) return null;
        $selected = [];
        foreach ($objectArguments as $argument) {
            if (preg_match('/^--object-id=([1-9][0-9]*)$/D', $argument, $match) !== 1 || strlen($match[1]) > 19 || (strlen($match[1]) === 19 && strcmp($match[1], '9223372036854775807') > 0)) return null;
            $id = (int) $match[1];
            if (in_array($id, $selected, true)) return null;
            $selected[] = $id;
        }
        return $selected;
    }

    /** @param array<string,mixed> $result */
    private function finish(array $result, int $exitCode): int
    {
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
        return $exitCode;
    }
}
