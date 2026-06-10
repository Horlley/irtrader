@extends('layouts.app')

@section('content')

<h2>Relatório Anual IRPF {{ $year }}</h2>
<div class="row g-2 mb-4">

    <!-- RESULTADO ANO -->
    <div class="col">
        <div class="card shadow-sm border-0 text-center p-2">
            <small class="text-muted">Resultado</small>
            <div class="fw-bold"
                style="color: {{ $summary['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">
                R$ {{ number_format($summary['profit'],2,',','.') }}
            </div>
        </div>
    </div>

    <!-- MÉDIA -->
    <div class="col">
        @php $mediaMensal = ($summary['profit'] ?? 0) / 12; @endphp
        <div class="card shadow-sm border-0 text-center p-2">
            <small class="text-muted">Média</small>
            <div class="fw-bold"
                style="color: {{ $mediaMensal >= 0 ? '#2563eb' : '#dc2626' }};">
                R$ {{ number_format($mediaMensal,2,',','.') }}
            </div>
        </div>
    </div>

    <!-- IMPOSTO -->
    <div class="col">
        <div class="card shadow-sm border-0 text-center p-2">
            <small class="text-muted">Imposto</small>
            <div class="fw-bold">
                R$ {{ number_format($summary['tax'],2,',','.') }}
            </div>
        </div>
    </div>

    <!-- IRRF -->
    <div class="col">
        <div class="card shadow-sm border-0 text-center p-2">
            <small class="text-muted">IRRF</small>
            <div class="fw-bold text-purple">
                R$ {{ number_format(end($months)['irrf_balance'] ?? 0,2,',','.') }}
            </div>
        </div>
    </div>

    <!-- PREJUÍZO -->
    <div class="col">
        <div class="card shadow-sm border-0 text-center p-2">
            <small class="text-muted">Prejuízo</small>
            <div class="fw-bold text-danger">
                R$ {{ number_format(end($months)['loss_carry'] ?? 0,2,',','.') }}
            </div>
        </div>
    </div>

</div>

@if(end($months)['loss_carry'] < 0)
    <div class="alert alert-warning">
    Você possui prejuízo acumulado que será utilizado para reduzir impostos futuros.
    </div>
    @endif

    @if(end($months)['irrf_balance'] > 0)
    <div class="alert alert-info">
        Você possui crédito de IRRF disponível para abater no próximo lucro.
    </div>
    @endif

    <table class="table table-bordered table-hover">

        <thead class="table-dark">
            <tr>
                <th>Mês</th>
                <th>Resultado</th>
                <th>Prejuízo Anterior</th>
                <th>Prejuízo a Compensar</th>
                <th>IRRF Mês</th>
                <th>IRRF Usado</th>
                <th>Saldo IRRF</th>
                <th>Imposto</th>
                <th>DARF</th>
            </tr>
        </thead>

        <tbody>

            @php
            $totalResult = 0;
            $totalTax = 0;
            $totalDarf = 0;
            @endphp

            @foreach($months as $m)

            @php
            $result = $m['result'] ?? 0;
            $previousLoss = $m['previous_loss'] ?? 0;
            $lossCarry = $m['loss_carry'] ?? 0;

            $irrfMonth = $m['irrf_month'] ?? 0;
            $irrfUsed = $m['irrf_used'] ?? 0;
            $irrfBalance = $m['irrf_balance'] ?? 0;

            $tax = $m['tax'] ?? 0;
            $darf = $m['darf'] ?? 0;

            $colorResult = $result >= 0 ? '#16a34a' : '#dc2626';

            $totalResult += $result;
            $totalTax += $tax;
            $totalDarf += $darf;
            @endphp

            <tr>
                <td><strong>{{ $year }}-{{ $m['month'] }}</strong></td>

                <td style="color: {{ $colorResult }}; font-weight: bold;">
                    R$ {{ number_format($result, 2, ',', '.') }}
                </td>

                <td>R$ {{ number_format($previousLoss, 2, ',', '.') }}</td>

                <td style="font-weight: bold;">
                    R$ {{ number_format($lossCarry, 2, ',', '.') }}
                </td>

                <td>R$ {{ number_format($irrfMonth, 2, ',', '.') }}</td>

                <td style="color:#2563eb;">
                    R$ {{ number_format($irrfUsed, 2, ',', '.') }}
                </td>

                <td style="color:#9333ea;">
                    R$ {{ number_format($irrfBalance, 2, ',', '.') }}
                </td>

                <td>
                    R$ {{ number_format($tax, 2, ',', '.') }}
                </td>

                <td style="font-weight: bold; color: {{ $darf > 0 ? '#dc2626' : '#16a34a' }};">
                    R$ {{ number_format($darf, 2, ',', '.') }}
                </td>
            </tr>

            @endforeach

        </tbody>

        <tfoot class="table-light">
            <tr>
                <th>Total</th>

                <th style="color: {{ $totalResult >= 0 ? '#16a34a' : '#dc2626' }}">
                    R$ {{ number_format($totalResult, 2, ',', '.') }}
                </th>

                <th colspan="5"></th>

                <th>
                    R$ {{ number_format($totalTax, 2, ',', '.') }}
                </th>

                <th>
                    R$ {{ number_format($totalDarf, 2, ',', '.') }}
                </th>
            </tr>
        </tfoot>

    </table>


    <h4>Resumo do Ano</h4>

    <table class="table table-bordered">

        <tr>
            <td>Lucro Total</td>
            <td>R$ {{ number_format($summary['profit'],2,',','.') }}</td>
        </tr>

        <tr>
            <td>IRRF Retido</td>
            <td>R$ {{ number_format($summary['irrf'],2,',','.') }}</td>
        </tr>

        <tr>
            <td>Imposto Devido</td>
            <td>R$ {{ number_format($summary['tax'],2,',','.') }}</td>
        </tr>

        <tr>
            <td>DARF Gerada</td>
            <td>R$ {{ number_format($summary['darf'],2,',','.') }}</td>
        </tr>

    </table>

    @endsection