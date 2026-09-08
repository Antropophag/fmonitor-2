<?php
declare(strict_types=1);
[$script,$root,$expectedSha,$expectedSize]=$argv;
$matches=[];$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($iterator as$file){if(!$file->isFile()||$file->isLink())continue;$bytes=file_get_contents($file->getPathname());if($bytes!==false&&hash('sha256',$bytes)===$expectedSha&&strlen($bytes)===(int)$expectedSize)$matches[]=$file->getPathname();}
sort($matches,SORT_STRING);fwrite(STDOUT,json_encode(['count'=>count($matches),'sha256'=>$expectedSha,'byteSize'=>(int)$expectedSize],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n");
