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

                        <td>
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

        $('#tradesTable').DataTable({

            responsive: true,

            pageLength: 25,

            order: [
                [1, 'desc']
            ],

            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
            },

            dom:

                "<'row mb-3'<'col-md-6'l><'col-md-6'f>>" +

                "<'row'<'col-12'tr>>" +

                "<'row mt-3'<'col-md-5'i><'col-md-7'p>>"

        });

    });
</script>

@endpush