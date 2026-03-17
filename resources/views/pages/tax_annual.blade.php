@extends('layouts.app')

@section('content')

<h2>Relatório Anual IRPF {{ $year }}</h2>

<table class="table table-bordered">

    <thead>

        <tr>

            <th>Mês</th>
            <th>Resultado</th>
            <th>IRRF</th>
            <th>Imposto</th>
            <th>DARF</th>

        </tr>

    </thead>

    <tbody>

        @foreach($results as $r)

        <tr>

            <td>{{ $r->month }}</td>

            <td>R$ {{ number_format($r->profit_daytrade,2,',','.') }}</td>

            <td>R$ {{ number_format($r->irrf_daytrade,2,',','.') }}</td>

            <td>R$ {{ number_format($r->tax_due,2,',','.') }}</td>

            <td>R$ {{ number_format($r->darf_due,2,',','.') }}</td>

        </tr>

        @endforeach

    </tbody>

</table>


<h4>Resumo do Ano</h4>

<table class="table table-bordered">

    <tr>
        <td>Lucro Total</td>
        <td>R$ {{ number_format($totals->total_profit,2,',','.') }}</td>
    </tr>

    <tr>
        <td>IRRF Retido</td>
        <td>R$ {{ number_format($totals->total_irrf,2,',','.') }}</td>
    </tr>

    <tr>
        <td>Imposto Devido</td>
        <td>R$ {{ number_format($totals->total_tax,2,',','.') }}</td>
    </tr>

    <tr>
        <td>DARF Gerada</td>
        <td>R$ {{ number_format($totals->total_darf,2,',','.') }}</td>
    </tr>

</table>

@endsection