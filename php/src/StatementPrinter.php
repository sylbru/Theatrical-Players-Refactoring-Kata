<?php

declare(strict_types=1);

namespace Theatrical;

use Error;
use NumberFormatter;

class StatementPrinter
{
    /**
     * @param array<string, Play> $plays
     */
    public function print(Invoice $invoice, array $plays): string
    {
        return $this->renderStatementPlainText($invoice, $plays);
    }

    /** @param Play[] $plays */
    private function renderStatementPlainText(Invoice $invoice, array $plays): string
    {
        $result = "Statement for {$invoice->customer}\n";

        foreach ($invoice->performances as $performance) {
            $play = $plays[$performance->playId];
            $thisAmount = $this->amountFor($play, $performance);

            $result .= "  {$play->name}: {$this->asUsd($thisAmount)} ";
            $result .= "({$performance->audience} seats)\n";
        }

        $totalAmount = $this->totalAmount($invoice, $plays);
        $result .= "Amount owed is {$this->asUsd($totalAmount)}\n";
        $result .= "You earned {$this->totalVolumeCredits($invoice, $plays)} credits";

        return $result;
    }

    /** @param Play[] $plays */
    private function totalAmount(Invoice $invoice, array $plays): int
    {
        $totalAmount = 0;

        foreach ($invoice->performances as $performance) {
            $play = $plays[$performance->playId];
            $totalAmount += $this->amountFor($play, $performance);
        }

        return $totalAmount;
    }

    /** @param Play[] $plays */
    private function totalVolumeCredits(Invoice $invoice, array $plays): float
    {
        $volumeCredits = 0;

        foreach ($invoice->performances as $performance) {
            $play = $plays[$performance->playId];
            $volumeCredits += $this->volumeCreditsFor($performance, $play);
        }

        return $volumeCredits;
    }


    private function asUsd(int $amount): string
    {
        $format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $format->formatCurrency($amount / 100, 'USD');
    }

    private function amountFor(Play $play, Performance $performance): int
    {
        $thisAmount = 0;

        switch ($play->type) {
            case 'tragedy':
                $thisAmount = 40000;
                if ($performance->audience > 30) {
                    $thisAmount += 1000 * ($performance->audience - 30);
                }
                break;

            case 'comedy':
                $thisAmount = 30000;
                if ($performance->audience > 20) {
                    $thisAmount += 10000 + 500 * ($performance->audience - 20);
                }
                $thisAmount += 300 * $performance->audience;
                break;

            default:
                throw new Error("Unknown type: {$play->type}");
        }

        return $thisAmount;
    }

    private function volumeCreditsFor($performance, $play): float
    {
        $volumeCredits = max($performance->audience - 30, 0);

        // add extra credit for every ten comedy attendees
        if ($play->type === 'comedy') {
            $volumeCredits += floor($performance->audience / 5);
        }

        return $volumeCredits;
    }
}
