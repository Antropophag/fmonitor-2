<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final readonly class SmtpConfiguration
{
    public const MAX_MESSAGE_BYTES = 5_242_880;
    private function __construct(
        public string $runtimeEnv, public string $host, public int $port,
        public string $encryption, public string $username, private string $password,
        public string $fromAddress, public string $fromName, public int $timeoutSeconds,
        public bool $verifyPeer, public string $publicBaseUrl, public ?string $testRecipient,
    ) {}

    public static function fromEnvironment(array $environment): self
    {
        try {
            $value = static function(string $key) use ($environment): string {
                $candidate=$environment[$key]??null;
                if(!is_string($candidate)||trim($candidate)==='')throw new \RuntimeException();
                return trim($candidate);
            };
            $runtime=$value('FMONITOR_RUNTIME_ENV');$host=$value('FMONITOR_SMTP_HOST');
            $port=filter_var($value('FMONITOR_SMTP_PORT'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
            $username=$value('FMONITOR_SMTP_USERNAME');$password=$value('FMONITOR_SMTP_PASSWORD');
            $from=$value('FMONITOR_SMTP_FROM_ADDRESS');$name=$value('FMONITOR_SMTP_FROM_NAME');
            $timeout=filter_var($value('FMONITOR_SMTP_TIMEOUT_SECONDS'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>120]]);
            $base=$value('FMONITOR_PUBLIC_BASE_URL');
            $testValue=$environment['FMONITOR_SMTP_TEST_RECIPIENT']??null;
            $test=is_string($testValue)&&trim($testValue)!==''?trim($testValue):null;
            $invalid=$port===false||$timeout===false
                ||$value('FMONITOR_SMTP_ENCRYPTION')!=='tls'
                ||$value('FMONITOR_SMTP_VERIFY_PEER')!=='true'
                ||!hash_equals($username,$from)
                ||filter_var($from,FILTER_VALIDATE_EMAIL)===false
                ||filter_var($base,FILTER_VALIDATE_URL)===false
                ||!str_starts_with($base,'https://')
                ||($test!==null&&filter_var($test,FILTER_VALIDATE_EMAIL)===false)
                ||($runtime==='production'&&$test!==null);
            if($invalid)throw new \RuntimeException();
            return new self($runtime,$host,(int)$port,'tls',$username,$password,$from,$name,(int)$timeout,true,rtrim($base,'/'),$test);
        } catch (\Throwable) { throw new \RuntimeException('SMTP_CONFIGURATION_INVALID'); }
    }

    public function password(): string { return $this->password; }
    public function safeValues(): array
    {
        return ['host'=>$this->host,'port'=>$this->port,'encryption'=>$this->encryption,'username'=>$this->username,
            'fromName'=>$this->fromName,'timeoutSeconds'=>$this->timeoutSeconds,'verifyPeer'=>$this->verifyPeer,
            'publicBaseUrl'=>$this->publicBaseUrl,'testRecipient'=>$this->testRecipient];
    }
}
