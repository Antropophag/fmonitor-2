<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionStorageTypes.php';
enum PilotSessionInspectionStatus:string{case OK='ok';case UNAVAILABLE='unavailable';}
interface PilotSessionStorageInspection{public function inspect(PilotSessionStorageConfig$config,PilotSessionFilesystemPrimitives$filesystem):PilotSessionInspectionResult;}
interface PilotSessionInspectorCliArguments{public function values():array;}
interface PilotSessionInspectorCliOutput{public function writeStdout(string$bytes):void;public function writeStderr(string$bytes):void;}
final readonly class PilotSessionInspectionResult{private function __construct(private PilotSessionInspectionStatus$status,private ?string$json){}public static function inspectorOk(string$json):self{json_decode($json,true,512,JSON_THROW_ON_ERROR);return new self(PilotSessionInspectionStatus::OK,$json);}public static function inspectorUnavailable():self{return new self(PilotSessionInspectionStatus::UNAVAILABLE,null);}public function status():PilotSessionInspectionStatus{return$this->status;}public function canonicalJson():?string{return$this->json;}}
