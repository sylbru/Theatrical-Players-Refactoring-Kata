<?php

declare(strict_types=1);

namespace Theatrical;

use Error;
use NumberFormatter;
use PhpParser\Builder\Enum_;

class StatementPrinter
{
    /**
     * @param array<string, Play> $plays
     */
    public function print(Invoice $invoice, array $plays): string
    {
        $data = $this->prepareStatementData($invoice, $plays);

        return $this->renderStatementPlainText($data);
    }

    /**
     * @param array<string, Play> $plays
     */
    public function printHtml(Invoice $invoice, array $plays): string
    {
        $data = $this->prepareStatementData($invoice, $plays);

        return $this->renderStatementHtml($data);
    }

    private function prepareStatementData(Invoice $invoice, array $plays): \stdClass
    {
        $data = new \stdClass;
        $data->customer = $invoice->customer;
        $data->performances = array_map(fn($performance) => $this->enrichPerformance($performance, $plays), $invoice->performances);
        $data->totalAmount = $this->totalAmount($data->performances, $plays);
        $data->volumeCredits = $this->totalVolumeCredits($data->performances, $plays);

        return $data;
    }

    /** @param Play[] $plays */
    private function renderStatementPlainText(\stdClass $data): string
    {
        $result = "Statement for {$data->customer}\n";

        foreach ($data->performances as $enrichedPerformance) {
            $result .= "  {$enrichedPerformance->play->name}: {$this->asUsd($enrichedPerformance->amount)} ";
            $result .= "({$enrichedPerformance->audience} seats)\n";
        }

        $result .= "Amount owed is {$this->asUsd($data->totalAmount)}\n";
        $result .= "You earned {$data->volumeCredits} credits";

        return $result;
    }


    /** @param Play[] $plays */
    private function renderStatementHtml(\stdClass $data): string
    {
        $result = "<h1>Statement for {$data->customer}</h1>\n";

        $result .= <<<HTML
            <table>
                <thead>
                    <tr>
                        <th>Play</th>
                        <th>Seats</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>

            HTML;

        foreach ($data->performances as $enrichedPerformance) {
            $result .= <<<HTML
                    <tr>
                        <td>{$enrichedPerformance->play->name}</td>
                        <td>{$this->asUsd($enrichedPerformance->amount)}</td>
                        <td>{$enrichedPerformance->audience}</td>
                    <tr>

            HTML;
        }

        $result .= <<<HTML
                </tbody>
            </table>

            HTML;


        $result .= "<p>Amount owed is <em>{$this->asUsd($data->totalAmount)}</em>.</p>\n";
        $result .= "<p>You earned <em>{$data->volumeCredits}</em> credits</p>";

        return $result;
    }

    private function enrichPerformance(Performance $performance, $plays): EnrichedPerformance
    {
        $play = $plays[$performance->playId];
        $enrichedPerformanceWithoutAmount = new EnrichedPerformance($performance->playId, $performance->audience, $play, null);

        return new EnrichedPerformance($performance->playId, $performance->audience, $play, $this->amountFor($enrichedPerformanceWithoutAmount));
    }

    /** @param EnrichedPerformance[] $performances */
    /** @param Play[] $plays */
    private function totalAmount(array $enrichedPerformances, array $plays): int
    {
        $totalAmount = 0;

        foreach ($enrichedPerformances as $enrichedPerformance) {
            $totalAmount += $this->amountFor($enrichedPerformance);
        }

        return $totalAmount;
    }

    /** @param EnrichedPerformance[] $performances */
    /** @param Play[] $plays */
    private function totalVolumeCredits(array $enrichedPerformances, array $plays): float
    {
        $volumeCredits = 0;

        foreach ($enrichedPerformances as $enrichedPerformances) {
            $volumeCredits += $this->volumeCreditsFor($enrichedPerformances);
        }

        return $volumeCredits;
    }

    private function asUsd(int $amount): string
    {
        $format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $format->formatCurrency($amount / 100, 'USD'); // @phpstan-ignore-line
    }

    private function amountFor(EnrichedPerformance $performance): int
    {
        $thisAmount = 0;

        switch ($performance->play->type) {
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
                throw new Error("Unknown type: {$performance->play->type}");
        }

        return $thisAmount;
    }

    private function volumeCreditsFor(EnrichedPerformance $performance): float
    {
        $volumeCredits = max($performance->audience - 30, 0);

        // add extra credit for every ten comedy attendees
        if ($performance->play->type === 'comedy') {
            $volumeCredits += floor($performance->audience / 5);
        }

        return $volumeCredits;
    }
}
