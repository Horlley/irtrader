@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Apuração Mensal de IR</h2>

    <form method="POST" action="{{ route('tax.calculate') }}">

        @csrf

        <button class="btn btn-primary mb-3">
            Recalcular Apuração
        </button>

    </form>

    <table class="table table-bordered">

        <thead>

            <tr>

                <th>Mês</th>
                <th>Resultado</th>
                <th>IRRF</th>
                <th>Base</th>
                <th>Imposto</th>
                <th>DARF</th>

            </tr>

        </thead>

        <tbody>

            @foreach($results as $r)

            <tr>

                <td>{{ $r->month }}</td>

                <td>
                    R$ {{ number_format($r->profit_daytrade,2,',','.') }}
                </td>

                <td>
                    R$ {{ number_format($r->irrf_daytrade,2,',','.') }}
                </td>

                <td>
                    R$ {{ number_format($r->taxable_base,2,',','.') }}
                </td>

                <td>
                    R$ {{ number_format($r->tax_due,2,',','.') }}
                </td>

                <td>
                    R$ {{ number_format($r->darf_due,2,',','.') }}
                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

</div>

@endsection