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

                    <form method="GET" id="filterForm">
                        <div class="row mb-3">

                            <div class="col-md-2">
                                <select name="year" class="form-select" onchange="this.form.submit()">
                                    <option value="">Ano</option>
                                    @foreach($years as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select name="month" class="form-select" onchange="this.form.submit()">
                                    <option value="">Mês</option>
                                    @for($m=1;$m<=12;$m++)
                                        <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}"
                                        {{ request('month') == str_pad($m,2,'0',STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ str_pad($m,2,'0',STR_PAD_LEFT) }}
                                        </option>
                                        @endfor
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="broker" class="form-select" onchange="this.form.submit()">
                                    <option value="">Corretora</option>
                                    @foreach($brokers as $b)
                                    <option value="{{ $b }}" {{ request('broker') == $b ? 'selected' : '' }}>
                                        {{ $b }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <!-- 🔥 continua frontend -->
                                <input type="text" id="filterNote" class="form-control" placeholder="Número da nota">
                            </div>

                        </div>
                    </form>

                    <div class="row mb-4">

                        <!-- SALDO -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 text-center h-100">
                                <div class="card-body">
                                    <small class="text-muted">Saldo Líquido</small>
                                    <h4 id="totalResult" class="fw-bold mb-0">R$ 0,00</h4>
                                </div>
                            </div>
                        </div>

                        <!-- LUCRO -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 text-center h-100">
                                <div class="card-body">
                                    <small class="text-muted">Lucro</small>
                                    <h4 id="totalProfit" class="fw-bold text-success mb-0">R$ 0,00</h4>
                                </div>
                            </div>
                        </div>

                        <!-- PREJUÍZO -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 text-center h-100">
                                <div class="card-body">
                                    <small class="text-muted">Prejuízo</small>
                                    <h4 id="totalLoss" class="fw-bold text-danger mb-0">R$ 0,00</h4>
                                </div>
                            </div>
                        </div>

                        <!-- ESPAÇO EXTRA (futuro KPI) -->
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 text-center h-100">
                                <div class="card-body">
                                    <small class="text-muted">Trades</small>
                                    <h4 id="totalTrades" class="fw-bold mb-0">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- GRÁFICO -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-muted">Evolução do Resultado</h6>
                            </div>

                            <div style="height:350px; background:#fff;">
                                <canvas id="chart"></canvas>
                            </div>


                        </div>
                    </div>

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

                                    <form action="{{ route('imports.destroy', $import->id) }}"
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

<style>
    .chart-container {
        position: relative;
        height: 320px;
        width: 100%;
    }
</style>

@endsection


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    $(function() {

        if ($.fn.DataTable.isDataTable('#importsTable')) {
            $('#importsTable').DataTable().destroy();
        }

        let table = $('#importsTable').DataTable({

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

        let years = new Set();

        $('#importsTable tbody tr').each(function() {
            let date = $(this).find('td:eq(1)').data('order');
            if (!date) return;

            years.add(date.split('-')[0]);
        });

        years.forEach(y => {
            $('#filterYear').append(`<option value="${y}">${y}</option>`);
        });

        let brokers = new Set();

        $('#importsTable tbody tr').each(function() {
            let broker = $(this).find('td:eq(3)').text().trim();
            if (broker) brokers.add(broker);
        });

        brokers.forEach(b => {
            $('#filterBroker').append(`<option value="${b}">${b}</option>`);
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {

            let row = table.row(dataIndex).node();

            let rawDate = $(row).find('td:eq(1)').data('order');
            if (!rawDate) return true;
            let year = rawDate.split('-')[0];
            let month = rawDate.split('-')[1];

            let broker = data[3];
            let note = data[2];

            let fYear = $('#filterYear').val();
            let fMonth = $('#filterMonth').val();
            let fBroker = $('#filterBroker').val();
            let fNote = $('#filterNote').val();

            if (fYear && year !== fYear) return false;
            if (fMonth && month !== fMonth) return false;
            if (fBroker && broker !== fBroker) return false;
            if (fNote && !note.includes(fNote)) return false;

            return true;
        });

        function updateSummary() {

            let total = 0;
            let lucro = 0;
            let prejuizo = 0;
            let months = {};
            let tradesCount = 0;

            table.rows({
                search: 'applied'
            }).every(function() {

                let node = this.node();

                let raw = $(node).find('td').eq(11).text();

                let value = parseFloat(
                    raw
                    .replace('R$', '')
                    .replace(/\./g, '')
                    .replace(',', '.')
                    .replace('+', '')
                    .trim()
                );

                if (isNaN(value)) value = 0;

                total += value;
                if (value > 0) lucro += value;
                if (value < 0) prejuizo += value;

                let trades = parseInt($(node).find('td').eq(4).text()) || 0;
                tradesCount += trades;

                let dateText = $(node).find('td').eq(1).text().trim();

                let parts = dateText.split('/');
                if (parts.length !== 3) return true;

                let month = parts[2] + '-' + parts[1];

                if (!months[month]) months[month] = 0;
                months[month] += value;

            });

            // =========================
            // 🔹 KPIs
            // =========================
            $('#totalResult')
                .removeClass('text-success text-danger text-muted')
                .addClass(total > 0 ? 'text-success' : total < 0 ? 'text-danger' : 'text-muted')
                .text("R$ " + total.toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                }));

            $('#totalProfit').text(
                "R$ " + lucro.toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                })
            );

            $('#totalLoss').text(
                "R$ " + prejuizo.toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                })
            );

            $('#totalTrades').text(tradesCount);

            let labels = Object.keys(months).sort((a, b) => new Date(a) - new Date(b));
            let dataChart = [];
            let acumulado = 0;

            labels.forEach(m => {
                acumulado += months[m];
                dataChart.push(acumulado);
            });

            if (labels.length === 0) {
                labels = ['Sem dados'];
                dataChart = [0];
            }

            let canvas = document.getElementById('chart');

            if (!canvas) {
                console.error('Canvas não encontrado');
                return;
            }

            if (typeof Chart === 'undefined') {
                console.error('Chart não carregado');
                return;
            }

            let ctx = canvas.getContext('2d');

            window.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels, // 🔥 usa seus dados reais
                    datasets: [{
                        label: 'Resultado Acumulado',
                        data: dataChart,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });

        }

        $('#filterYear, #filterMonth, #filterBroker').on('change', function() {
            table.draw();
            setTimeout(updateSummary, 100);
        });

        $('#filterNote').on('keyup', function() {
            table.draw();
            setTimeout(updateSummary, 100);
        });

        // 🔥 PRIMEIRA EXECUÇÃO
        setTimeout(updateSummary, 100);

    });

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