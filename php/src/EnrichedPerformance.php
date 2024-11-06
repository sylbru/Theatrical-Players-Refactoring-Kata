<?php

declare(strict_types=1);

namespace Theatrical;

class EnrichedPerformance
{
    public function __construct(
        public string $playId,
        public int $audience,
        public ?Play $play,
        public ?int $amount,
    ) {
    }
}
