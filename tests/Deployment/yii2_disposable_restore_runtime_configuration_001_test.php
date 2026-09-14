<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\RuntimeRestore\{NativeStandProcess,StandRuntimeConfiguration};
$env=['FMONITOR_PROCESS_TABLE_PREFIX'=>'fm2_','FMONITOR_SESSION_STATE_ROOT'=>'/home/fmonitor/.local/state/fmonitor2','FMONITOR_ARTIFACT_STORAGE_ROOT'=>'/home/fmonitor/.local/state/fmonitor2/artifacts','FMONITOR_YII_SESSION_PATH'=>'/home/fmonitor/.local/state/fmonitor2/yii-sessions'];
if(!class_exists(NativeStandProcess::class)){echo "INTENDED_RED: production process implementation is not PSR-4 autoloadable\n";throw new TestFailure('native process autoload missing');}
$native=new NativeStandProcess();try{$local=trim($native->run(['php','-r','echo "LOCAL";']));}catch(\Throwable$error){echo "INTENDED_RED: native process drops inherited executable environment\n";throw$error;}assertSameValue('LOCAL',$local,'native process executes a harmless inherited-PATH command');
putenv('FMONITOR_NATIVE_PROCESS_CANARY=runtime-canary');try{$native->run(['/bin/sh','-c','test "$FMONITOR_NATIVE_PROCESS_CANARY" = runtime-canary']);}catch(\Throwable$error){echo "INTENDED_RED: native process drops inherited runtime environment\n";throw$error;}finally{putenv('FMONITOR_NATIVE_PROCESS_CANARY');}
putenv('FMONITOR_NATIVE_OVERRIDE_CANARY=stale');try{assertSameValue('',$native->run(['/bin/sh','-c','test "$FMONITOR_NATIVE_OVERRIDE_CANARY" = fresh'],'',['FMONITOR_NATIVE_OVERRIDE_CANARY'=>'fresh']),'per-call secret environment overrides inherited value without stdout');}finally{putenv('FMONITOR_NATIVE_OVERRIDE_CANARY');}
if(!class_exists(StandRuntimeConfiguration::class)){echo "INTENDED_RED: canonical stand runtime configuration is missing\n";throw new TestFailure('canonical configuration missing');}
$config=StandRuntimeConfiguration::fromEnvironment($env);
assertSameValue(['jobs'=>'fm2_fm2_jobs','jobEvents'=>'fm2_fm2_job_events','outboxIntents'=>'fm2_fm2_outbox_intents','outboxAttempts'=>'fm2_fm2_outbox_attempt_events','workerHeartbeats'=>'fm2_fm2_worker_heartbeats'],$config->jobsTables(),'logical jobs names receive the canonical prefix exactly once');
assertSameValue('artifacts',$config->artifactVolumePath(),'artifact path is relative to compose-owned state mount');
assertSameValue('yii-sessions',$config->sessionVolumePath(),'Yii session path is relative to compose-owned state mount');
foreach([array_replace($env,['FMONITOR_PROCESS_TABLE_PREFIX'=>'bad-prefix']),array_replace($env,['FMONITOR_ARTIFACT_STORAGE_ROOT'=>'/outside/artifacts']),array_replace($env,['FMONITOR_YII_SESSION_PATH'=>'/home/fmonitor/.local/state/fmonitor2/artifacts'])]as$bad){try{StandRuntimeConfiguration::fromEnvironment($bad);throw new TestFailure('invalid canonical runtime config accepted');}catch(\InvalidArgumentException){}}
echo "YII2_DISPOSABLE_RESTORE_RUNTIME_CONFIGURATION_001_OK\n";
