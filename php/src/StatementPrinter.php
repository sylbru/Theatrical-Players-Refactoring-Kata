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

    /**
     * @param array<Play> $plays
     */
    private function prepareStatementData(Invoice $invoice, array $plays): StatementData
    {
        $enrichedPerformances = array_map(fn ($performance) => $this->enrichPerformance($performance, $plays), $invoice->performances);

        return new StatementData(
            customer: $invoice->customer,
            performances: $enrichedPerformances,
            totalAmount: $this->totalAmount($enrichedPerformances),
            volumeCredits: $this->totalVolumeCredits($enrichedPerformances),
        );
    }

    private function renderStatementPlainText(StatementData $data): string
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

    private function renderStatementHtml(StatementData $data): string
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

    /**
     * @param array<Play> $plays
     */
    private function enrichPerformance(Performance $performance, $plays): EnrichedPerformance
    {
        $calculator = $this->createPerformanceCalculator($performance, $plays[$performance->playId]);

        return new EnrichedPerformance(
            $performance->playId,
            $performance->audience,
            $calculator->play,
            $calculator->calculateAmount(),
            $calculator->calculateVolumeCredits(),
        );
    }

    /**
     * @param array<EnrichedPerformance> $enrichedPerformances
     */
    private function totalAmount(array $enrichedPerformances): int
    {
        return array_reduce(
            $enrichedPerformances,
            fn ($carry, $item) => $carry
                + $this->createPerformanceCalculator($item->toSimplePerformance(), $item->play)
                    ->calculateAmount(),
            0,
        );
    }

    /**
     * @param array<EnrichedPerformance> $enrichedPerformances
     */
    private function totalVolumeCredits(array $enrichedPerformances): float
    {
        return array_reduce(
            $enrichedPerformances,
            fn ($carry, $item) => $carry
                + $this->createPerformanceCalculator($item->toSimplePerformance(), $item->play)
                    ->calculateVolumeCredits(),
            initial: 0,
        );
    }

    private function createPerformanceCalculator(Performance $performance, Play $play): PerformanceCalculator
    {
        return match ($play->type) {
            'comedy' => (new ComedyPerformanceCalculator($performance, $play)),
            'tragedy' => (new TragedyPerformanceCalculator($performance, $play)),
            default => throw new Error("Unknown play type: {$play->type}"),
        };
    }

    private function asUsd(int $amount): string
    {
        $format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $format->formatCurrency($amount / 100, 'USD'); // @phpstan-ignore-line
    }
}
