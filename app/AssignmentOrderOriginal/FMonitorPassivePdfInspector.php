<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';

final class FMonitorPassivePdfInspector implements AssignmentOrderOriginalPdfInspector{
 private const ACTIVE=['JavaScript','JS','OpenAction','AA','Launch','EmbeddedFiles','Filespec','FileAttachment','RichMedia','Movie','Sound','URI','GoToR','SubmitForm','ImportData'];
 public function __construct(){if((int)ini_get('memory_limit')>0&&(int)ini_get('memory_limit')<256)ini_set('memory_limit','256M');}
 public function algorithmId():string{return'fmonitor-passive-pdf-v1';}
 public function inspect(string$b):AssignmentOrderOriginalPdfInspection{try{return$this->parse($b);}catch(\Throwable){return AssignmentOrderOriginalPdfInspection::invalid();}}
 private function parse(string$b):AssignmentOrderOriginalPdfInspection{
  $len=strlen($b);if($len<20||$len>20971520||!preg_match('/^%PDF-1\.[4-7](?:\r?\n|\r)/D',$b)||!preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/D',$b,$last))return AssignmentOrderOriginalPdfInspection::invalid();
  $direct=$this->direct($b);if($direct===null||!$direct||count($direct)>100001)return AssignmentOrderOriginalPdfInspection::invalid();
  $entries=[];$seen=[];$root=null;$offset=(int)$last[1];$decodedBytes=0;
  for($revision=0;$revision<64;$revision++){
   if($offset<0||$offset>=$len||isset($seen[$offset]))return AssignmentOrderOriginalPdfInspection::invalid();$seen[$offset]=true;
   $section=str_starts_with(substr($b,$offset,4),'xref')?$this->classic($b,$offset):$this->xrefStream($b,$offset,$decodedBytes);if($section===null)return AssignmentOrderOriginalPdfInspection::invalid();if($section['encrypted'])return AssignmentOrderOriginalPdfInspection::unsafe();
   if($root===null&&$section['root']!==null)$root=$section['root'];foreach($section['entries']as$n=>$entry)if(!array_key_exists($n,$entries))$entries[$n]=$entry;
   if($section['prev']===null)break;$offset=$section['prev'];if($revision===63)return AssignmentOrderOriginalPdfInspection::invalid();
  }
  if($root===null||count($entries)>100001)return AssignmentOrderOriginalPdfInspection::invalid();
  $objects=[];$compressed=[];foreach($entries as$n=>$entry){if($entry['type']===0)continue;if($entry['type']===1){$key=$n.' '.$entry['generation'];if(!isset($direct[$key])||$direct[$key]['offset']!==$entry['offset'])return AssignmentOrderOriginalPdfInspection::invalid();$objects[$key]=$direct[$key]['body'];}elseif($entry['type']===2)$compressed[$n]=$entry;else return AssignmentOrderOriginalPdfInspection::invalid();}
  $streams=[];foreach($objects as$key=>$body){if(preg_match('/\/Filter\s*\/LZWDecode\b/',$body))return AssignmentOrderOriginalPdfInspection::invalid();if(preg_match('/\/Type\s*\/ObjStm\b/',$body)){$decoded=$this->objectStream($body,$decodedBytes);if($decoded===null)return AssignmentOrderOriginalPdfInspection::invalid();$streams[(int)explode(' ',$key)[0]]=$decoded;}}
  foreach($compressed as$n=>$entry){if(!isset($streams[$entry['stream']][$entry['index']])||$streams[$entry['stream']][$entry['index']]['number']!==$n)return AssignmentOrderOriginalPdfInspection::invalid();$key=$n.' 0';if(isset($objects[$key]))return AssignmentOrderOriginalPdfInspection::invalid();$objects[$key]=$streams[$entry['stream']][$entry['index']]['body'];}
  if(!isset($objects[$root]))return AssignmentOrderOriginalPdfInspection::invalid();$queue=[[$root,0]];$visited=[];
  while($queue){[$key,$depth]=array_shift($queue);if(isset($visited[$key]))continue;if($depth>100||!isset($objects[$key]))return AssignmentOrderOriginalPdfInspection::invalid();$visited[$key]=true;$body=$this->reachableBody($objects[$key],$decodedBytes);if($body===null)return AssignmentOrderOriginalPdfInspection::invalid();$body=preg_replace_callback('/#([0-9A-Fa-f]{2})/',static fn($m)=>chr(hexdec($m[1])),$body);foreach(self::ACTIVE as$name)if(preg_match('/\/'.$name.'\b/',$body))return AssignmentOrderOriginalPdfInspection::unsafe();if(preg_match_all('/(\d+)\s+(\d+)\s+R\b/',$body,$refs))foreach($refs[1]as$i=>$n)$queue[]=[$n.' '.$refs[2][$i],$depth+1];}
  if(!$this->exactType($objects[$root],'Catalog')||($pagesRoot=$this->oneReference($objects[$root],'Pages'))===null)return AssignmentOrderOriginalPdfInspection::invalid();$treeSeen=[];$pages=$this->pageTree($pagesRoot,null,$objects,$treeSeen,0);return$pages!==null&&$pages>0?AssignmentOrderOriginalPdfInspection::passive():AssignmentOrderOriginalPdfInspection::invalid();
 }
 private function direct(string$b):?array{
  if(!preg_match_all('/(?:^|[\r\n])(\d+)\s+(\d+)\s+obj\s*(.*?)\s*endobj(?=[\r\n]|$)/s',$b,$m,PREG_OFFSET_CAPTURE))return[];$out=[];foreach($m[1]as$i=>$n){$key=$n[0].' '.$m[2][$i][0];if(isset($out[$key]))return null;$out[$key]=['offset'=>$n[1],'body'=>$m[3][$i][0]];}return$out;
 }
 private function classic(string$b,int$offset):?array{
  $tail=substr($b,$offset);if(!preg_match('/^xref(?:\r?\n|\r)(.*?)(?:trailer\s*<<(.*?)>>)/s',$tail,$x))return null;$lines=preg_split('/\r\n|\n|\r/',trim($x[1]));if($lines===false)return null;$entries=[];
  for($line=0;$line<count($lines);){if(!preg_match('/^(\d+)\s+(\d+)$/D',trim($lines[$line++]),$h))return null;$first=(int)$h[1];$count=(int)$h[2];if($count<1||$first+$count>100001)return null;for($i=0;$i<$count;$i++){if($line>=count($lines)||!preg_match('/^(\d{10})\s+(\d{5})\s+([nf])\s*$/D',trim($lines[$line++]),$e))return null;$number=$first+$i;if(isset($entries[$number]))return null;$entries[$number]=$e[3]==='n'?['type'=>1,'offset'=>(int)$e[1],'generation'=>(int)$e[2]]:['type'=>0,'offset'=>(int)$e[1],'generation'=>(int)$e[2]];}}
  return$this->section($entries,$x[2]);
 }
 private function xrefStream(string$b,int$offset,int&$decodedBytes):?array{
  $tail=substr($b,$offset);if(!preg_match('/^(\d+)\s+(\d+)\s+obj\s*<<(.*?)>>\s*stream(?:\r?\n|\r)/s',$tail,$x)||!preg_match('/\/Type\s*\/XRef\b/',$x[3]))return null;
  preg_match_all('/\/Size\b/',$x[3],$sizeKeys);preg_match_all('/\/Size\s+(\d+)\b/',$x[3],$sizes);preg_match_all('/\/W\b/',$x[3],$widthKeys);preg_match_all('/\/W\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s*\]/',$x[3],$widths,PREG_SET_ORDER);preg_match_all('/\/Index\b/',$x[3],$indexKeys);preg_match_all('/\/Index\s*\[([^]]*)\]/',$x[3],$indexes);preg_match_all('/\/Root\b/',$x[3],$rootKeys);preg_match_all('/\/Root\s+(\d+)\s+(\d+)\s+R\b/',$x[3],$roots);if(count($sizeKeys[0])!==1||count($sizes[1])!==1||count($widthKeys[0])!==1||count($widths)!==1||count($indexKeys[0])>1||count($indexes[1])!==count($indexKeys[0])||count($rootKeys[0])!==1||count($roots[1])!==1)return null;$z=[1=>$sizes[1][0]];$w=$widths[0];
  preg_match_all('/\/Length\b/',$x[3],$lengthKeys);preg_match_all('/\/Length\s+(\d+)\b/',$x[3],$lengths);preg_match_all('/\/Filter\b/',$x[3],$filterKeys);preg_match_all('/\/Filter\s*\/([A-Za-z0-9]+)\b/',$x[3],$filters);if(count($lengthKeys[0])!==1||count($lengths[1])!==1||count($filterKeys[0])!==count($filters[1])||count($filters[1])>1||($filters[1]&&$filters[1][0]!=='FlateDecode'))return null;
  $size=(int)$z[1];$width=[(int)$w[1],(int)$w[2],(int)$w[3]];$declared=(int)$lengths[1][0];$start=strlen($x[0]);if($size<1||$size>100001||array_sum($width)<1||max($width)>8||$start+$declared>strlen($tail)||!preg_match('/^(?:\r\n|\n|\r)endstream\s*endobj(?:\r?\n|\r)/D',substr($tail,$start+$declared)))return null;$payload=substr($tail,$start,$declared);
  if($filters[1]){$payload=@gzuncompress($payload,67108865-$decodedBytes);if(!is_string($payload)||!$this->accountDecoded($decodedBytes,strlen($payload)))return null;}
  $ranges=[0,$size];if(preg_match('/\/Index\s*\[([^]]*)\]/',$x[3],$ix)){preg_match_all('/\d+/',$ix[1],$nums);$ranges=array_map('intval',$nums[0]);if(count($ranges)<2||count($ranges)%2)return null;}
  $rows=0;foreach(array_chunk($ranges,2)as[$first,$count]){if($count<1||$first+$count>$size)return null;$rows+=$count;}if(strlen($payload)!==$rows*array_sum($width))return null;
  $entries=[];$cursor=0;foreach(array_chunk($ranges,2)as[$first,$count])for($i=0;$i<$count;$i++){$number=$first+$i;if(isset($entries[$number]))return null;$f=[];foreach($width as$j=>$bytes){$f[$j]=$bytes===0?($j===0?1:0):$this->uint(substr($payload,$cursor,$bytes));$cursor+=$bytes;}if($f[0]===0||$f[0]===1)$entries[$number]=['type'=>$f[0],'offset'=>$f[1],'generation'=>$f[2]];elseif($f[0]===2)$entries[$number]=['type'=>2,'stream'=>$f[1],'index'=>$f[2]];else return null;}
  return$this->section($entries,$x[3]);
 }
 private function section(array$entries,string$dict):array{return['entries'=>$entries,'root'=>preg_match('/\/Root\s+(\d+)\s+(\d+)\s+R/',$dict,$r)?$r[1].' '.$r[2]:null,'prev'=>preg_match('/\/Prev\s+(\d+)/',$dict,$p)?(int)$p[1]:null,'encrypted'=>(bool)preg_match('/\/Encrypt\b/',$dict)];}
 private function objectStream(string$body,int&$decodedBytes):?array{
  if(!preg_match('/^(.*?)stream(?:\r?\n|\r)(.*?)\r?\nendstream\s*$/s',$body,$stream))return null;$dictionary=$stream[1];$payload=$stream[2];preg_match_all('/\/N\s+(\d+)\b/',$dictionary,$ns);preg_match_all('/\/First\s+(\d+)\b/',$dictionary,$firsts);preg_match_all('/\/Length\s+(\d+)\b/',$dictionary,$lengths);preg_match_all('/\/Filter\s*\/(\w+)\b/',$dictionary,$filters);if(count($ns[1])!==1||count($firsts[1])!==1||count($lengths[1])!==1||count($filters[1])>1||strlen($payload)!==(int)$lengths[1][0])return null;$count=(int)$ns[1][0];$first=(int)$firsts[1][0];if($filters[1]&&$filters[1][0]!=='FlateDecode')return null;if($filters[1]){$payload=@gzuncompress($payload,67108865-$decodedBytes);if(!is_string($payload)||!$this->accountDecoded($decodedBytes,strlen($payload)))return null;}if($count<1||$count>100001||$first>strlen($payload))return null;preg_match_all('/(\d+)\s+(\d+)/',substr($payload,0,$first),$pairs);if(count($pairs[1])!==$count)return null;$out=[];foreach($pairs[1]as$i=>$n){$start=$first+(int)$pairs[2][$i];$end=$i+1<$count?$first+(int)$pairs[2][$i+1]:strlen($payload);if($start>$end||$end>strlen($payload))return null;$out[]=['number'=>(int)$n,'body'=>substr($payload,$start,$end-$start)];}return$out;
 }
 private function reachableBody(string$body,int&$decodedBytes):?string{
  if(!preg_match('/\bstream(?:\r?\n|\r)/',$body,$marker,PREG_OFFSET_CAPTURE))return$body;$start=$marker[0][1]+strlen($marker[0][0]);$dictionary=substr($body,0,$marker[0][1]);if(!preg_match('/\/Length\s+(\d+)/',$dictionary,$length))return null;$declared=(int)$length[1];if($declared<0||$start+$declared>strlen($body)||!preg_match('/^(?:\r\n|\n|\r)?endstream\s*$/D',substr($body,$start+$declared)))return null;$payload=substr($body,$start,$declared);if(preg_match('/\/Filter\s*\/(\w+)/',$dictionary,$filter)){if($filter[1]!=='FlateDecode')return null;$payload=@gzuncompress($payload,67108865-$decodedBytes);if(!is_string($payload)||!$this->accountDecoded($decodedBytes,strlen($payload)))return null;}return$dictionary;
 }
 private function exactType(string$body,string$type):bool{preg_match_all('/\/Type\s*\/([A-Za-z0-9]+)\b/',$body,$matches);return count($matches[1])===1&&$matches[1][0]===$type;}
 private function oneReference(string$body,string$name):?string{preg_match_all('/\/'.$name.'\s+(\d+)\s+(\d+)\s+R\b/',$body,$matches);return count($matches[1])===1?$matches[1][0].' '.$matches[2][0]:null;}
 private function pageTree(string$key,?string$parent,array$objects,array&$seen,int$depth):?int{
  if($depth>100||isset($seen[$key])||!isset($objects[$key]))return null;$seen[$key]=true;$body=$objects[$key];
  if($this->exactType($body,'Page'))return$parent!==null&&$this->oneReference($body,'Parent')===$parent?1:null;
  if(!$this->exactType($body,'Pages'))return null;if($parent!==null&&$this->oneReference($body,'Parent')!==$parent)return null;
  preg_match_all('/\/Count\s+(\d+)\b/',$body,$counts);preg_match_all('/\/Kids\s*\[(.*?)\]/s',$body,$kids);if(count($counts[1])!==1||count($kids[1])!==1)return null;
  $content=$kids[1][0];preg_match_all('/(\d+)\s+(\d+)\s+R\b/',$content,$references,PREG_SET_ORDER);$remainder=preg_replace('/(\d+)\s+(\d+)\s+R\b/','',$content);if(trim((string)$remainder)!=='')return null;
  $total=0;foreach($references as$reference){$child=$reference[1].' '.$reference[2];$childPages=$this->pageTree($child,$key,$objects,$seen,$depth+1);if($childPages===null)return null;$total+=$childPages;if($total>100001)return null;}return$total===(int)$counts[1][0]?$total:null;
 }
 private function accountDecoded(int&$total,int$bytes):bool{if($bytes<0||$bytes>67108864-$total)return false;$total+=$bytes;return true;}
 private function uint(string$b):int{$value=0;for($i=0;$i<strlen($b);$i++){if($value>intdiv(PHP_INT_MAX-ord($b[$i]),256))throw new \RuntimeException();$value=$value*256+ord($b[$i]);}return$value;}
}
