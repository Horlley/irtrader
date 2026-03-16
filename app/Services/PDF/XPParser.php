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

            // normaliza espaços
            $line = preg_replace('/\s+/', ' ', $line);

            // detecta linha de trade
            if (preg_match('/^(C|V)(WIN|WDO)/', $line)) {

                // separa CWIN -> C WIN
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
}
