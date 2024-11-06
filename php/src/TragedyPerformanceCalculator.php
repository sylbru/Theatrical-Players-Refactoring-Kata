<?php

declare(strict_types=1);

namespace Theatrical;

class TragedyPerformanceCalculator extends PerformanceCalculator
{
    public function calculateAmount(): int
    {
        $thisAmount = 40000;
        if ($this->performance->audience > 30) {
            $thisAmount += 1000 * ($this->performance->audience - 30);
        }

        return $thisAmount;
    }
}
