<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionPdfAssignmentOrderRenderer
{
    /** @return list<array{type:string,filename:string,mediaType:string,bytes:string}> */
    public function renderAssignmentOrder(array $input): array
    {
        $this->validateInput($input);
        $this->loadTcpdf();

        $version=(int)$input['assignmentOrderVersion'];
        $object=$input['installationObjectSnapshot'];
        $engineer=$input['controlEngineer'];
        $date=$this->displayDate((string)$input['assignmentOrderDate']);

        $pdf=new \TCPDF('P','mm','A4',true,'UTF-8',false);
        $pdf->SetCreator('FMonitor 2.0');
        $pdf->SetAuthor('АО «Щербинский лифтостроительный завод»');
        $pdf->SetTitle('Распоряжение о закреплении монтажников');
        $pdf->SetSubject('Распоряжение о закреплении монтажников за объектом монтажа');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(30,15,15);
        $pdf->SetAutoPageBreak(true,18);
        $pdf->setFontSubsetting(true);

        $pdf->AddPage('P','A4');
        $this->renderLogo($pdf);
        $pdf->SetY(47);
        $pdf->SetFont('dejavusanscondensed','B',12);
        $pdf->Cell(0,6,'РАСПОРЯЖЕНИЕ',0,1,'L');
        $pdf->SetFont('dejavusanscondensed','',10);
        $pdf->Cell(0,5,'№ ____________',0,1,'L');
        $pdf->Cell(0,5,'«'.$this->day((string)$input['assignmentOrderDate']).'» '.$this->month((string)$input['assignmentOrderDate']).' '.substr((string)$input['assignmentOrderDate'],0,4).' г.',0,1,'L');
        $pdf->Ln(10);
        $pdf->SetFont('dejavusanscondensed','',11);
        $pdf->MultiCell(140,5.8,'О закреплении монтажников лифтового оборудования за объектами и назначении ответственных производителей работ по монтажу лифтового оборудования в многоквартирных домах г. Москвы',0,'L',false,1);
        $pdf->Ln(10);
        $pdf->MultiCell(0,5.8,'В целях организации работ по монтажу лифтового оборудования на территории г. Москвы в соответствии с заключёнными договорами, а также в рамках взаимодействия с Фондом капитального ремонта многоквартирных домов г. Москвы:',0,'L',false,1);
        $pdf->Ln(5);
        $pdf->SetFont('dejavusanscondensed','',11);
        $pdf->Cell(0,5,'О Б Я З Ы В А Ю:',0,1,'C');
        $pdf->Ln(3);
        $pdf->SetFont('dejavusanscondensed','',10.8);
        $items=[
            ['1.','Закрепить монтажников лифтового оборудования и помощников монтажника лифтового оборудования (далее по тексту — Монтажники), согласно прилагаемому Перечню (Приложение 1 к настоящему распоряжению) за объектом монтажа лифтового оборудования, объектом и производителем работ (далее — Перечень), за многоквартирными домами г. Москвы по адресам, указанным в Перечне, на период выполнения работ согласно планируемой в Перечне форме организации труда (индивидуальной или бригадной).',0],
            ['2.','Назначить '.$engineer['fullName'].' ('.$engineer['position'].') ответственным производителем работ на объекте, указанном в Перечне.',0],
            ['3.','Монтажников и Производителя работ по монтажу лифтового оборудования на объекте по адресу и в сроки, определённые Перечнем.',0],
            ['4.','Монтажников, указанных в Перечне:',0],
            ['4.1.','Производить на объектах работы по демонтажу/монтажу лифтового оборудования с соблюдением требований по качеству, технологии, технических условий, инструкций и стандартов.',1],
            ['4.2.','Соблюдать на объектах производственную и трудовую дисциплину, правила трудового распорядка, требования пожарной безопасности, охраны труда, промышленной и пожарной безопасности.',1],
            ['4.3.','При производстве работ осуществлять экономное расходование материалов и всех видов энергии, бережно относиться к оборудованию и инструменту.',1],
        ];
        $this->writeClauses($pdf,$items);

        $pdf->AddPage('P','A4');
        $pdf->SetFont('dejavusanscondensed','',10.8);
        $this->writeClauses($pdf,[
            ['5.','Производителя работ, указанного в Перечне:',0],
            ['5.1.','Обеспечить руководство соответствующими Монтажниками при выполнении ими работ на объекте, указанном в Перечне.',1],
            ['5.2.','Организовать и контролировать на объектах ход и качество выполнения монтажных работ.',1],
            ['5.3.','Обеспечивать соблюдение Монтажниками на объектах требований охраны труда, промышленной безопасности и пожарной безопасности.',1],
            ['5.4.','Контролировать обеспеченность рабочих мест сырьём, материалами, инструментом, приспособлениями, средствами индивидуальной защиты; принимать необходимые меры по предупреждению и устранению аварий, простоев оборудования, исправлению обнаруженных дефектов и недостатков в результатах выполненных работ.',1],
            ['5.5.','Осуществлять достоверный учёт фактически отработанного Монтажниками рабочего времени и предоставлять отчётность о выполненных на объекте работах в порядке и сроки, установленные НПА АО «ЩЛЗ».',1],
            ['6.','Руководителя отдела монтажа лифтового оборудования по г. Москве (Ерёмин В.С.):',0],
            ['6.1.','Ознакомить с настоящим распоряжением работников службы по работе с региональными операторами капитального ремонта, указанных в Перечне.',1],
            ['6.2.','Обеспечить подготовку и издание распоряжения (или внесение изменений в существующее распоряжение) не позднее следующего рабочего дня после:',1],
            ['6.2.1.','приёма монтажника на работу;',2],
            ['6.2.2.','перевода монтажника на другой объект (в связи с завершением работ на прежнем объекте или возникновением производственной необходимости).',2],
            ['7.','Прекратить действие ранее изданных распоряжений в части объекта, включённого в настоящий Перечень, с даты настоящего распоряжения.',0],
            ['8.','Руководителю отдела управления документооборотом направить распоряжение работникам АО «ЩЛЗ» согласно прилагаемому списку рассылки в течение 3 (трёх) рабочих дней с даты подписания настоящего распоряжения.',0],
        ]);
        $pdf->Ln(4);
        $pdf->SetFont('dejavusanscondensed','',10.8);
        $pdf->MultiCell(0,5.8,'Приложение к распоряжению: 1. Перечень монтажников лифтового оборудования, объектов и производителей работ. Количество объектов — 1; количество монтажников — '.count($input['installers']).'.',0,'L',false,1);
        $pdf->SetY(238);
        $pdf->SetFont('dejavusanscondensed','B',9.7);
        $pdf->MultiCell(92,5.1,"Директор по работе с региональными\nоператорами капитального ремонта\nАлексеева К.О.",0,'L',false,0);
        $pdf->SetFont('dejavusanscondensed','',9.7);
        $pdf->Cell(45,10,'________________',0,0,'C');

        $pdf->AddPage('L','A4');
        $pdf->SetMargins(8,8,8);
        $pdf->SetY(8);
        $pdf->SetFont('dejavusanscondensed','',6.4);
        $pdf->MultiCell(0,3.4,'Приложение 1 к распоряжению № ________ от '.$date."\n".'«О закреплении монтажников лифтового оборудования за объектами и назначении ответственных производителей работ»',0,'R',false,1);
        $pdf->Ln(2);
        $pdf->SetFont('dejavusanscondensed','B',8.5);
        $pdf->Cell(0,5,'Перечень монтажников лифтового оборудования, объектов и производителей работ',0,1,'C');
        $pdf->Ln(2);
        $this->renderAppendixTable($pdf,$input,$object,$engineer);
        $pdf->Ln(5);
        $pdf->SetFont('dejavusanscondensed','',7);
        $pdf->Cell(0,4,'Форма организации труда определяется количеством закреплённых за объектом монтажников.',0,1,'L');
        $pdf->SetY(174);
        $pdf->SetFont('dejavusanscondensed','',7.5);
        $pdf->Cell(178,5,'Руководитель отдела монтажа лифтового оборудования по г. Москве',0,0,'L');
        $pdf->Cell(45,5,'________________',0,0,'C');
        $pdf->Cell(0,5,'В.С. Ерёмин',0,1,'L');
        $pdf->Ln(3);
        $pdf->Cell(178,5,'Директор по работе с региональными операторами капитального ремонта',0,0,'L');
        $pdf->Cell(45,5,'________________',0,0,'C');
        $pdf->Cell(0,5,'К.О. Алексеева',0,1,'L');

        $bytes=$pdf->Output('', 'S');
        return [['type'=>'order','filename'=>'Распоряжение о закреплении монтажников.pdf','mediaType'=>'application/pdf','bytes'=>$bytes]];
    }

    private function renderLogo(\TCPDF $pdf): void
    {
        $asset=dirname(__DIR__,2).'/rapid-pilot/assets/shlz-logo.jpg.base64';
        $encoded=is_file($asset)?file_get_contents($asset):false;
        $bytes=is_string($encoded)?base64_decode(trim($encoded),true):false;
        if(!is_string($bytes)||$bytes==='')throw new \RuntimeException('PDF logo asset is unavailable.');
        $pdf->Image('@'.$bytes,24,15,48,0,'JPEG','','',false,300,'',false,false,0,false,false,false);
    }

    /** @param list<array{0:string,1:string,2:int}> $clauses */
    private function writeClauses(\TCPDF $pdf,array $clauses):void
    {
        $last=count($clauses)-1;
        foreach($clauses as $index=>[$number,$text,$level]){
            $nextLevel=$index<$last?$clauses[$index+1][2]:-1;
            $compactAfter=$nextLevel>$level||($level>0&&$nextLevel===$level);
            $this->writeClause($pdf,$number,$text,$level,$compactAfter?0.35:1.6);
        }
    }

    private function writeClause(\TCPDF $pdf,string $number,string $text,int $level,float $gapAfter):void
    {
        $left=30+($level*8);
        $numberWidth=$level===2?15:($level===1?12:8);
        $textX=$left+$numberWidth;
        $textWidth=210-15-$textX;
        $lineHeight=5.8;
        $y=$pdf->GetY();
        $pdf->SetXY($left,$y);
        $pdf->MultiCell($numberWidth,$lineHeight,$number,0,'L',false,0);
        $pdf->SetXY($textX,$y);
        $pdf->MultiCell($textWidth,$lineHeight,$text,0,'L',false,1);
        $pdf->SetY($pdf->GetY()+$gapAfter);
    }

    private function renderAppendixTable(\TCPDF $pdf,array $input,array $object,array $engineer):void
    {
        $widths=array_map(static fn(int $percent):float=>281*$percent/100,[3,9,12,6,7,16,5,7,7,7,6,8,7]);
        $x0=8.0;$y=$pdf->GetY();$top=12.0;$lower=10.0;$full=$top+$lower;
        $headers=["№\nп/п",'Должность','Фамилия, имя, отчество','Таб. №',"Форма\nорганизации\nтруда"];
        $pdf->SetFont('dejavusanscondensed','B',6.8);
        $x=$x0;
        foreach($headers as $index=>$label){$this->tableCell($pdf,$x,$y,$widths[$index],$full,$label,'C','M');$x+=$widths[$index];}
        $this->tableCell($pdf,$x,$y,$widths[5]+$widths[6],$top,'Данные по объекту','C','M');
        $this->tableCell($pdf,$x,$y+$top,$widths[5],$lower,'Адрес','C','M');
        $this->tableCell($pdf,$x+$widths[5],$y+$top,$widths[6],$lower,'Подъезд','C','M');
        $x+=$widths[5]+$widths[6];
        foreach([[7,'Рег. номер'],[8,"Дата начала\nработ"],[9,"Дата завершения\nработ"],[10,"Статус\nработника"]] as [$index,$label]){$this->tableCell($pdf,$x,$y,$widths[$index],$full,$label,'C','M');$x+=$widths[$index];}
        $this->tableCell($pdf,$x,$y,$widths[11]+$widths[12],$top,'Данные о закреплённом за объектом и монтажниками производителе работ','C','M');
        $this->tableCell($pdf,$x,$y+$top,$widths[11],$lower,'Должность','C','M');
        $this->tableCell($pdf,$x+$widths[11],$y+$top,$widths[12],$lower,'ФИО','C','M');

        $pdf->SetFont('dejavusanscondensed','',6.8);$y+=$full;$number=1;
        foreach(($input['documentInstallers']??$input['installers']) as $installer){
            $values=[(string)$number++,'Монтажник лифтового оборудования',(string)$installer['fullName'],str_pad((string)$installer['tabId'],6,'0',STR_PAD_LEFT),$this->organizationLabel((string)$input['organizationType']),(string)$object['address'],(string)$object['entrance'],(string)$object['objectRegistrationNumber'],$this->displayDate((string)$object['plannedStartDate']),$this->displayDate((string)$object['plannedFinishDate']),(string)($installer['workStatus']??'Работа'),(string)$engineer['position'],(string)$engineer['fullName']];
            $rowHeight=12.0;
            foreach($values as $index=>$value)$rowHeight=max($rowHeight,$pdf->getNumLines($value,$widths[$index]-2)*3.7+2);
            $x=$x0;foreach($values as $index=>$value){$this->tableCell($pdf,$x,$y,$widths[$index],$rowHeight,$value,'L','T');$x+=$widths[$index];}$y+=$rowHeight;
        }
        $pdf->SetY($y);
    }

    private function tableCell(\TCPDF $pdf,float $x,float $y,float $width,float $height,string $text,string $align,string $verticalAlign):void
    {
        $pdf->SetXY($x,$y);
        $pdf->MultiCell($width,$height,$text,1,$align,false,0,'','',true,0,false,true,$height,$verticalAlign);
    }

    private function loadTcpdf(): void
    {
        if(class_exists(\TCPDF::class))return;
        $autoload=dirname(__DIR__,2).'/vendor/autoload.php';
        if(!is_file($autoload))throw new \RuntimeException('PDF renderer dependency is unavailable.');
        require_once $autoload;
        if(!class_exists(\TCPDF::class))throw new \RuntimeException('PDF renderer dependency is unavailable.');
    }

    private function validateInput(array $input): void
    {
        $object=$input['installationObjectSnapshot']??null;$installers=$input['installers']??null;$documentInstallers=$input['documentInstallers']??$installers;$engineer=$input['controlEngineer']??null;
        $valid=isset($input['assignmentOrderVersion'])&&is_int($input['assignmentOrderVersion'])&&$input['assignmentOrderVersion']>0
            &&$this->isDate($input['assignmentOrderDate']??null)&&in_array($input['organizationType']??null,['individual','brigade'],true)
            &&is_array($object)&&$this->nonblank($object['address']??null)&&$this->nonblank($object['entrance']??null)&&$this->nonblank($object['objectRegistrationNumber']??null)&&$this->isDate($object['plannedStartDate']??null)&&$this->isDate($object['plannedFinishDate']??null)
            &&is_array($installers)&&array_is_list($installers)&&$installers!==[]&&$this->validInstallers($installers)
            &&is_array($documentInstallers)&&array_is_list($documentInstallers)&&$documentInstallers!==[]&&$this->validDocumentInstallers($documentInstallers)
            &&is_array($engineer)&&isset($engineer['userId'])&&is_int($engineer['userId'])&&$engineer['userId']>0&&$this->nonblank($engineer['fullName']??null)&&$this->nonblank($engineer['position']??null);
        if(!$valid)throw new \InvalidArgumentException('Invalid assignment order document input.');
    }
    private function validInstallers(array $installers):bool{foreach($installers as $x)if(!is_array($x)||!isset($x['tabId'])||!is_int($x['tabId'])||$x['tabId']<=0||!$this->nonblank($x['fullName']??null)||!$this->nonblank($x['position']??null))return false;return true;}
    private function validDocumentInstallers(array $installers):bool{if(!$this->validInstallers($installers))return false;foreach($installers as$x)if(isset($x['workStatus'])&&!in_array($x['workStatus'],['Работа','Перемещён'],true))return false;return true;}
    private function nonblank(mixed $v):bool{return is_string($v)&&trim($v)!=='';}
    private function isDate(mixed $v):bool{if(!is_string($v)||preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)!==1)return false;$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d!==false&&$d->format('Y-m-d')===$v;}
    private function displayDate(string $v):string{return \DateTimeImmutable::createFromFormat('!Y-m-d',$v)->format('d.m.Y');}
    private function day(string $v):string{return substr($v,8,2);}
    private function month(string $v):string{return ['01'=>'января','02'=>'февраля','03'=>'марта','04'=>'апреля','05'=>'мая','06'=>'июня','07'=>'июля','08'=>'августа','09'=>'сентября','10'=>'октября','11'=>'ноября','12'=>'декабря'][substr($v,5,2)];}
    private function organizationLabel(string $v):string{return $v==='individual'?'индивидуальная':'бригадная';}
    private function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',true);}
}
