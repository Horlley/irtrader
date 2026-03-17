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

                $quantity = (int) $parts[4];

                $price = str_replace('.', '', $parts[5]);
                $price = str_replace(',', '.', $price);

                // =========================
                // ✅ RESULTADO REAL DA TRADE
                // =========================

                $result = 0;

                // normalmente vem após "DAY TRADE"
                $result = 0;

                for ($i = 0; $i < count($parts) - 1; $i++) {

                    $current = $parts[$i];
                    $next = $parts[$i + 1];

                    // pega padrão: "172,00 C" ou "7,74 D"
                    if (
                        preg_match('/-?\d{1,3}(?:\.\d{3})*,\d{2}/', $current) &&
                        in_array($next, ['C', 'D'])
                    ) {

                        $value = str_replace('.', '', $current);
                        $value = str_replace(',', '.', $value);

                        $result = (float) $value;

                        if ($next === 'D') {
                            $result *= -1;
                        }

                        break;
                    }
                }

                $trades[] = [
                    'side' => $side,
                    'asset' => $asset,
                    'date' => $date,
                    'quantity' => $quantity,
                    'price' => (float) $price,
                    'result' => $result // 🔥 ESSENCIAL
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

            // Valor dos negócios
            if (str_contains($line, 'Valor dos negócios')) {

                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $m)) {
                    $summary['gross_value'] = self::money($m[1]);
                }
            }

            // IRRF e taxas
            if (str_contains($line, 'IRRF Day Trade')) {

                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/\|\s*([0-9\.,]+)/', $values, $m)) {
                    $summary['irrf_daytrade_proj'] = self::money($m[1]);
                }

                if (preg_match('/\s([0-9\.,]+)\s+([0-9\.,]+)\s*\|\s*D/', $values, $m)) {
                    $summary['bmf_registration_fee'] = self::money($m[1]);
                    $summary['bmf_fees'] = self::money($m[2]);
                }
            }

            // Ajuste day trade
            if (str_contains($line, 'Ajuste day trade')) {

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $line, $m)) {

                    $value = self::money($m[1]);

                    if ($m[2] === 'D') {
                        $value = -$value;
                    }

                    $summary['daytrade_adjustment'] = $value;
                }
            }



            // Total líquido da nota
            if (str_contains($line, 'Total líquido da nota')) {

                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $m)) {
                    $summary['net_total'] = self::money($m[1], $m[2]);
                }
            }

            // Total Conta Normal
            if (str_contains($line, 'Total Conta Normal')) {

                $values = trim($lines[$i + 1] ?? '');

                if (preg_match('/([0-9\.,]+)\s*\|\s*([CD])/', $values, $m)) {
                    $summary['account_normal_total'] = self::money($m[1]);
                }
            }
        }

        return $summary;
    }


    public static function extractMarketResults($text)
    {
        $result = [
            'dolar' => 0,
            'indice' => 0
        ];

        $lines = explode("\n", $text);

        foreach ($lines as $line) {

            $line = trim($line);
            $line = preg_replace('/\s+/', ' ', $line);

            // 🔥 DÓLAR (linha específica do resumo)
            if (preg_match('/Mercado futuro.*d[oó]lar.*?(-?\d{1,3}(?:\.\d{3})*,\d{2})$/i', $line, $m)) {
                $result['dolar'] = self::toFloat($m[1]);
            }

            // 🔥 ÍNDICE (linha específica do resumo)
            if (preg_match('/Mercado futuro.*[íi]ndice.*?(-?\d{1,3}(?:\.\d{3})*,\d{2})$/i', $line, $m)) {
                $result['indice'] = self::toFloat($m[1]);
            }
        }

        return $result;
    }

    private static function toFloat($value)
    {
        // remove pontos de milhar e troca vírgula por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private static function money($value, $type = 'C')
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        $number = (float) $value;

        if ($type === 'D') {
            $number = -$number;
        }

        return $number;
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

        if (preg_match('/' . preg_quote($label, '/') . '\s*[\r\n\s]*([0-9\.,]+)/i', $text, $matches)) {

            $value = str_replace('.', '', $matches[1]);
            $value = str_replace(',', '.', $value);

            return (float) $value;
        }

        return 0;
    }
}
