@extends('layouts.app')

@section('content')

<div class="container">

    <h3 class="mb-4">
        📊 Relatório para Declaração IR - {{ $year }}
    </h3>

    @foreach($months as $m)

    <div class="card mb-4 shadow-sm">

        <div class="card-header bg-primary text-white">
            Mês: {{ $m['month'] }}/{{ $year }}
        </div>

        <div class="card-body">

            <!-- MERCADO FUTURO (MODELO IRTRADE) -->
            <h6 class="mb-2">📈 Mercado Futuro</h6>

            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        <th>Operações Comuns</th>
                        <th>Day-Trade</th>
                    </tr>
                </thead>
                <tbody>

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

                </tbody>
            </table>

            <!-- RESULTADOS -->
            <h6 class="mt-3">📊 Resultados</h6>

            <table class="table table-sm table-bordered">

                <tr>
                    <th>Resultado Líquido do Mês</th>
                    <td class="{{ $m['result'] >= 0 ? 'text-success' : 'text-danger' }}">
                        R$ {{ number_format($m['result'],2,',','.') }}
                    </td>
                </tr>

                <tr>
                    <th>Resultado Negativo até o Mês Anterior</th>
                    <td>R$ {{ number_format($m['previous_loss'],2,',','.') }}</td>
                </tr>

                <tr>
                    <th>Base de Cálculo do Imposto</th>
                    <td>R$ {{ number_format($m['base'],2,',','.') }}</td>
                </tr>

                <tr>
                    <th>Prejuízo a Compensar</th>
                    <td>R$ {{ number_format($m['loss_carry'],2,',','.') }}</td>
                </tr>

                <tr>
                    <th>Alíquota</th>
                    <td>20%</td>
                </tr>

                <tr>
                    <th>Imposto Devido</th>
                    <td class="text-danger fw-bold">
                        R$ {{ number_format($m['tax'],2,',','.') }}
                    </td>
                </tr>

            </table>

            <!-- CONSOLIDAÇÃO -->
            <h6 class="mt-3">📦 Consolidação do Mês</h6>

            <table class="table table-sm table-bordered">

                <tr>
                    <th>Total do imposto devido</th>
                    <td>R$ {{ number_format($m['tax'],2,',','.') }}</td>
                </tr>

                <tr>
                    <th>IR Fonte no mês</th>
                    <td>R$ {{ number_format($m['irrf_month'],2,',','.') }}</td>
                </tr>

                <tr>
                    <th>IR Fonte meses anteriores</th>
                    <td>
                        R$ {{ number_format($m['irrf_previous'] ?? 0,2,',','.') }}
                    </td>
                </tr>

                <tr>
                    <th>IR Fonte acumulado</th>
                    <td>
                        R$ {{ number_format($m['irrf_balance'],2,',','.') }}
                    </td>
                </tr>

                <tr>
                    <th>Imposto a pagar</th>
                    <td class="fw-bold text-primary">
                        R$ {{ number_format($m['darf'],2,',','.') }}
                    </td>
                </tr>

            </table>

        </div>

    </div>

    @endforeach

</div>

@endsection