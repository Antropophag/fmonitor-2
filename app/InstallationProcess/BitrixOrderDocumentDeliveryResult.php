<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class BitrixOrderDocumentDeliveryResult
{
    public function __construct(public string $kind, public ?string $reason, public array $links) {}
    public static function complete(array $links): self { return new self('complete', null, $links); }
    public static function failed(string $reason): self { return new self('failed', $reason, []); }
}
