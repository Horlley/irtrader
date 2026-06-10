<?php

namespace App\Services\PDF;

class B3Parser
{
    private const MONEY_REGEX = '-?\d+(?:\.\d{3})*,\d{2}';

    public static function parse($text)
    {
        $lines = explode("\n", $text);
        $trades = [];

        foreach ($lines as $line) {
            $trade = self::parseTradeLine($line);

            if ($trade) {
                $trades[] = $trade;
            }
        }

        return $trades;
    }

    public static function extractNoteNumber($text)
    {
        $text = self::normalizeSearchText($text);

        if (preg_match('/(?:NR\.?\s*NOTA|NUMERO\s+(?:DA\s+)?NOTA|NRO\.?\s*NOTA)\s*([0-9\.]+)/', $text, $matches)) {
            return str_replace('.', '', $matches[1]);
        }

        return null;
    }

    public static function extractSummary($text)
    {
        $summary = [
            'gross_value' => 0,
            'operational_fee' => 0,
            'bmf_registration_fee' => 0,
            'bmf_fees' => 0,
            'irrf_daytrade_proj' => 0,
            'total_costs' => 0,
            'daytrade_adjustment' => 0,
            'net_total' => 0,
            'account_normal_total' => 0,
        ];

        $lines = explode("\n", $text);

        for ($i = 0; $i < count($lines); $i++) {
            $line = self::normalizeSearchText($lines[$i]);
            $values = self::summaryValueLine($lines, $i);

            if (strpos($line, 'VALOR DOS NEG') !== false) {
                $pairs = self::debitCreditValues($values);

                if ($pairs) {
                    $pair = end($pairs);
                    $summary['gross_value'] = self::money($pair['value'], $pair['type']);
                }
            }

            if (strpos($line, 'IRRF DAY TRADE') !== false) {
                $amounts = self::moneyValues($values);

                if (isset($amounts[1])) {
                    $summary['irrf_daytrade_proj'] = $amounts[1];
                }

                if (isset($amounts[2])) {
                    $summary['operational_fee'] = $amounts[2];
                }

                if (isset($amounts[3])) {
                    $summary['bmf_registration_fee'] = $amounts[3];
                }

                if (isset($amounts[4])) {
                    $summary['bmf_fees'] = $amounts[4];
                }
            }

            if (strpos($line, 'AJUSTE DAY TRADE') !== false || strpos($line, 'TOTAL DE CUSTOS OPERACIONAIS') !== false) {
                $pairs = self::debitCreditValues($values);

                if (count($pairs) >= 2) {
                    $daytrade = $pairs[count($pairs) - 2];
                    $costs = $pairs[count($pairs) - 1];

                    $summary['daytrade_adjustment'] = self::money($daytrade['value'], $daytrade['type']);
                    $summary['total_costs'] = abs(self::money($costs['value'], $costs['type']));
                }
            }

            if (strpos($line, 'TOTAL L') !== false && strpos($line, 'DA NOTA') !== false) {
                $pairs = self::debitCreditValues($values);

                if ($pairs) {
                    $last = end($pairs);
                    $summary['net_total'] = self::money($last['value'], $last['type']);
                }
            }

            if (strpos($line, 'TOTAL CONTA NORMAL') !== false) {
                $pairs = self::debitCreditValues($values);

                if ($pairs) {
                    $first = reset($pairs);
                    $summary['account_normal_total'] = self::money($first['value'], $first['type']);
                }
            }
        }

        return $summary;
    }

    public static function extractTradeDate($text)
    {
        $text = self::normalizeSearchText($text);

        if (preg_match('/DATA\s+PREG\S*\s*(\d{2}\/\d{2}\/\d{4})/', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/NR\.?\s*NOTA.{0,200}?(\d{2}\/\d{2}\/\d{4})/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function parseTradeLine($line)
    {
        $line = self::normalizeLine($line);

        if ($line === '') {
            return null;
        }

        $pattern = '/^(C|V)\s*([A-Z]{3,6})\s+([A-Z]\d{2})\s+@?(\d{2}\/\d{2}\/\d{4})\s+(\d+)\s+('
            . self::MONEY_REGEX
            . ')\s+(.+?)\s+('
            . self::MONEY_REGEX
            . ')\s*([CD])(?:\s+('
            . self::MONEY_REGEX
            . '))?$/i';

        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $tradeType = str_replace(' ', '', self::normalizeSearchText($matches[7]));

        return [
            'side' => strtoupper($matches[1]) === 'C' ? 'buy' : 'sell',
            'asset' => strtoupper($matches[2]),
            'contract' => strtoupper($matches[3]),
            'date' => $matches[4],
            'quantity' => (int) $matches[5],
            'price' => self::money($matches[6]),
            'trade_type' => strpos($tradeType, 'DAYTRADE') !== false ? 'daytrade' : 'normal',
            'result' => self::money($matches[8], strtoupper($matches[9])),
            'operational_fee' => isset($matches[10]) ? self::money($matches[10]) : 0,
        ];
    }

    private static function money($value, $type = 'C')
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        $number = (float) $value;

        if (strtoupper($type) === 'D') {
            $number *= -1;
        }

        return $number;
    }

    private static function normalizeLine($line)
    {
        return trim(preg_replace('/\s+/', ' ', $line));
    }

    private static function normalizeSearchText($text)
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted !== false) {
            $text = $converted;
        }

        $text = strtoupper($text);

        return self::normalizeLine($text);
    }

    private static function summaryValueLine(array $lines, $index)
    {
        $current = self::normalizeLine($lines[$index] ?? '');
        $next = self::normalizeLine($lines[$index + 1] ?? '');

        if (preg_match('/' . self::MONEY_REGEX . '/', $current)) {
            return trim($current . ' ' . $next);
        }

        return $next;
    }

    private static function debitCreditValues($text)
    {
        $values = [];

        if (!preg_match_all('/(' . self::MONEY_REGEX . ')\s*(?:\|\s*)?([CD])\b/i', $text, $matches, PREG_SET_ORDER)) {
            return $values;
        }

        foreach ($matches as $match) {
            $values[] = [
                'value' => $match[1],
                'type' => strtoupper($match[2]),
            ];
        }

        return $values;
    }

    private static function moneyValues($text)
    {
        if (!preg_match_all('/' . self::MONEY_REGEX . '/', $text, $matches)) {
            return [];
        }

        return array_map(function ($value) {
            return self::money($value);
        }, $matches[0]);
    }
}
