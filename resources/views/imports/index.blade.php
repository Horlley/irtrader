@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        <!-- UPLOAD DA NOTA -->

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h5 class="mb-3">
                        📂 Importar Nota de Corretagem
                    </h5>

                    <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Selecione a Nota de Corretagem (PDF)
                            </label>

                            <input type="file" name="file" class="form-control" required>

                        </div>

                        <button class="btn btn-primary w-100">
                            Importar Nota
                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- LISTA DE NOTAS -->

        <div class="col-md-8">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h5 class="mb-3">
                        📑 Notas Importadas
                    </h5>

                    <table id="importsTable" class="table table-striped table-hover nowrap w-100">

                        <thead>

                            <tr>
                                <th></th>
                                <th>Data</th>
                                <th>Nota</th>
                                <th>Corretora</th>
                                <th>Trades</th>
                                <th>Valor Negócios</th>
                                <th>Taxa Reg.</th>
                                <th>Taxas BM&F</th>
                                <th>Custos</th>
                                <th>IRRF</th>
                                <th>Conta Normal</th>
                                <th>Resultado</th>
                                <th>Ações</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach($imports as $import)

                            <tr data-id="{{ $import->id }}">

                                <td></td>

                                <td data-order="{{ \Carbon\Carbon::parse($import->trade_date)->format('Y-m-d') }}">
                                    {{ \Carbon\Carbon::parse($import->trade_date)->format('d/m/Y') }}
                                </td>

                                <td>{{ $import->note_number }}</td>

                                <td>{{ strtoupper($import->broker) }}</td>

                                <td>{{ $import->trades_count }}</td>

                                <td>
                                    R$ {{ number_format($import->gross_value ?? 0,2,',','.') }}
                                </td>

                                <td>
                                    R$ {{ number_format($import->bmf_registration_fee ?? 0,2,',','.') }}
                                </td>

                                <td>
                                    R$ {{ number_format($import->bmf_fees ?? 0,2,',','.') }}
                                </td>

                                <td>
                                    R$ {{ number_format($import->total_costs ?? 0,2,',','.') }}
                                </td>

                                <td>
                                    R$ {{ number_format($import->irrf_daytrade_proj ?? 0,2,',','.') }}
                                </td>

                                <td>
                                    R$ {{ number_format($import->account_normal_total ?? 0,2,',','.') }}
                                </td>

                                <td>

                                    @if(($import->net_total ?? 0) > 0)

                                    <span class="text-success fw-bold">
                                        +{{ number_format($import->net_total,2,',','.') }}
                                    </span>

                                    @elseif(($import->net_total ?? 0) < 0)

                                        <span class="text-danger fw-bold">
                                        {{ number_format($import->net_total,2,',','.') }}
                                        </span>

                                        @else

                                        <span class="text-muted">0</span>

                                        @endif

                                </td>

                                <td>

                                    <button class="btn btn-sm btn-primary btn-show-trades"
                                        data-id="{{ $import->id }}">
                                        Ver
                                    </button>

                                    <form action="/imports/{{ $import->id }}"
                                        method="POST"
                                        class="form-delete"
                                        style="display:inline">

                                        @csrf
                                        @method('DELETE')

                                        <button type="button"
                                            class="btn btn-sm btn-danger btn-delete">
                                            Excluir
                                        </button>

                                    </form>

                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
    $(function() {

        if ($.fn.DataTable.isDataTable('#importsTable')) {
            $('#importsTable').DataTable().destroy();
        }

        $('#importsTable').DataTable({

            responsive: {
                details: {
                    type: 'column',
                    target: 0
                }
            },

            columnDefs: [{
                className: 'dtr-control',
                orderable: false,
                targets: 0
            }],

            order: [
                [1, 'desc']
            ],

            pageLength: 10,

            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
            }

        });

    });


    // MOSTRAR TRADES DA NOTA

    $(document).on('click', '.btn-show-trades', function() {

        let id = $(this).data('id');
        let row = $(this).closest('tr');

        // evita duplicar
        if (row.next().hasClass('details')) {
            row.next().remove();
            return;
        }

        $.get('/imports/' + id + '/trades', function(trades) {

            let html = '<table class="table table-sm table-bordered">';
            html += '<thead><tr>';
            html += '<th>Ativo</th>';
            html += '<th>Lado</th>';
            html += '<th>Qtd</th>';
            html += '<th>Preço</th>';
            html += '</tr></thead><tbody>';

            trades.forEach(function(t) {

                let side = t.side === 'buy' ?
                    '<span class="text-success">Compra</span>' :
                    '<span class="text-danger">Venda</span>';

                html += '<tr>';
                html += '<td>' + t.asset + '</td>';
                html += '<td>' + side + '</td>';
                html += '<td>' + t.quantity + '</td>';
                html += '<td>' + Number(t.price).toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                }) + '</td>';
                html += '</tr>';

            });

            html += '</tbody></table>';

            row.after('<tr class="details"><td colspan="6">' + html + '</td></tr>');

        });

    });

    $(document).on('click', '.btn-delete', function(e) {

        let form = $(this).closest('form');

        Swal.fire({
            title: 'Excluir nota?',
            text: 'Todos os trades dessa nota serão removidos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {

            if (result.isConfirmed) {
                form.submit();
            }

        });

    });
</script>

@endpush