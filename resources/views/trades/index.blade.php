@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="card shadow-sm mb-3">

        <div class="card-body d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                📊 Operações (Trades)
            </h5>

            <a href="/imports" class="btn btn-success">
                📂 Importar Nota
            </a>

        </div>
    </div>

    <div class="row mb-3">

        <div class="col-md-2">
            <select id="filterYear" class="form-select">
                <option value="">Ano</option>
            </select>
        </div>

        <div class="col-md-2">
            <select id="filterMonth" class="form-select">
                <option value="">Mês</option>
                @for($m=1;$m<=12;$m++)
                    <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">
                    {{ str_pad($m,2,'0',STR_PAD_LEFT) }}
                    </option>
                    @endfor
            </select>
        </div>

        <div class="col-md-2">
            <select id="filterSide" class="form-select">
                <option value="">Lado</option>
                <option value="Compra">Compra</option>
                <option value="Venda">Venda</option>
            </select>
        </div>

        <div class="col-md-2">
            <select id="filterMarket" class="form-select">
                <option value="">Mercado</option>
                <option value="dolar">Dólar</option>
                <option value="indice">Índice</option>
                <option value="outros">Outros</option>
            </select>
        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <table id="tradesTable" class="table table-striped table-bordered nowrap w-100">

                <thead>
                    <tr>
                        <th></th>
                        <th>Data</th>
                        <th>Ativo</th>
                        <th>Mercado</th>
                        <th>Tipo</th>
                        <th>Lado</th>
                        <th>Qtd</th>
                        <th>Preço</th>
                        <th>Resultado</th>
                        <th>Corretora</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($trades as $trade)

                    <tr>

                        <td></td>

                        <td>{{ \Carbon\Carbon::parse($trade->trade_date)->format('d/m/Y') }}</td>

                        <td>
                            <strong>{{ $trade->asset }}</strong>
                        </td>

                        <td>{{ ucfirst($trade->market) }}</td>

                        <td>{{ ucfirst($trade->trade_type) }}</td>

                        <td class="side">

                            <span class="d-none">
                                {{ $trade->side == 'buy' ? 'Compra' : 'Venda' }}
                            </span>

                            @if($trade->side == 'buy')
                            <span class="badge bg-success">Compra</span>
                            @else
                            <span class="badge bg-danger">Venda</span>
                            @endif

                        </td>

                        <td>{{ $trade->quantity }}</td>

                        <td>R$ {{ number_format($trade->price,2,',','.') }}</td>

                        <td>

                            @if($trade->result > 0)

                            @if($trade->result > 0)

                            <span class="badge bg-success">
                                +{{ number_format($trade->result,2,',','.') }}
                            </span>

                            @elseif($trade->result < 0)

                                <span class="badge bg-danger">
                                {{ number_format($trade->result,2,',','.') }}
                                </span>

                                @else

                                <span class="badge bg-secondary">0</span>

                                @endif

                                @elseif($trade->result < 0)

                                    <span class="text-danger fw-bold">
                                    {{ number_format($trade->result,2,',','.') }}
                                    </span>

                                    @else

                                    <span class="text-muted">0</span>

                                    @endif

                        </td>

                        <td>{{ $trade->broker }}</td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>
    </div>

</div>

@endsection


@push('scripts')

<script>
    $(function() {

        let table = $('#tradesTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [
                [1, 'desc']
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
            }
        });

        // =========================
        // 🔹 GERAR ANOS AUTOMATICAMENTE
        // =========================
        let years = new Set();

        table.column(1).data().each(function(value) {
            let year = value.split('/')[2];
            years.add(year);
        });

        years.forEach(y => {
            $('#filterYear').append(`<option value="${y}">${y}</option>`);
        });

        // =========================
        // 🔹 FILTROS CUSTOM
        // =========================
        $.fn.dataTable.ext.search.push(function(settings, data) {

            let date = data[1]; // dd/mm/yyyy
            let market = data[3].toLowerCase();
            let side = data[5];

            let year = date.split('/')[2];
            let month = date.split('/')[1];

            let fYear = $('#filterYear').val();
            let fMonth = $('#filterMonth').val();
            let fSide = $('#filterSide').val();
            let fMarket = $('#filterMarket').val();

            if (fYear && year !== fYear) return false;
            if (fMonth && month !== fMonth) return false;
            if (fSide && side !== fSide) return false;
            if (fMarket && market !== fMarket) return false;

            return true;
        });

        // =========================
        // 🔹 EVENTOS
        // =========================
        $('#filterYear, #filterMonth, #filterSide, #filterMarket').on('change', function() {
            table.draw();
        });

    });
</script>

@endpush