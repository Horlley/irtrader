@extends('layouts.app')

@section('content')

@php
    $monthNames = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Marco',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
@endphp

<div class="monthly-tax-report">
    <div class="report-header">
        <div>
            <h2>Relatorio Fiscal Mensal</h2>
            <p>Resultado por mercado no periodo selecionado.</p>
        </div>

        <form id="reportFilters" class="report-filters">
            <div class="filter-control">
                <label for="monthSelect">Mes</label>
                <select id="monthSelect" name="month" class="form-select">
                    @foreach($monthNames as $number => $name)
                        <option value="{{ $number }}" {{ (int) $month === $number ? 'selected' : '' }}>
                            {{ str_pad($number, 2, '0', STR_PAD_LEFT) }} - {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-control">
                <label for="yearSelect">Ano</label>
                <select id="yearSelect" name="year" class="form-select">
                    @for($y = 2024; $y <= now()->year; $y++)
                        <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
        </form>
    </div>

    <div id="reportLoading" class="report-loading">
        Carregando relatorio...
    </div>

    <div id="reportContent" class="d-none">
        <div class="summary-grid notes-summary-grid">
            <div class="summary-card">
                <span>Notas importadas</span>
                <strong id="summaryNotes">0</strong>
            </div>

            <div class="summary-card">
                <span>Trades importados</span>
                <strong id="summaryTrades">0</strong>
            </div>

            <div class="summary-card">
                <span>Total de custos</span>
                <strong id="summaryCosts">R$ 0,00</strong>
            </div>

            <div class="summary-card">
                <span>IRRF Day Trade</span>
                <strong id="summaryIrrfDayTrade">R$ 0,00</strong>
            </div>

            <div class="summary-card">
                <span>Valor liquido das notas</span>
                <strong id="summaryNotesNet">R$ 0,00</strong>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="section-title">Resultado por mercado</h6>
                <div class="table-responsive">
                    <table class="table report-table mb-0">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Mercado</th>
                                <th>Resultado Bruto</th>
                                <th>Resultado Liquido</th>
                            </tr>
                        </thead>
                        <tbody id="reportRows"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th id="footerGross">R$ 0,00</th>
                                <th id="footerNet">R$ 0,00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="section-title">Resumo das notas por corretora</h6>

                <div class="table-responsive">
                    <table class="table report-table mb-0">
                        <thead>
                            <tr>
                                <th>Corretora</th>
                                <th>Notas</th>
                                <th>Trades</th>
                                <th>Taxa Registro</th>
                                <th>Taxa BM&F</th>
                                <th>IR Day Trade</th>
                                <th>Valor Liquido</th>
                            </tr>
                        </thead>
                        <tbody id="brokerRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .monthly-tax-report {
        color: #111827;
    }

    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 24px;
        margin-bottom: 22px;
    }

    .report-header h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
    }

    .report-header p {
        margin: 6px 0 0;
        color: #64748b;
    }

    .report-filters {
        display: flex;
        gap: 14px;
        align-items: end;
    }

    .filter-control {
        min-width: 180px;
    }

    .filter-control label {
        display: block;
        margin-bottom: 8px;
        color: #374151;
        font-size: 13px;
        font-weight: 800;
    }

    .filter-control .form-select {
        min-height: 46px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        background-color: #fff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
        color: #111827;
        font-weight: 700;
    }

    .filter-control .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .report-loading {
        padding: 28px;
        border: 1px dashed #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 800;
        text-align: center;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .notes-summary-grid {
        grid-template-columns: repeat(5, minmax(150px, 1fr));
    }

    .summary-card {
        min-height: 92px;
        padding: 16px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        background: #eff6ff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }

    .summary-card span {
        display: block;
        margin-bottom: 10px;
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
    }

    .summary-card strong {
        font-size: 23px;
        font-weight: 900;
    }

    .section-title {
        margin: 0 0 14px;
        color: #374151;
        font-size: 14px;
        font-weight: 900;
    }

    .report-table thead th {
        border-bottom: 0;
        background: #eaf2ff;
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
    }

    .report-table tbody td,
    .report-table tfoot th {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .report-table tfoot th {
        border-top: 1px solid #dbeafe;
        background: #f8fafc;
    }

    @media (max-width: 900px) {
        .report-header {
            align-items: stretch;
            flex-direction: column;
        }

        .report-filters {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .summary-grid {
            grid-template-columns: repeat(2, minmax(160px, 1fr));
        }

        .notes-summary-grid {
            grid-template-columns: repeat(2, minmax(160px, 1fr));
        }
    }

    @media (max-width: 620px) {
        .report-filters,
        .summary-grid {
            grid-template-columns: 1fr;
        }

        .filter-control {
            min-width: 0;
        }
    }
</style>

@endsection

@push('scripts')
<script>
    const reportUrl = "{{ route('tax.report') }}";
    const initialMonth = "{{ (int) $month }}";
    const initialYear = "{{ (int) $year }}";

    function money(value) {
        value = Number(value || 0);

        return 'R$ ' + value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function valueClass(value) {
        value = Number(value || 0);

        if (value > 0) return 'text-success';
        if (value < 0) return 'text-danger';
        return 'text-muted';
    }

    function setMoney(selector, value) {
        const element = document.querySelector(selector);

        element.classList.remove('text-success', 'text-danger', 'text-muted');
        element.classList.add(valueClass(value));
        element.textContent = money(value);
    }

    function setNumber(selector, value) {
        document.querySelector(selector).textContent = Number(value || 0).toLocaleString('pt-BR');
    }

    function marketLabel(key) {
        const labels = {
            dolar: 'Mercado futuro dolar',
            indice: 'Mercado futuro indice',
            bitcoin: 'Mercado futuro bitcoin',
            outros: 'Outros'
        };

        return labels[key] || key;
    }

    function renderReport(data) {
        const month = String(data.month).padStart(2, '0');
        const monthData = data.monthData || {};
        const markets = monthData.markets || {};
        const rows = [];

        let totalGross = 0;
        let totalNet = 0;

        Object.keys(markets).forEach((key) => {
            const market = markets[key] || {};
            const gross = Number(market.profit || 0);
            const net = Number(market.net || 0);

            totalGross += gross;
            totalNet += net;

            rows.push(`
                <tr>
                    <td>${data.year}-${month}</td>
                    <td>${marketLabel(key)}</td>
                    <td>${money(gross)}</td>
                    <td class="${valueClass(net)} fw-bold">${money(net)}</td>
                </tr>
            `);
        });

        rows.push(`
            <tr>
                <td>${data.year}-${month}</td>
                <td><strong>TOTAL REAL</strong></td>
                <td><strong>${money(Number(monthData.result || 0))}</strong></td>
                <td class="${valueClass(Number(monthData.result || 0) - Number(monthData.tax || 0))} fw-bold">
                    <strong>${money(Number(monthData.result || 0) - Number(monthData.tax || 0))}</strong>
                </td>
            </tr>
        `);

        document.getElementById('reportRows').innerHTML = rows.join('');

        const notes = data.notesSummary || {};
        const brokerRows = (data.brokerSummary || []).map((row) => `
            <tr>
                <td><strong>${row.broker}</strong></td>
                <td>${Number(row.notes_count || 0).toLocaleString('pt-BR')}</td>
                <td>${Number(row.trades_count || 0).toLocaleString('pt-BR')}</td>
                <td>${money(row.registration_fee)}</td>
                <td>${money(row.bmf_fees)}</td>
                <td>${money(row.ir_day_trade)}</td>
                <td class="${valueClass(row.net_total)} fw-bold">${money(row.net_total)}</td>
            </tr>
        `);

        document.getElementById('brokerRows').innerHTML = brokerRows.length
            ? brokerRows.join('')
            : '<tr><td colspan="7" class="text-muted">Nenhuma nota encontrada para o periodo.</td></tr>';

        setNumber('#summaryNotes', notes.notes_count);
        setNumber('#summaryTrades', notes.trades_count);
        setMoney('#summaryCosts', notes.total_costs);
        setMoney('#summaryIrrfDayTrade', notes.ir_day_trade);
        setMoney('#summaryNotesNet', notes.net_total);
        setMoney('#footerGross', totalGross);
        setMoney('#footerNet', totalNet);

        document.getElementById('reportLoading').classList.add('d-none');
        document.getElementById('reportContent').classList.remove('d-none');
    }

    function loadReport() {
        const month = document.getElementById('monthSelect').value;
        const year = document.getElementById('yearSelect').value;
        const url = `${reportUrl}?month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}`;

        document.getElementById('reportLoading').classList.remove('d-none');
        document.getElementById('reportContent').classList.add('d-none');

        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(renderReport)
            .catch(() => {
                document.getElementById('reportLoading').textContent = 'Nao foi possivel carregar o relatorio.';
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('monthSelect').value = initialMonth;
        document.getElementById('yearSelect').value = initialYear;

        document.getElementById('monthSelect').addEventListener('change', loadReport);
        document.getElementById('yearSelect').addEventListener('change', loadReport);

        loadReport();
    });
</script>
@endpush
