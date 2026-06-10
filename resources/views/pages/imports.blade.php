@extends('layouts.app')

@section('title','Importar em Massa')

@section('content')

<div class="container-fluid mass-import-page">

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Importar Notas em Massa</h5>
                <a href="{{ route('imports.index') }}" class="btn btn-light">Importar nota individual</a>
            </div>

            <div id="drop-area" class="mass-drop-area">
                <p class="mb-2">Arraste PDFs aqui ou clique para selecionar</p>
                <small class="text-muted">As notas importadas aparecem na listagem abaixo.</small>
                <input type="file" id="fileInput" multiple hidden accept="application/pdf">
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="fileList"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Notas Importadas</h5>

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
                            <option value="">Mes</option>
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
                                    {{ strtoupper($b) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="text" id="filterNote" class="form-control" placeholder="Numero da nota">
                    </div>
                </div>
            </form>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 text-center h-100">
                        <div class="card-body">
                            <small class="text-muted">Saldo Liquido</small>
                            <h4 id="totalResult" class="fw-bold mb-0">R$ 0,00</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 text-center h-100">
                        <div class="card-body">
                            <small class="text-muted">Lucro</small>
                            <h4 id="totalProfit" class="fw-bold text-success mb-0">R$ 0,00</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 text-center h-100">
                        <div class="card-body">
                            <small class="text-muted">Prejuizo</small>
                            <h4 id="totalLoss" class="fw-bold text-danger mb-0">R$ 0,00</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 text-center h-100">
                        <div class="card-body">
                            <small class="text-muted">Trades</small>
                            <h4 id="totalTrades" class="fw-bold mb-0">0</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-muted">Evolucao do Resultado</h6>
                    </div>

                    <div style="height:350px; background:#fff;">
                        <canvas id="chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="importsTable" class="table table-striped table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Data</th>
                            <th>Nota</th>
                            <th>Corretora</th>
                            <th>Trades</th>
                            <th>Valor Negocios</th>
                            <th>Taxa Reg.</th>
                            <th>Taxas BM&F</th>
                            <th>Custos</th>
                            <th>IRRF</th>
                            <th>Conta Normal</th>
                            <th>Resultado</th>
                            <th>Acoes</th>
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
                                <td>R$ {{ number_format($import->gross_value ?? 0,2,',','.') }}</td>
                                <td>R$ {{ number_format($import->bmf_registration_fee ?? 0,2,',','.') }}</td>
                                <td>R$ {{ number_format($import->bmf_fees ?? 0,2,',','.') }}</td>
                                <td>R$ {{ number_format($import->total_costs ?? 0,2,',','.') }}</td>
                                <td>R$ {{ number_format($import->irrf_daytrade_proj ?? 0,2,',','.') }}</td>
                                <td>R$ {{ number_format($import->account_normal_total ?? 0,2,',','.') }}</td>

                                <td>
                                    @if(($import->net_total ?? 0) > 0)
                                        <span class="text-success fw-bold">+{{ number_format($import->net_total,2,',','.') }}</span>
                                    @elseif(($import->net_total ?? 0) < 0)
                                        <span class="text-danger fw-bold">{{ number_format($import->net_total,2,',','.') }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>

                                <td>
                                    <button class="btn btn-sm btn-primary btn-show-trades" data-id="{{ $import->id }}">
                                        Ver
                                    </button>

                                    <form action="{{ route('imports.destroy', $import->id) }}" method="POST" class="form-delete" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger btn-delete">
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

<style>
    .mass-drop-area {
        border: 2px dashed #cbd5e1;
        padding: 34px;
        text-align: center;
        cursor: pointer;
        border-radius: 8px;
        background: #f8fafc;
        transition: 0.2s;
    }

    .mass-drop-area:hover,
    .mass-drop-area.dragover {
        border-color: #2563eb;
        background: #eff6ff;
    }

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
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

    if (dropArea) {
        dropArea.addEventListener('click', () => fileInput.click());

        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('dragover');
        });

        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });
    }

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (file.type !== 'application/pdf') {
                Swal.fire('Erro', 'Somente PDF', 'error');
                return;
            }

            let row = document.createElement('tr');
            let statusId = 'status-' + file.name.replace(/[^a-zA-Z0-9]/g, '-');

            row.innerHTML = `
                <td>${file.name}</td>
                <td id="${statusId}">Enviando...</td>
            `;

            fileList.appendChild(row);
            uploadFile(file, statusId);
        });
    }

    function uploadFile(file, statusId) {
        let formData = new FormData();
        formData.append('file', file);

        let xhr = new XMLHttpRequest();
        xhr.open('POST', '/upload', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            let statusCell = document.getElementById(statusId);
            let response = {};

            try {
                response = JSON.parse(xhr.responseText || '{}');
            } catch (e) {}

            if (xhr.status === 200 && response.success) {
                statusCell.innerHTML = '<span class="text-success">Importado</span>';
                setTimeout(() => window.location.reload(), 800);
                return;
            }

            statusCell.innerHTML = '<span class="text-danger">Erro</span>';

            Swal.fire({
                icon: 'error',
                title: 'Falha ao importar',
                text: response.message || 'Nao foi possivel importar o arquivo',
            });
        };

        xhr.onerror = function() {
            let statusCell = document.getElementById(statusId);
            statusCell.innerHTML = '<span class="text-danger">Erro</span>';
        };

        xhr.send(formData);
    }

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

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'importsTable') {
                return true;
            }

            let row = table.row(dataIndex).node();
            let rawDate = $(row).find('td:eq(1)').data('order');

            if (!rawDate) return true;

            let year = rawDate.split('-')[0];
            let month = rawDate.split('-')[1];
            let broker = data[3];
            let note = data[2];

            let fYear = $('select[name="year"]').val();
            let fMonth = $('select[name="month"]').val();
            let fBroker = $('select[name="broker"]').val();
            let fNote = $('#filterNote').val();

            if (fYear && year !== fYear) return false;
            if (fMonth && month !== fMonth) return false;
            if (fBroker && broker.toLowerCase() !== fBroker.toLowerCase()) return false;
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

            $('#totalResult')
                .removeClass('text-success text-danger text-muted')
                .addClass(total > 0 ? 'text-success' : total < 0 ? 'text-danger' : 'text-muted')
                .text("R$ " + total.toLocaleString("pt-BR", {
                    minimumFractionDigits: 2
                }));

            $('#totalProfit').text("R$ " + lucro.toLocaleString("pt-BR", {
                minimumFractionDigits: 2
            }));

            $('#totalLoss').text("R$ " + prejuizo.toLocaleString("pt-BR", {
                minimumFractionDigits: 2
            }));

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

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            if (window.importsChart) {
                window.importsChart.destroy();
            }

            window.importsChart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
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

        $('#filterNote').on('keyup', function() {
            table.draw();
            setTimeout(updateSummary, 100);
        });

        table.on('draw', function() {
            updateSummary();
        });

        setTimeout(updateSummary, 100);
    });

    $(document).on('click', '.btn-show-trades', function() {
        let id = $(this).data('id');
        let row = $(this).closest('tr');

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
            html += '<th>Preco</th>';
            html += '</tr></thead><tbody>';

            trades.forEach(function(t) {
                let side = t.side === 'buy'
                    ? '<span class="text-success">Compra</span>'
                    : '<span class="text-danger">Venda</span>';

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
            row.after('<tr class="details"><td colspan="13">' + html + '</td></tr>');
        });
    });

    $(document).on('click', '.btn-delete', function() {
        let form = $(this).closest('form');

        Swal.fire({
            title: 'Excluir nota?',
            text: 'Todos os trades dessa nota serao removidos.',
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
