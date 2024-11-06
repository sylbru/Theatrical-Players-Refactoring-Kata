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

    public function calculateAmount(): int
    {
        $thisAmount = 0;

        switch ($this->play->type) {
            case 'tragedy':
                throw new Error("should use polymorphism");

            case 'comedy':
                $thisAmount = 30000;
                if ($this->performance->audience > 20) {
                    $thisAmount += 10000 + 500 * ($this->performance->audience - 20);
                }
                $thisAmount += 300 * $this->performance->audience;
                break;

            default:
                throw new Error("Unknown type: {$this->play->type}");
        }

        return $thisAmount;
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
