"""Execute the production aggregate query against independent relationship fixtures."""
import json, sqlite3, subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[2]
query=subprocess.check_output(['php','-r',"require 'app/autoload.php';echo FMonitor2\\InstallationProcess\\MariaDbErpEquipmentFactsSource::ordersQuery(4);"],cwd=root,text=True)
db=sqlite3.connect(':memory:');db.row_factory=sqlite3.Row
db.execute("ATTACH DATABASE ':memory:' AS [1c-erp]")
# Only SQL Server date-display functions are shimmed; actual generated FROM/WHERE/GROUP BY executes unchanged.
db.create_function('varchar',1,lambda size:'varchar')
db.create_function('CONVERT',3,lambda kind,value,style:None if value is None else str(value)[:10])
db.executescript('''CREATE TABLE [1c-erp].[BI_DЗаказКлиентаID] (Номер TEXT,Ссылка TEXT);
CREATE TABLE [1c-erp].[BI_DЗаказНаПроизводство2_2ID] (Номер TEXT,ДокументОснование TEXT);
CREATE TABLE [1c-erp].[BI_Sпроф_СрокиХраненияГотовойПродукцииID] (ЗаказКлиента TEXT,ДатаКомплектности TEXT,ДатаПолнойОтгрузки TEXT);
''')
db.executemany('INSERT INTO [1c-erp].[BI_DЗаказКлиентаID] VALUES (?,?)',[('59740.25','S1'),('OTHER','S2'),('ORPHAN','S3'),('ABSENT','S4')])
db.executemany('INSERT INTO [1c-erp].[BI_DЗаказНаПроизводство2_2ID] VALUES (?,?)',[('PROD-A','S1'),('PROD-B','S1'),('OTHER','S2'),('ORPHAN','S2')])
db.executemany('INSERT INTO [1c-erp].[BI_Sпроф_СрокиХраненияГотовойПродукцииID] VALUES (?,?,?)',[('S1','2026-08-01','2026-08-10'),('S1','2026-08-10','2026-08-21'),('S2','0001-01-01','0001-01-01'),('S3','2026-07-01','2026-07-02')])
rows=[dict(row) for row in db.execute(query,('59740.25','OTHER','ORPHAN','ABSENT'))]
by_order={row['sourceOrderNumber']:row for row in rows}
assert set(by_order)=={'59740.25','OTHER'},f'INTENDED_RED: linked order with different production numbers must exist; same-number wrong-document decoy excluded: {list(by_order)}'
assert len(rows)==2,'multiple production/storage rows aggregate once'
assert by_order['59740.25']=={'sourceOrderNumber':'59740.25','readinessDate':'2026-08-10','fullShipmentDate':'2026-08-21'}
assert by_order['OTHER']['readinessDate'] is None and by_order['OTHER']['fullShipmentDate'] is None
print('PASS: actual ERP aggregate query uses document relationship, excludes false number matches and deduplicates dates')
