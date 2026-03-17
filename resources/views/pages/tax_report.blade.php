@extends('layouts.app')

@section('content')

<h2>Relatório Fiscal Mensal</h2>

<h4>
    Resultado total:
    <span style="color: {{ $total >= 0 ? 'green' : 'red' }}">
        R$ {{ number_format($total, 2, ',', '.') }}
    </span>
</h4>

<form method="GET">
    <select name="month">
        @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
            </option>
            @endfor
    </select>

    <select name="year">
        @for($y = 2024; $y <= now()->year; $y++)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                {{ $y }}
            </option>
            @endfor
    </select>

    <button type="submit">Filtrar</button>
</form>

<table class="table table-bordered">

    <thead>
        <tr>
            <th>Mês</th>
            <th>Mercado</th>
            <th>Resultado Bruto</th>
            <th>Resultado Líquido</th>
        </tr>
    </thead>

    <tbody>

        @foreach($data as $r)

        <tr>
            <td>{{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</td>

            <td>{{ $r['market'] }}</td>

            <td>
                R$ {{ number_format($r['gross'], 2, ',', '.') }}
            </td>

            <td style="font-weight: bold; color: {{ $r['net'] >= 0 ? 'green' : 'red' }}">
                R$ {{ number_format($r['net'], 2, ',', '.') }}
            </td>
        </tr>

        @endforeach

    </tbody>
    <tfoot>
    <tr>
        <th colspan="2">Total</th>

        <th>
            R$ {{ number_format(collect($data)->sum('gross'), 2, ',', '.') }}
        </th>

        <th style="color: {{ collect($data)->sum('net') >= 0 ? 'green' : 'red' }}">
            R$ {{ number_format(collect($data)->sum('net'), 2, ',', '.') }}
        </th>
    </tr>
</tfoot>

</table>

@endsection