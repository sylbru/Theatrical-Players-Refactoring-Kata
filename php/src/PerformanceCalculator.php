<?php

declare(strict_types=1);

namespace Theatrical;

use Error;

class PerformanceCalculator
{
    public function __construct(
        public readonly Performance $performance,
        public readonly Play $play,
    ) {
    }

    public function calculateVolumeCredits(): float
    {
        $volumeCredits = max($this->performance->audience - 30, 0);

        // add extra credit for every ten comedy attendees
        if ($this->play->type === 'comedy') {
            $volumeCredits += floor($this->performance->audience / 5);
        }

        return $volumeCredits;
    }
}
