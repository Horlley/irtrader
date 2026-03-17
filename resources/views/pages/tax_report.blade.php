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

        @php
        $selectedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $totalGross = 0;
        $totalNet = 0;
        @endphp

        @foreach($months as $m)

        @if($m['month'] == $selectedMonth)

        {{-- DÓLAR --}}
        <tr>
            <td>{{ $year }}-{{ $m['month'] }}</td>

            <td>Mercado futuro dólar</td>

            <td>
                @php $gross = $m['markets']['dolar']['profit'] ?? 0; @endphp
                R$ {{ number_format($gross, 2, ',', '.') }}
            </td>

            <td style="font-weight: bold; color: {{ ($m['markets']['dolar']['net'] ?? 0) >= 0 ? 'green' : 'red' }}">
                @php $net = $m['markets']['dolar']['net'] ?? 0; @endphp
                R$ {{ number_format($net, 2, ',', '.') }}
            </td>
        </tr>

        @php
        $totalGross += $gross;
        $totalNet += $net;
        @endphp

        {{-- ÍNDICE --}}
        <tr>
            <td>{{ $year }}-{{ $m['month'] }}</td>

            <td>Mercado futuro índice</td>

            <td>
                @php $gross = $m['markets']['indice']['profit'] ?? 0; @endphp
                R$ {{ number_format($gross, 2, ',', '.') }}
            </td>

            <td style="font-weight: bold; color: {{ ($m['markets']['indice']['net'] ?? 0) >= 0 ? 'green' : 'red' }}">
                @php $net = $m['markets']['indice']['net'] ?? 0; @endphp
                R$ {{ number_format($net, 2, ',', '.') }}
            </td>
        </tr>

        @php
        $totalGross += $gross;
        $totalNet += $net;
        @endphp

        {{-- TOTAL --}}
        <tr>
            <td>{{ $year }}-{{ $m['month'] }}</td>

            <td><strong>TOTAL REAL</strong></td>

            <td>
                <strong>
                    R$ {{ number_format($m['result'], 2, ',', '.') }}
                </strong>
            </td>

            <td style="font-weight: bold; color: {{ ($m['result'] - $m['tax']) >= 0 ? 'green' : 'red' }}">
                <strong>
                    R$ {{ number_format($m['result'] - $m['tax'], 2, ',', '.') }}
                </strong>
            </td>
        </tr>

        @endif

        @endforeach

    </tbody>

    <tfoot>
        <tr>
            <th colspan="2">Total</th>

            <th>
                R$ {{ number_format($totalGross, 2, ',', '.') }}
            </th>

            <th style="color: {{ $totalNet >= 0 ? 'green' : 'red' }}">
                R$ {{ number_format($totalNet, 2, ',', '.') }}
            </th>
        </tr>
    </tfoot>

</table>

@endsection