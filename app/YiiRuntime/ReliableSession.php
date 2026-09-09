<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;
final class ReliableSession extends \yii\web\Session
{
    public function close():void
    {
        if($this->getIsActive()&&!session_write_close())throw new \RuntimeException('Session persistence unavailable.');
        parent::close();
    }
}
