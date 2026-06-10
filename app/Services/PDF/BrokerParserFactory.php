<?php

namespace App\Services\PDF;

class BrokerParserFactory
{
    public static function make($broker)
    {
        $b3Brokers = [
            'xp',
            'clear',
        ];

        if (in_array($broker, $b3Brokers, true)) {
            return B3Parser::class;
        }

        return null;
    }
}
