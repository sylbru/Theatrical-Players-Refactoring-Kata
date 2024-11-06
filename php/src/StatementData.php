<?php

namespace Theatrical;

readonly class StatementData
{
    public function __construct(
        public string $customer,
        /** @param array<EnrichedPerformance> $performances */
        public array $performances,
        public int $totalAmount,
        public float $volumeCredits,
    ) {}
}
