<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
final class SelectionHttpAssertions
{
    public const PATH='/pilot/objects/4512/assignment-order/selection';
    public static function field(string $html,string $name):string
    {
        if(preg_match('/<input\b[^>]*name="'.preg_quote($name,'/').'"[^>]*value="([^"]*)"/',$html,$m)!==1)throw new \TestFailure('Missing public form field '.$name);
        return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    }
    public static function submission(string $html,array $choices=[]):string
    {
        $dom=new \DOMDocument();if(!$dom->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING))throw new \TestFailure('Invalid form HTML');
        $xpath=new \DOMXPath($dom);$forms=$xpath->query('//form[@action="'.self::PATH.'"]');
        \assertSameValue(1,$forms->length,'one real selection/retry form');$pairs=[];
        foreach($forms->item(0)->getElementsByTagName('input') as $input){
            if($input->hasAttribute('disabled'))continue;$name=$input->getAttribute('name');if($name==='')continue;
            $type=$input->getAttribute('type');$value=$input->getAttribute('value');
            if(in_array($type,['checkbox','radio'],true)){
                $checked=array_key_exists($name,$choices)?in_array($value,$choices[$name],true):$input->hasAttribute('checked');
                if(!$checked)continue;
            }elseif(in_array($type,['submit','button','file'],true))continue;
            $pairs[]=rawurlencode($name).'='.rawurlencode($value);
        }
        return implode('&',$pairs);
    }
    public static function input(SelectionHttpFixture $f,int $request=1,int $revision=0,int $installer=7001,string $mode='new_order'):array
    { return ['csrfToken'=>$f->csrf,'requestId'=>sprintf('11111111-1111-4111-8111-%012d',$request),'mode'=>$mode,'expectedSelectionRevision'=>(string)$revision,'controlEngineerUserId'=>'73','controlEngineerConfirmed'=>'yes','installerTabIds'=>[(string)$installer]]; }
    public static function body(array $input):string
    {
        $list=$input['installerTabIds']??[];unset($input['installerTabIds']);$body=http_build_query($input,'','&',PHP_QUERY_RFC3986);
        foreach($list as $id)$body.='&installerTabIds%5B%5D='.rawurlencode((string)$id);return $body;
    }
    public static function rowsPreserved(array $before,array $after,array $allowed=[]):void
    { foreach($before as $table=>$rows)if(!in_array($table,$allowed,true))\assertSameValue($rows,$after[$table],"public flow preserves $table"); }
    public static function html(array $r,int $status=200):void
    {
        \assertSameValue($status,$r['status'],'HTTP status');
        \assertSameValue('text/html; charset=UTF-8',$r['headers']['content-type']??null,'HTML media');
        \assertSameValue('no-store',$r['headers']['cache-control']??null,'private response');
        \assertSameValue('nosniff',$r['headers']['x-content-type-options']??null,'nosniff');
    }
}
