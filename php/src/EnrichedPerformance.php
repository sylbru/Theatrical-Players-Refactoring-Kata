<?php

declare(strict_types=1);

namespace Theatrical;

class EnrichedPerformance
{
    public function __construct(
        public string $playId,
        public int $audience,
        public ?string $playName,
    ) {
    }
}
