<?php

namespace App\Services\PDF;

class XPParser
{

    public static function parse($text)
    {

        $lines = explode("\n", $text);

        $trades = [];

        foreach ($lines as $line) {

            $line = trim($line);

            $line = preg_replace('/\s+/', ' ', $line);

            if (preg_match('/^(C|V)(WIN|WDO)/', $line)) {

                $line = preg_replace('/^(C|V)(WIN|WDO)/', '$1 $2', $line);

                $parts = explode(' ', $line);

                if (count($parts) < 6) {
                    continue;
                }

                $side = $parts[0] === 'C' ? 'buy' : 'sell';

                $asset = $parts[1];

                $date = $parts[3];

                $quantity = $parts[4];

                $price = str_replace('.', '', $parts[5]);
                $price = str_replace(',', '.', $price);

                $trades[] = [
                    'side' => $side,
                    'asset' => $asset,
                    'date' => $date,
                    'quantity' => (int) $quantity,
                    'price' => (float) $price
                ];
            }
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

    return [
        'gross_value' => self::extractValue($text, 'Valor dos negócios'),
        'operational_fee' => self::extractValue($text, 'Taxa operacional'),
        'bmf_registration_fee' => self::extractValue($text, 'Taxa registro BM&F'),
        'bmf_fees' => self::extractValue($text, 'Taxas BM&F'),
        'irrf_daytrade_proj' => self::extractValue($text, 'IRRF Day Trade'),
        'total_costs' => self::extractValue($text, 'Total de custos operacionais'),
        'daytrade_adjustment' => self::extractValue($text, 'Ajuste day trade'),
        'net_total' => self::extractValue($text, 'Total líquido da nota')
    ];

}

    public static function extractTradeDate($text)
    {
        if (preg_match('/Nr\.\s*nota[\s\S]{0,200}?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }


    private static function extractValue($text, $label)
    {

        if (preg_match('/' . preg_quote($label, '/') . '.*?([0-9\.,]+)/i', $text, $matches)) {

            $value = str_replace('.', '', $matches[1]);
            $value = str_replace(',', '.', $value);

            return (float) $value;
        }

        return 0;
    }
}
