@extends('layouts.app')

@section('content')

<h2>DARFs Geradas</h2>

<table class="table table-bordered">

    <thead>

        <tr>

            <th>Mês</th>
            <th>Valor</th>
            <th>Vencimento</th>
            <th>Status</th>

        </tr>

    </thead>

    <tbody>

        @foreach($darfs as $d)

        <tr>

            <td>{{ $d->month }}</td>

            <td>R$ {{ number_format($d->tax_amount,2,',','.') }}</td>

            <td>{{ \Carbon\Carbon::parse($d->due_date)->format('d/m/Y') }}</td>

            <td>{{ $d->status }}</td>

        </tr>

        @endforeach

    </tbody>

</table>

@endsection