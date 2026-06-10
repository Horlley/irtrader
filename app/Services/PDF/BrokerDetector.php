<?php

namespace App\Services\PDF;

class BrokerDetector
{

    public static function detect($text)
    {

        $text = strtoupper($text);

        if (strpos($text, 'XP INVESTIMENTOS') !== false) {
            return 'xp';
        }

        if (strpos($text, 'CLEAR CTVM') !== false || strpos($text, 'CLEAR CORRETORA') !== false || strpos($text, 'CORRETORA.CLEAR') !== false) {
            return 'clear';
        }

        if (strpos($text, 'RICO INVESTIMENTOS') !== false) {
            return 'rico';
        }

        if (strpos($text, 'BTG PACTUAL') !== false) {
            return 'btg';
        }

        if (strpos($text, 'INTER DTVM') !== false) {
            return 'inter';
        }

        return 'desconhecida';
    }

}
