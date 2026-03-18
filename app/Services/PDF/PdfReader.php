<?php

namespace App\Services\PDF;

use Smalot\PdfParser\Parser;

class PdfReader
{

    public static function read($path)
    {
        $parser = new Parser();

        // 🔥 AGORA RECEBE CAMINHO COMPLETO (public_path)
        if (!file_exists($path)) {
            throw new \Exception("Arquivo não encontrado: " . $path);
        }

        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }
}