<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
// PILOT-ENTRYPOINT-LOAD-001 v0.2. Real public require seam, isolated PHP children.
$entry=var_export(dirname(__DIR__,2).'/app/PilotHttp/production-entrypoint.php',true);
$core=var_export(dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php',true);
$factory=var_export(dirname(__DIR__,2).'/app/PilotHttp/ProductionPilotHttpEntrypointFactory.php',true);
$load='$entry=require '.$entry.';if(!$entry instanceof FMonitor2\\PilotHttp\\PilotHttpEntrypoint||!is_callable([$entry,"handle"]))exit(12);';
$cases=['clean'=>['',false],'canonical core'=>['require_once '.$core.';',false],
    'canonical factory'=>['require_once '.$factory.';',false],'repeat'=>[$load,false],
    'shadow request'=>['namespace FMonitor2\\PilotHttp { class PilotHttpRequest {} } namespace { ',true],
    'shadow factory'=>['namespace FMonitor2\\PilotHttp { class ProductionPilotHttpEntrypointFactory {} } namespace { ',true]];
$failures=[];
foreach($cases as $name=>[$before,$shadow]){
    $process=null;$pipes=[];
    try{
        $code=$before.$load.'echo "LOAD_OK\n";'.($shadow?'}':'');
        $process=proc_open([PHP_BINARY,'-d','display_errors=0','-d','log_errors=1','-r',$code],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
        assertSameValue(true,is_resource($process),'child started');fclose($pipes[0]);unset($pipes[0]);
        stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$out='';$err='';$deadline=hrtime(true)+5_000_000_000;
        do{
            $read=[$pipes[1],$pipes[2]];$write=null;$except=null;
            if(stream_select($read,$write,$except,0,100000)===false)throw new TestFailure('child pipe observation failed');
            $out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);$status=proc_get_status($process);
        }while($status['running']&&hrtime(true)<$deadline);
        assertSameValue(false,$status['running'],'bounded child completion');
        $out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);$pipes=[];
        $closed=proc_close($process);$process=null;$exit=$status['exitcode']>=0?$status['exitcode']:$closed;
        if($shadow){assertSameValue(true,$exit!==0,'shadow fails closed');assertSameValue('', $out,'no attacker success');assertSameValue(1,preg_match('/Cannot (?:re)?declare class/',$err),'canonical declaration rejects shadow');}
        else{assertSameValue([0,"LOAD_OK\n",''],[$exit,$out,$err],'canonical dependency reuse creates real entrypoint');}
        echo "PASS $name\n";
    }catch(Throwable $e){$failures[]=$name;echo "FAIL $name: ".$e->getMessage()."\n";}
    finally{if(is_resource($process)){proc_terminate($process,9);foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);proc_close($process);}}
}
echo 'PILOT_ENTRYPOINT_LOAD failures='.count($failures)."\n";exit($failures===[]?0:1);
