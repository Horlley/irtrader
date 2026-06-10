<?php

namespace Tests\Unit;

use App\Services\PDF\B3Parser;
use App\Services\PDF\BrokerDetector;
use App\Services\PDF\BrokerParserFactory;
use PHPUnit\Framework\TestCase;

class B3ParserTest extends TestCase
{
    public function test_parses_clear_b3_note_text()
    {
        $text = $this->clearNoteText();

        $this->assertSame('clear', BrokerDetector::detect($text));
        $this->assertSame(B3Parser::class, BrokerParserFactory::make('clear'));
        $this->assertSame('499883', B3Parser::extractNoteNumber($text));
        $this->assertSame('09/06/2026', B3Parser::extractTradeDate($text));

        $trades = B3Parser::parse($text);

        $this->assertCount(4, $trades);
        $this->assertSame('buy', $trades[0]['side']);
        $this->assertSame('BIT', $trades[0]['asset']);
        $this->assertSame('M26', $trades[0]['contract']);
        $this->assertSame('26/06/2026', $trades[0]['date']);
        $this->assertSame(3, $trades[0]['quantity']);
        $this->assertSame(323460.0, $trades[0]['price']);
        $this->assertSame(-3.45, $trades[0]['result']);

        $this->assertSame('sell', $trades[3]['side']);
        $this->assertSame('WIN', $trades[3]['asset']);
        $this->assertSame(-22.0, $trades[3]['result']);

        $summary = B3Parser::extractSummary($text);

        $this->assertSame(-88.4, $summary['gross_value']);
        $this->assertSame(8.64, $summary['bmf_registration_fee']);
        $this->assertSame(4.78, $summary['bmf_fees']);
        $this->assertSame(-88.4, $summary['daytrade_adjustment']);
        $this->assertSame(13.42, $summary['total_costs']);
        $this->assertSame(-101.82, $summary['account_normal_total']);
        $this->assertSame(-101.82, $summary['net_total']);
    }

    public function test_parses_xp_b3_note_text()
    {
        $text = $this->xpNoteText();

        $this->assertSame('xp', BrokerDetector::detect($text));
        $this->assertSame(B3Parser::class, BrokerParserFactory::make('xp'));
        $this->assertSame('532413', B3Parser::extractNoteNumber($text));
        $this->assertSame('18/03/2026', B3Parser::extractTradeDate($text));

        $trades = B3Parser::parse($text);

        $this->assertCount(4, $trades);
        $this->assertSame('WDO', $trades[0]['asset']);
        $this->assertSame(-50.17, $trades[0]['result']);
        $this->assertSame('sell', $trades[2]['side']);
        $this->assertSame(70.17, $trades[2]['result']);

        $summary = B3Parser::extractSummary($text);

        $this->assertSame(25.0, $summary['gross_value']);
        $this->assertSame(0.2, $summary['irrf_daytrade_proj']);
        $this->assertSame(2.72, $summary['bmf_registration_fee']);
        $this->assertSame(1.48, $summary['bmf_fees']);
        $this->assertSame(25.0, $summary['daytrade_adjustment']);
        $this->assertSame(4.2, $summary['total_costs']);
        $this->assertSame(20.6, $summary['account_normal_total']);
        $this->assertSame(20.6, $summary['net_total']);
    }

    public function test_returns_null_for_unsupported_broker_parser()
    {
        $this->assertNull(BrokerParserFactory::make('rico'));
        $this->assertSame(['xp', 'clear'], BrokerParserFactory::supportedBrokers());
    }

    private function clearNoteText()
    {
        return <<<TXT
Negociacoes
C/V Mercadoria Vencimento Quantidade Preco/Ajuste Tipo Negocio Vlr de Operacao/Ajuste D/C Taxa Operacional
CBIT M26 26/06/2026 3 323.460,00 DAY TRADE 3,45D 0,00
CBIT M26 @26/06/2026 3 323.780,00 DAY TRADE 13,05D 0,00
VWDO N26 01/07/2026 1 5.203,50 DAY TRADE 64,13C 0,00
VWIN M26 17/06/2026 2 170.100,00 DAY TRADE 22,00D 0,00
NOTA DE NEGOCIACAO Nr. nota
499.883
Data pregao
09/06/2026
CLEAR CTVM S/A
Venda disponivel Compra disponivel Venda Opcoes Compra Opcoes Valor dos negocios
0,00 0,00 0,00 0,00 88,40 | D
IRRF IRRF Day Trade (proj.) Taxa operacional Taxa registro BM&F Taxas BM&F (emol+f.gar)
0,00| 0,00 0,00 8,64 4,78 | D
+Outros Custos Impostos Ajuste de posicao Ajuste day trade Total de custos operacionais
0,00 0,00 0,00 | 88,40 | D 13,42 | D
Outros IRRF operacional Total Conta Investimento Total Conta Normal Total liquido (#) Total liquido da nota
0,00 0,00 0,00| 101,82 | D 6,50 | C 101,82 | D
TXT;
    }

    private function xpNoteText()
    {
        return <<<TXT
Negociacoes
C/V Mercadoria Vencimento Quantidade Preco/Ajuste Tipo Negocio Vlr de Operacao/Ajuste D/C Taxa Operacional
CWDO J26 01/04/2026 1 5.243,50 DAY TRADE 50,17D 0,00
CWDO J26 01/04/2026 1 5.217,50 DAY TRADE 209,83C 0,00
VWDO J26 01/04/2026 1 5.245,50 DAY TRADE 70,17C 0,00
VWDO J26 01/04/2026 1 5.218,00 DAY TRADE 204,83D 0,00
NOTA DE NEGOCIACAO Nr. nota
532.413
Data pregao
18/03/2026
XP INVESTIMENTOS CORRETORA DE CAMBIO, TITULOS E VALORES MOBILIARIOS S.A.
Venda disponivel Compra disponivel Venda Opcoes Compra Opcoes Valor dos negocios
0,00 0,00 0,00 0,00 25,00 | C
IRRF IRRF Day Trade (proj.) Taxa operacional Taxa registro BM&F Taxas BM&F (emol+f.gar)
0,00| 0,20 0,00 2,72 1,48 | D
+Outros Custos Impostos Ajuste de posicao Ajuste day trade Total de custos operacionais
0,00 0,00 0,00 | 25,00 | C 4,20 | D
Outros IRRF operacional Total Conta Investimento Total Conta Normal Total liquido (#) Total liquido da nota
0,00 0,00 0,00| 20,60 | C 0,00 | 20,60 | C
TXT;
    }
}
