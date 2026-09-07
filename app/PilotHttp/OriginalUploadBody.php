<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderOriginal as O;
/** Bounded native acquisition; the in-memory stream has no filesystem persistence. */
final class OriginalUploadBody implements O\AssignmentOrderOriginalByteStream
{
    private mixed $memory=null;
    public static function acquire(int $declared):?self
    {
        $input=null;$body=new self();
        try{
            $input=fopen('php://input','rb');$body->memory=fopen('php://memory','w+b');
            if($input===false||$body->memory===false)throw new \RuntimeException();$total=0;
            while(!feof($input)){
                $bytes=fread($input,min(65536,20971521-$total));
                if($bytes===false||($bytes===''&&!feof($input)))throw new \RuntimeException();
                $total+=strlen($bytes);if($total>20971520){$body->close();return null;}
                if(fwrite($body->memory,$bytes)!==strlen($bytes))throw new \RuntimeException();
            }
            if($total!==$declared){$body->close();return null;}
            if(!rewind($body->memory))throw new \RuntimeException();return $body;
        }catch(\Throwable $e){$body->close();throw $e;}
        finally{if(is_resource($input))fclose($input);}
    }
    public function read(int $maximumBytes):O\AssignmentOrderOriginalStreamRead
    {
        if(!is_resource($this->memory)||$maximumBytes<1)return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::FAILED,'');
        $bytes=fread($this->memory,$maximumBytes);
        return new O\AssignmentOrderOriginalStreamRead($bytes===false?O\AssignmentOrderOriginalStreamReadStatus::FAILED:($bytes===''?O\AssignmentOrderOriginalStreamReadStatus::EOF:O\AssignmentOrderOriginalStreamReadStatus::BYTES),$bytes===false?'':$bytes);
    }
    public function close():void { if(is_resource($this->memory))fclose($this->memory);$this->memory=null; }
}
