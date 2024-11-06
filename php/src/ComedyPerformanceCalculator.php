<?php

declare(strict_types=1);

namespace Theatrical;

class ComedyPerformanceCalculator extends PerformanceCalculator
{
    public function calculateAmount(): int
    {
        $thisAmount = 30000;

        if ($this->performance->audience > 20) {
            $thisAmount += 10000 + 500 * ($this->performance->audience - 20);
        }
        $thisAmount += 300 * $this->performance->audience;

        return $thisAmount;
    }
}
