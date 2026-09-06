<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\InstallationProcess as I;

final class TemplateGenerationFixture implements C\SelectionClock
{
    public readonly SelectedOriginalFixture $original;
    public string $at='2026-09-05T21:30:00Z';public int $clockCalls=0;public array $inputs=[];public bool $renderFails=false;
    public function __construct()
    {
        $this->original=new SelectedOriginalFixture();
        try {
            $this->original->selection->db->query("UPDATE fm_maintable SET ordadr_address='Москва, Тестовая улица, 1',entrance='2',regnumber='77-000123',workdatestart='2026-10-05',plan_finish_date='2026-12-20'");
            \assertSameValue('selected',$this->original->selection->app()->selectAssignmentOrderComposition(SelectionNativeFixture::command())->status()->value,'real native selection');
            $probe=(new I\ProductionPdfAssignmentOrderRenderer())->renderAssignmentOrder(['assignmentOrderVersion'=>1,'assignmentOrderDate'=>'2026-09-06','organizationType'=>'individual','installationObjectSnapshot'=>['address'=>'Москва, Тестовая улица, 1','entrance'=>'2','objectRegistrationNumber'=>'77-000123','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20'],'installers'=>[['tabId'=>7001,'fullName'=>'Монтажник 7001','position'=>'Монтажник']],'controlEngineer'=>['userId'=>73,'fullName'=>'Инженер теста','position'=>'Инженер строительного контроля']]);
            \assertSameValue(true,str_starts_with($probe[0]['bytes']??'','%PDF-'),'real renderer prerequisite');
            foreach(['Монтажник 7001','Инженер теста','Тестовая улица','сентября','06'] as $marker)self::pdfMarker($probe[0]['bytes'],$marker);
        }catch(\Throwable $e){$this->original->close();throw $e;}
    }
    public function now():C\SelectionInstantLookup {$this->clockCalls++;return C\SelectionInstantLookup::found(new C\SelectionInstant($this->at));}
    public function render(array $input):array {$this->inputs[]=$input;if($this->renderFails)throw new \RuntimeException('Synthetic renderer failure.');return (new I\ProductionPdfAssignmentOrderRenderer())->renderAssignmentOrder($input);}
    public function app(bool $production=false):C\AssignmentOrderTemplateApplication {
        $factory=$production?C\ProductionAssignmentOrderTemplateFactory::class:C\AssignmentOrderTemplateVerificationFactory::class;
        \assertSameValue(true,is_callable([$factory,'create']),'RED_ASSERTION: on-demand template owner missing after real native/renderer setup');
        return $production?$factory::create($this->original->selection->db):$factory::create($this->original->selection->db,'',$this,$this->render(...));
    }
    public function dates():C\AssignmentOrderTemplateDateReader {
        \assertSameValue(true,is_callable([C\ProductionAssignmentOrderTemplateFactory::class,'dateReader']),'date projection public factory');
        return C\ProductionAssignmentOrderTemplateFactory::dateReader($this->original->selection->db);
    }
    public static function pdfMarker(string $pdf,string $text):void {
        preg_match_all('/stream\R(.*?)\Rendstream/s',$pdf,$matches);$needle=bin2hex(mb_convert_encoding($text,'UTF-16BE','UTF-8'));
        foreach($matches[1] as $stream){$decoded=@gzuncompress($stream);if(str_contains(bin2hex(is_string($decoded)?$decoded:$stream),$needle))return;}
        throw new \TestFailure('Real PDF semantic marker missing: '.$text);
    }
    public function close():void {$this->original->close();}
}
