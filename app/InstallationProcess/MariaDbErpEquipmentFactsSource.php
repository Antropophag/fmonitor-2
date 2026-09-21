<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact read-only legacy ERP query ownership for bounded local order candidates. */
final class MariaDbErpEquipmentFactsSource
{
    public static function ordersQuery(int $count): string
    {
        return "SELECT sale.Номер AS sourceOrderNumber, NULLIF(CONVERT(varchar(10), MAX(sroki.ДатаКомплектности), 23), '0001-01-01') AS readinessDate, NULLIF(CONVERT(varchar(10), MAX(sroki.ДатаПолнойОтгрузки), 23), '0001-01-01') AS fullShipmentDate
FROM [1c-erp].[BI_DЗаказКлиентаID] sale
JOIN [1c-erp].[BI_DЗаказНаПроизводство2_2ID] prod ON prod.Номер = sale.Номер
LEFT JOIN [1c-erp].[BI_Sпроф_СрокиХраненияГотовойПродукцииID] sroki ON sroki.ЗаказКлиента = sale.Ссылка
WHERE sale.Номер IN (".self::parameters($count).")
GROUP BY sale.Номер";
    }
    public static function shipmentsQuery(int $count): string
    {
        return "SELECT sale.Номер AS sourceOrderNumber, CONVERT(varchar(10), MIN(etap.ДатаОтгрузки), 23) AS shipmentDate
FROM [1c-erp].[BI_DЭтапПроизводства2_2ID] etap
JOIN [1c-erp].[BI_DЗаказНаПроизводство2_2ID] prod ON prod.Ссылка = etap.Распоряжение
JOIN [1c-erp].[BI_DЗаказКлиентаID] sale ON sale.Ссылка = prod.ДокументОснование
JOIN [1c-erp].[BI_Eshlz_ТипЗаказаID] type ON type.Ссылка = sale.shlz_ТипЗаказа
WHERE etap.ПометкаУдаления = 0
  AND type.Наименование = N'ЛифтовоеОборудование'
  AND etap.ДатаОтгрузки IS NOT NULL
  AND etap.ДатаОтгрузки <> '0001-01-01'
  AND sale.Номер IN (".self::parameters($count).")
GROUP BY sale.Номер";
    }
    private static function parameters(int $count): string
    {
        if($count<1||$count>500)throw new \InvalidArgumentException('CONFIGURATION_INVALID');
        return implode(',',array_fill(0,$count,'?'));
    }
}
