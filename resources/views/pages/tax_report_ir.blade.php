@extends('layouts.app')

@section('content')

<div class="container">

    <h3 class="mb-4">
        📊 Relatório IR (Modelo Receita) - {{ $year }}
    </h3>

    @foreach($months as $m)

    <div class="card mb-4 shadow-sm">

        <div class="card-header bg-dark text-white">
            Mês: {{ $m['month'] }}/{{ $year }}
        </div>

        <div class="card-body p-0">

            <table class="table table-bordered table-sm mb-0">

                <!-- HEADER -->
                <thead class="table-dark">
                    <tr>
                        <th>Tipo de Mercado/Ativo</th>
                        <th>Operações Comuns</th>
                        <th>Day-Trade</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- MERCADO VISTA -->
                    <tr class="table-secondary">
                        <td colspan="3"><strong>Mercado à Vista</strong></td>
                    </tr>

                    <tr>
                        <td>Ações</td>
                        <td>-</td>
                        <td>R$ 0,00</td>
                    </tr>

                    <!-- MERCADO OPÇÕES -->
                    <tr class="table-secondary">
                        <td colspan="3"><strong>Mercado Opções</strong></td>
                    </tr>

                    <tr>
                        <td>Opções</td>
                        <td>-</td>
                        <td>R$ 0,00</td>
                    </tr>

                    <!-- MERCADO FUTURO -->
                    <tr class="table-secondary">
                        <td colspan="3"><strong>Mercado Futuro</strong></td>
                    </tr>

                    <tr>
                        <td>Dólar</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['markets']['dolar']['profit'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Índice</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['markets']['indice']['profit'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Outros</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['markets']['outros']['profit'] ?? 0,2,',','.') }}</td>
                    </tr>

                    <!-- TERMO -->
                    <tr class="table-secondary">
                        <td colspan="3"><strong>Mercado a Termo</strong></td>
                    </tr>

                    <tr>
                        <td>Termo</td>
                        <td>-</td>
                        <td>R$ 0,00</td>
                    </tr>

                    <!-- RESULTADOS -->
                    <tr class="table-dark">
                        <td><strong>Resultados</strong></td>
                        <td><strong>Operações Comuns</strong></td>
                        <td><strong>Day-Trade</strong></td>
                    </tr>

                    <tr>
                        <td>Resultado Líquido do Mês</td>
                        <td>-</td>
                        <td class="{{ $m['result'] >= 0 ? 'text-success' : 'text-danger' }}">
                            R$ {{ number_format($m['result'],2,',','.') }}
                        </td>
                    </tr>

                    <tr>
                        <td>Resultado Negativo até o Mês Anterior</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['previous_loss'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Base de Cálculo do Imposto</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['base'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Prejuízo a Compensar</td>
                        <td>-</td>
                        <td>R$ {{ number_format($m['loss_carry'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Alíquota</td>
                        <td>15%</td>
                        <td>20%</td>
                    </tr>

                    <tr>
                        <td>Imposto Devido</td>
                        <td>-</td>
                        <td class="text-danger fw-bold">
                            R$ {{ number_format($m['tax'],2,',','.') }}
                        </td>
                    </tr>

                    <!-- CONSOLIDAÇÃO -->
                    <tr class="table-dark">
                        <td colspan="3"><strong>Consolidação do Mês</strong></td>
                    </tr>

                    <tr>
                        <td>Total do imposto devido</td>
                        <td colspan="2">R$ {{ number_format($m['tax'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>IR Fonte no mês</td>
                        <td colspan="2">R$ {{ number_format($m['irrf_month'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>IR Fonte meses anteriores</td>
                        <td colspan="2">R$ {{ number_format($m['irrf_previous'] ?? 0,2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>IR Fonte acumulado</td>
                        <td colspan="2">R$ {{ number_format($m['irrf_balance'],2,',','.') }}</td>
                    </tr>

                    <tr>
                        <td>Imposto a pagar</td>
                        <td colspan="2" class="fw-bold text-primary">
                            R$ {{ number_format($m['darf'],2,',','.') }}
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

    @endforeach

</div>

@endsection