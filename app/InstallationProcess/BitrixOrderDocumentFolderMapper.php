<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class BitrixOrderDocumentFolderMapper
{
    public static function orderNumbers(string $name, int $limit): array
    {
        if ($name === '' || str_contains($name, "\0") || $limit < 1) throw new \DomainException('AMBIGUOUS_FOLDER_NAME');
        if (!str_contains($name, '-')) return [$name];
        if (preg_match('/^([0-9]+)\.([0-9]+)-([0-9]+)\.\2$/D', $name, $parts) !== 1
            || (strlen($parts[1]) > 1 && $parts[1][0] === '0')
            || (strlen($parts[3]) > 1 && $parts[3][0] === '0')) throw new \DomainException('AMBIGUOUS_FOLDER_NAME');
        $first = (int) $parts[1]; $last = (int) $parts[3];
        if ($last < $first) throw new \DomainException('AMBIGUOUS_FOLDER_NAME');
        if ($last - $first + 1 > $limit) throw new \DomainException('LIMIT_EXCEEDED');
        $result=[]; for ($value=$first; $value<=$last; $value++) $result[]=$value.'.'.$parts[2];
        return $result;
    }
}
