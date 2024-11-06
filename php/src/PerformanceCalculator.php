<?php

declare(strict_types=1);

namespace Theatrical;

abstract class PerformanceCalculator
{
    public function __construct(
        public readonly Performance $performance,
        public readonly Play $play,
    ) {
    }

    abstract public function calculateAmount(): int;

    public function calculateVolumeCredits(): float
    {
        return max($this->performance->audience - 30, 0);
    }
}
