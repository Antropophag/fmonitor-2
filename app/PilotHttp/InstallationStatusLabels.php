<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

/** Shared presentation vocabulary; persisted process facts are not rewritten. */
final class InstallationStatusLabels
{
    public const OPTIONS = [
        'needs_assignment_order'=>'Требуется распоряжение',
        'ready_to_open'=>'Готов к открытию',
        'installation'=>'Монтажные работы',
        'document_closeout'=>'Документарное закрытие',
        'completed'=>'Работы завершены',
        'needs_assignment_change'=>'Требуется изменение',
    ];

    public static function canonical(string $label):string
    {
        return match($label) {
            'В работе','В работе после cutover','working'=>'Монтажные работы',
            'Распоряжение подготовлено'=>'Требуется распоряжение',
            'Изменяющее распоряжение подготовлено'=>'Требуется изменение',
            default=>self::OPTIONS[$label]??$label,
        };
    }
}
