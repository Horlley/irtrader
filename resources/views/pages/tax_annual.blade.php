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

        @foreach($months as $m)
        <tr>
            <td>{{ $year }}-{{ $m['month'] }}</td>
            <td>R$ {{ number_format($m['result'],2,',','.') }}</td>
            <td>R$ {{ number_format($m['irrf'],2,',','.') }}</td>
            <td>R$ {{ number_format($m['tax'],2,',','.') }}</td>
            <td>R$ {{ number_format($m['darf'],2,',','.') }}</td>
        </tr>
        @endforeach

    </tbody>

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