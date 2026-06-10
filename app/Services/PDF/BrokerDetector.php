<?php

namespace App\Services\PDF;

class BrokerDetector
{
    private const BROKER_SIGNATURES = [
        'xp' => [
            'XP INVESTIMENTOS',
            'XP CORRETORA',
            'XP CCTVM',
            'XP C C T V M',
        ],
        'clear' => [
            'CLEAR CTVM',
            'CLEAR CTVM S/A',
            'CLEAR C T V M',
            'CLEAR CORRETORA',
            'CORRETORA CLEAR',
        ],
        'rico' => [
            'RICO INVESTIMENTOS',
            'RICO CTVM',
            'RICO C T V M',
        ],
        'btg' => [
            'BTG PACTUAL',
        ],
        'inter' => [
            'INTER DTVM',
            'INTER D T V M',
            'BANCO INTER',
        ],
    ];

    public static function detect($text)
    {
        $text = self::normalize($text);

        foreach (self::BROKER_SIGNATURES as $broker => $signatures) {
            foreach ($signatures as $signature) {
                if (strpos($text, $signature) !== false) {
                    return $broker;
                }
            }
        }

        return 'desconhecida';
    }

    private static function normalize($text)
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted !== false) {
            $text = $converted;
        }

        $text = strtoupper($text);
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
