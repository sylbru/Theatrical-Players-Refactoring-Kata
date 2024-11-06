<?php

declare(strict_types=1);

namespace Theatrical;

readonly class StatementData
{
    /**
     * @param array<EnrichedPerformance> $performances
     */
    public function __construct(
        public string $customer,
        public array $performances,
        public int $totalAmount,
        public float $volumeCredits,
    ) {
    }
}
