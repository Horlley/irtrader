<?php

namespace App\Services\PDF;

use Smalot\PdfParser\Parser;

class PdfReader
{

    public static function read($path)
    {

        $parser = new Parser();

        $pdf = $parser->parseFile(storage_path('app/'.$path));

        $text = $pdf->getText();

        return $text;

    }

}