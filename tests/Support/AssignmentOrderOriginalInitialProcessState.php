<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class AssignmentOrderOriginalInitialProcessState
{
    /** @var array<string, string> */
    private array $families = [
        'orderCompositionSha256' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'caseSha256' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        'openingSha256' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        'tasksSha256' => 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd',
        'checklistSha256' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
        'decoySha256' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
    ];

    /** @return array<string, string> */
    public function families(): array
    {
        return $this->families;
    }

    public function perturb(string $family): void
    {
        if (!array_key_exists($family, $this->families)) {
            throw new \InvalidArgumentException('Unknown process-state family.');
        }
        $this->families[$family] = str_repeat('9', 64);
    }
}

final readonly class AssignmentOrderOriginalInitialProcessEvidenceReader
{
    public function __construct(private AssignmentOrderOriginalInitialProcessState $state) {}

    public function canonicalJson(): string
    {
        return json_encode(
            ['schema' => 'aoou-process-v1', ...$this->state->families()],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
    }
}
