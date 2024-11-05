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
        $data = new \stdClass;
        $data->customer = $invoice->customer;
        $data->performances = $invoice->performances;

        return $this->renderStatementPlainText($data, $plays);
    }

    /** @param Play[] $plays */
    private function renderStatementPlainText(\stdClass $data, array $plays): string
    {
        $result = "Statement for {$data->customer}\n";

        foreach ($data->performances as $performance) {
            $play = $plays[$performance->playId];
            $thisAmount = $this->amountFor($play, $performance);

            $result .= "  {$play->name}: {$this->asUsd($thisAmount)} ";
            $result .= "({$performance->audience} seats)\n";
        }

        $totalAmount = $this->totalAmount($data->performances, $plays);
        $result .= "Amount owed is {$this->asUsd($totalAmount)}\n";
        $result .= "You earned {$this->totalVolumeCredits($data->performances, $plays)} credits";

        return $result;
    }

    /** @param Performance[] $performances */
    /** @param Play[] $plays */
    private function totalAmount(array $performances, array $plays): int
    {
        $totalAmount = 0;

        foreach ($performances as $performance) {
            $play = $plays[$performance->playId];
            $totalAmount += $this->amountFor($play, $performance);
        }

        return $totalAmount;
    }

    /** @param Performance[] $performances */
    /** @param Play[] $plays */
    private function totalVolumeCredits(array $performances, array $plays): float
    {
        $volumeCredits = 0;

        foreach ($performances as $performance) {
            $play = $plays[$performance->playId];
            $volumeCredits += $this->volumeCreditsFor($performance, $play);
        }

        return $volumeCredits;
    }

    private function asUsd(int $amount): string
    {
        $format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $format->formatCurrency($amount / 100, 'USD'); // @phpstan-ignore-line
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

    private function volumeCreditsFor(Performance $performance, Play $play): float
    {
        $volumeCredits = max($performance->audience - 30, 0);

        // add extra credit for every ten comedy attendees
        if ($play->type === 'comedy') {
            $volumeCredits += floor($performance->audience / 5);
        }

        return $volumeCredits;
    }
}
