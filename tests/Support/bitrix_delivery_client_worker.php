<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Workforce as W;
$settings=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);$exit=0;
try{
    if(($settings['disabledFunction']??null)!==null&&function_exists($settings['disabledFunction']))throw new RuntimeException();
    $client=W\BitrixWorkforceDeliveryFactory::create(new W\BitrixWorkforceDeliveryConfig(...$settings['config']));
    echo "PHASE ready\n";fflush(STDOUT);$step=0;$prior=[];
    while(true){$read=[STDIN];$write=null;$except=null;if(stream_select($read,$write,$except,15)!==1)throw new RuntimeException();$line=fgets(STDIN);if($line==="stop\n")break;if($line!=="fetch\n")throw new RuntimeException();$step++;
        $result=$client->fetch();foreach($prior as [$old,$rows])if($old->records()!==$rows)throw new RuntimeException();
        if($result->batch!==null){$original=$result->batch->records();$copy=$result->batch->records();if($copy!==[]){$copy[0]['NAME']='changed';$copy[0]['UF_DEPARTMENT'][0]=999;}if($result->batch->records()!==$original)throw new RuntimeException();$prior[]=[$result->batch,$original];}
        file_put_contents($settings['output'].'-'.$step.'.json',json_encode($result->batch?->records(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
        echo 'RESULT '.$step.' '.json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\nPHASE done".$step."\n";fflush(STDOUT);
    }
}catch(Throwable){fwrite(STDERR,"Delivery client worker failed.\n");$exit=70;}
exit($exit);
