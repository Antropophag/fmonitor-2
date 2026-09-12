<?php
declare(strict_types=1);
$target=(string)getenv('FMONITOR_TEST_INCLUDED_FILES_LOG');
if($target!=='')register_shutdown_function(static function()use($target):void{file_put_contents($target,json_encode(array_map(static fn(string$p):string=>str_replace('\\','/',$p),get_included_files()),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",FILE_APPEND|LOCK_EX);});
