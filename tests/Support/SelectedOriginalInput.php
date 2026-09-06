<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;
final class SelectedOriginalInput implements O\AssignmentOrderOriginalByteStream
{
    public int $reads=0;public int $closes=0;private O\AssignmentOrderOriginalMemoryStream $stream;
    public function __construct(string $bytes){$this->stream=new O\AssignmentOrderOriginalMemoryStream($bytes);}
    public function read(int $maximumBytes):O\AssignmentOrderOriginalStreamRead {$this->reads++;return $this->stream->read($maximumBytes);}
    public function close():void {$this->closes++;$this->stream->close();}
}
