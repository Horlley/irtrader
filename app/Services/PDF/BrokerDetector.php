<?php

namespace App\Services\PDF;

class BrokerDetector
{

    public static function detect($text)
    {

        $text = strtoupper($text);

        if (str_contains($text, 'XP INVESTIMENTOS')) {
            return 'xp';
        }

        if (str_contains($text, 'CLEAR CORRETORA')) {
            return 'clear';
        }

        if (str_contains($text, 'RICO INVESTIMENTOS')) {
            return 'rico';
        }

        if (str_contains($text, 'BTG PACTUAL')) {
            return 'btg';
        }

        if (str_contains($text, 'INTER DTVM')) {
            return 'inter';
        }

        return 'desconhecida';
    }

}