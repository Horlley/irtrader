<?php

namespace App\Services\PDF;

class BrokerParserFactory
{
    private const PARSERS = [
        'xp' => B3Parser::class,
        'clear' => B3Parser::class,
    ];

    public static function make($broker)
    {
        $broker = strtolower(trim((string) $broker));

        return self::PARSERS[$broker] ?? null;
    }

    public static function supportedBrokers()
    {
        return array_keys(self::PARSERS);
    }
}
