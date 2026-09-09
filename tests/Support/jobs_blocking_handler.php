<?php
declare(strict_types=1);
$job=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);file_put_contents(getenv('JOBS_HANDLER_ARRIVAL'),json_encode(['jobId'=>$job['jobId'],'pid'=>getmypid()],JSON_THROW_ON_ERROR),LOCK_EX);$deadline=microtime(true)+10;while(!is_file(getenv('JOBS_HANDLER_RELEASE'))){if(microtime(true)>=$deadline)exit(70);usleep(10000);}echo"{\"status\":\"completed\",\"result\":{\"ok\":true}}\n";
