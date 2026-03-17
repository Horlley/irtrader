@extends('layouts.app')

@section('content')

<h2>Relatório Fiscal Mensal</h2>

<table class="table table-bordered">

    <thead>

        <tr>

            <th>Mês</th>
            <th>Mercado</th>
            <th>Resultado</th>

        </tr>

    </thead>

    <tbody>

        @foreach($results as $r)

        <tr>

            <td>{{ $r->month }}</td>

            <td>

                @if($r->market=='indice')

                Mercado futuro índice

                @else

                Mercado futuro dólar

                @endif

            </td>

            <td>

                R$ {{ number_format($r->result,2,',','.') }}

            </td>

        </tr>

        @endforeach

    </tbody>

</table>

@endsection