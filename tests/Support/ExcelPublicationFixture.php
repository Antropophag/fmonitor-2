<?php
// OTIZ-EXCEL-PUBLICATION-001 — root-authored synthetic money operands on canonical persistence.
declare(strict_types=1);
require_once dirname(__DIR__).'/Yii2/PreopeningFixture.php';
final class ExcelPublicationFixture
{
 public PreopeningFixture $http;
 public yii\db\Connection $yii;
 public string $now='2026-09-14T12:00:00Z';
 private int $sequence=2000;
 public function __construct(){ $this->http=new PreopeningFixture(dirname(__DIR__,2));$h=$this->http;$this->yii=new yii\db\Connection(['dsn'=>'mysql:host='.(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('FMONITOR_TEST_DB_PORT')?:'23306').';dbname='.$h->database,'username'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','password'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','charset'=>'utf8mb4']);$this->yii->open();}
 public function operation():string{return sprintf('00000000-0000-4000-8000-%012d',++$this->sequence);}
 public function clock():Closure{return fn():string=>$this->now;}
 public static function inputs(string $date,array $objects):array
 {
  $source=['label'=>'Literal Excel publication fixture','locator'=>'spec/OTIZ-EXCEL-PUBLICATION-001','contentSha256'=>str_repeat('a',64)];$rows=[];
  foreach($objects as$o){$id=$o['id'];$premium=$o['premium']??10000;$progress=$o['progress']??10000;$deadline=$o['deadline']??'2026-09-04';$pto=$o['pto']??null;$team=$o['team']??[['tab'=>'1','name'=>'One','position'=>'Installer','weight'=>1],['tab'=>'10','name'=>'Ten','position'=>'Installer','weight'=>1],['tab'=>'2','name'=>'Two','position'=>'Installer','weight'=>1]];$fact=static fn($value):array=>['value'=>$value,'effectiveDate'=>'2026-09-01','source'=>$source];
  $rows[]=['id'=>$id,'caseId'=>$o['caseId']??6101,'reg'=>'EXCEL-'.$id,'address'=>'Synthetic '.$id,'progress'=>$progress,'deadline'=>$deadline,'pto'=>$pto,'premium'=>$premium,'shaft'=>10000,'team'=>$team,'issues'=>$o['issues']??[],'sourceEvidence'=>['capturedAt'=>'2026-09-14T12:00:00Z','fixture'=>$source],'operands'=>['reportDate'=>$fact($date),'premiumCents'=>$fact($premium),'shaftBp'=>$fact(10000),'progressBp'=>$fact($progress),'deadlineDate'=>$fact($deadline),'completionDate'=>$fact($pto)]];
  }return$rows;
 }
 public function publication(array $objects):FMonitor2\Otiz\SnapshotPublication{return new FMonitor2\Otiz\SnapshotPublication(new FMonitor2\Otiz\MariaDbSnapshotStore($this->http->db,$this->http->p),static fn(string $date):array=>self::inputs($date,$objects),$this->clock());}
 public function settlement():FMonitor2\Otiz\OtizSettlement{return new FMonitor2\Otiz\OtizSettlement($this->yii,$this->http->p,$this->clock());}
 public function read(int $id):array{return$this->publication([])->read(96,$id);}
 public static function immutable(array $read):array{unset($read['events']);return$read;}
 public function close():void{$this->yii->close();$this->http->close();}
}
function excelRefusal(string $expected,Closure $action):void{try{$action();throw new TestFailure('Expected '.$expected);}catch(DomainException $error){assertSameValue($expected,$error->getMessage(),'exact Excel publication refusal');}}
