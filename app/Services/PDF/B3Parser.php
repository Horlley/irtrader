<?php

namespace App\Services\PDF;

class B3Parser
{
    public static function parse($text)
    {
        $lines = explode("\n", $text);
        $trades = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/\s+/', ' ', $line);

            if (!preg_match('/^(C|V)([A-Z]{3,5})\s+([A-Z]\d{2})\s+@?(\d{2}\/\d{2}\/\d{4})\s+(\d+)\s+([0-9\.]+,\d{2})\s+(.+?)\s+(-?\d{1,3}(?:\.\d{3})*,\d{2})([CD])\s+([0-9\.,]+)/', $line, $matches)) {
                continue;
            }

            $trades[] = [
                'side' => $matches[1] === 'C' ? 'buy' : 'sell',
                'asset' => $matches[2],
                'contract' => $matches[3],
                'date' => $matches[4],
                'quantity' => (int) $matches[5],
                'price' => self::money($matches[6]),
                'trade_type' => stripos($matches[7], 'DAY TRADE') !== false ? 'daytrade' : 'normal',
                'result' => self::money($matches[8], $matches[9]),
                'operational_fee' => self::money($matches[10]),
            ];
        }

        return $trades;
    }

    public static function extractNoteNumber($text)
    {
        if (preg_match('/Nr\.\s*nota\s*([0-9\.]+)/i', $text, $matches)) {
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
            $line = trim($lines[$i]);

            if (strpos($line, 'Valor dos negócios') !== false) {
                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $m)) {
                    $summary['gross_value'] = self::money($m[1], $m[2]);
                }
            }

            if (strpos($line, 'IRRF Day Trade') !== false) {
                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/\|\s*([0-9\.,]+)/', $values, $m)) {
                    $summary['irrf_daytrade_proj'] = self::money($m[1]);
                }

                if (preg_match('/\s([0-9\.,]+)\s+([0-9\.,]+)\s*\|\s*D/', $values, $m)) {
                    $summary['bmf_registration_fee'] = self::money($m[1]);
                    $summary['bmf_fees'] = self::money($m[2]);
                }
            }

            if (strpos($line, 'Ajuste day trade') !== false) {
                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $line, $m)) {
                    $summary['daytrade_adjustment'] = self::money($m[1], $m[2]);
                }
            }

            if (strpos($line, 'Total líquido da nota') !== false || strpos($line, 'Total liquido da nota') !== false) {
                $values = trim($lines[$i + 1] ?? '');

                if (preg_match_all('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $matches, PREG_SET_ORDER)) {
                    $last = end($matches);
                    $summary['net_total'] = self::money($last[1], $last[2]);
                }
            }

            if (strpos($line, 'Total Conta Normal') !== false) {
                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $m)) {
                    $summary['account_normal_total'] = self::money($m[1], $m[2]);
                }
            }
        }

        return $summary;
    }

    public static function extractTradeDate($text)
    {
        if (preg_match('/Data pregão\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Nr\.\s*nota[\s\S]{0,200}?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function money($value, $type = 'C')
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        $number = (float) $value;

        if ($type === 'D') {
            $number *= -1;
        }

        return $number;
    }
}
