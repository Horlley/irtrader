@extends('layouts.app')

@section('title','Relatorio IR')

@section('content')

@php
    $monthNames = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Marco',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro',
    ];

    $money = function ($value) {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    };

    $valueClass = function ($value) {
        if ((float) $value > 0) {
            return 'text-success';
        }

        if ((float) $value < 0) {
            return 'text-danger';
        }

        return 'text-muted';
    };

    $annualResult = collect($months)->sum('result');
    $annualTax = collect($months)->sum('tax');
    $annualDarf = collect($months)->sum('darf');
    $annualIrrf = collect($months)->sum('irrf_month');
    $monthsWithMovement = collect($months)->filter(function ($month) {
        return abs((float) $month['result']) > 0
            || abs((float) $month['tax']) > 0
            || abs((float) $month['darf']) > 0
            || abs((float) $month['irrf_month']) > 0;
    })->count();
@endphp

<div class="ir-report-page">
    <div class="ir-report-header">
        <div>
            <h2>Relatorio IR (Modelo Receita)</h2>
            <p>Conferencia anual por mes, mercados e consolidacao fiscal.</p>
        </div>

        <form method="GET" action="{{ url('/tax/report-ir') }}" class="ir-report-filter" data-ajax-form>
            <div class="filter-control">
                <label for="irYearSelect">Ano</label>
                <select id="irYearSelect" name="year" class="form-select" data-ajax-auto-submit>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <span>Resultado anual</span>
            <strong class="{{ $valueClass($annualResult) }}">{{ $money($annualResult) }}</strong>
        </div>

        <div class="summary-card">
            <span>Imposto devido</span>
            <strong class="{{ $valueClass($annualTax) }}">{{ $money($annualTax) }}</strong>
        </div>

        <div class="summary-card">
            <span>IRRF no ano</span>
            <strong>{{ $money($annualIrrf) }}</strong>
        </div>

        <div class="summary-card">
            <span>DARF a pagar</span>
            <strong class="{{ $annualDarf > 0 ? 'text-danger' : 'text-muted' }}">{{ $money($annualDarf) }}</strong>
        </div>

        <div class="summary-card">
            <span>Meses com movimento</span>
            <strong>{{ $monthsWithMovement }}</strong>
        </div>
    </div>

    <div class="month-tabs">
        @foreach($months as $m)
            @php
                $monthKey = $m['month'];
                $hasMovement = abs((float) $m['result']) > 0 || abs((float) $m['tax']) > 0 || abs((float) $m['darf']) > 0;
            @endphp
            <a href="#month-{{ $monthKey }}" class="month-chip {{ $hasMovement ? 'has-movement' : '' }}">
                {{ $monthKey }}
            </a>
        @endforeach
    </div>

    <div class="months-stack">
        @foreach($months as $m)
            @php
                $monthKey = $m['month'];
                $marketRows = [
                    ['label' => 'Acoes', 'common' => null, 'daytrade' => 0],
                    ['label' => 'Opcoes', 'common' => null, 'daytrade' => 0],
                    ['label' => 'Dolar', 'common' => null, 'daytrade' => $m['markets']['dolar']['profit'] ?? 0],
                    ['label' => 'Indice', 'common' => null, 'daytrade' => $m['markets']['indice']['profit'] ?? 0],
                    ['label' => 'Outros', 'common' => null, 'daytrade' => $m['markets']['outros']['profit'] ?? 0],
                    ['label' => 'Termo', 'common' => null, 'daytrade' => 0],
                ];
            @endphp

            <section id="month-{{ $monthKey }}" class="month-card">
                <div class="month-card-header">
                    <div>
                        <span>{{ $monthKey }}/{{ $year }}</span>
                        <h3>{{ $monthNames[$monthKey] ?? $monthKey }}</h3>
                    </div>

                    <div class="month-card-total">
                        <span>Resultado liquido</span>
                        <strong class="{{ $valueClass($m['result']) }}">{{ $money($m['result']) }}</strong>
                    </div>
                </div>

                <div class="ir-sections-grid">
                    <div class="ir-section">
                        <div class="section-heading">
                            <span>Mercados</span>
                            <strong>Resultado por tipo</strong>
                        </div>

                        <div class="table-responsive">
                            <table class="table ir-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tipo de Mercado/Ativo</th>
                                        <th>Operacoes Comuns</th>
                                        <th>Day-Trade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($marketRows as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ $row['common'] === null ? '-' : $money($row['common']) }}</td>
                                            <td class="{{ $valueClass($row['daytrade']) }}">
                                                {{ $money($row['daytrade']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ir-section">
                        <div class="section-heading">
                            <span>Resultados</span>
                            <strong>Base de calculo</strong>
                        </div>

                        <div class="metric-list">
                            <div>
                                <span>Resultado liquido do mes</span>
                                <strong class="{{ $valueClass($m['result']) }}">{{ $money($m['result']) }}</strong>
                            </div>

                            <div>
                                <span>Resultado negativo ate o mes anterior</span>
                                <strong>{{ $money($m['previous_loss']) }}</strong>
                            </div>

                            <div>
                                <span>Base de calculo do imposto</span>
                                <strong>{{ $money($m['base']) }}</strong>
                            </div>

                            <div>
                                <span>Prejuizo a compensar</span>
                                <strong>{{ $money($m['loss_carry']) }}</strong>
                            </div>

                            <div>
                                <span>Aliquota</span>
                                <strong>20% Day-Trade</strong>
                            </div>

                            <div>
                                <span>Imposto devido</span>
                                <strong class="{{ $m['tax'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $money($m['tax']) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="ir-section consolidation-section">
                        <div class="section-heading">
                            <span>Consolidacao</span>
                            <strong>Imposto do mes</strong>
                        </div>

                        <div class="metric-list compact">
                            <div>
                                <span>Total do imposto devido</span>
                                <strong>{{ $money($m['tax']) }}</strong>
                            </div>

                            <div>
                                <span>IR Fonte no mes</span>
                                <strong>{{ $money($m['irrf_month']) }}</strong>
                            </div>

                            <div>
                                <span>IR Fonte meses anteriores</span>
                                <strong>{{ $money($m['irrf_previous'] ?? 0) }}</strong>
                            </div>

                            <div>
                                <span>IR Fonte acumulado</span>
                                <strong>{{ $money($m['irrf_balance']) }}</strong>
                            </div>

                            <div class="highlight-row">
                                <span>Imposto a pagar</span>
                                <strong class="{{ $m['darf'] > 0 ? 'text-danger' : 'text-primary' }}">{{ $money($m['darf']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>

<style>
    .ir-report-page {
        color: #111827;
    }

    .ir-report-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
    }

    .ir-report-header h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
    }

    .ir-report-header p {
        margin: 6px 0 0;
        color: #64748b;
    }

    .ir-report-filter {
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
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
        color: #111827;
        font-weight: 700;
    }

    .filter-control .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
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
        font-size: 22px;
        font-weight: 900;
    }

    .month-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }

    .month-chip {
        display: inline-flex;
        min-width: 44px;
        min-height: 34px;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #fff;
        color: #64748b;
        font-weight: 800;
        text-decoration: none;
    }

    .month-chip:hover,
    .month-chip.has-movement {
        border-color: #2563eb;
        background: #eff6ff;
        color: #2563eb;
    }

    .months-stack {
        display: grid;
        gap: 18px;
    }

    .month-card {
        scroll-margin-top: 18px;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .month-card-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
        padding: 16px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #dbeafe;
    }

    .month-card-header span,
    .month-card-total span,
    .section-heading span,
    .metric-list span {
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
    }

    .month-card-header h3 {
        margin: 4px 0 0;
        font-size: 20px;
        font-weight: 900;
    }

    .month-card-total {
        text-align: right;
    }

    .month-card-total strong {
        display: block;
        margin-top: 4px;
        font-size: 20px;
        font-weight: 900;
    }

    .ir-sections-grid {
        display: grid;
        grid-template-columns: minmax(360px, 1.2fr) minmax(280px, .9fr);
        gap: 16px;
        padding: 18px;
    }

    .ir-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .consolidation-section {
        grid-column: 1 / -1;
    }

    .section-heading {
        padding: 14px;
        border-bottom: 1px solid #e5e7eb;
        background: #eff6ff;
    }

    .section-heading strong {
        display: block;
        margin-top: 4px;
        font-size: 16px;
        font-weight: 900;
    }

    .ir-table thead th {
        border-bottom: 0;
        background: #eaf2ff;
        color: #1f2937;
        font-size: 13px;
        font-weight: 800;
    }

    .ir-table tbody td {
        padding: 12px;
        vertical-align: middle;
    }

    .metric-list {
        display: grid;
        gap: 0;
    }

    .metric-list div {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
    }

    .metric-list div:last-child {
        border-bottom: 0;
    }

    .metric-list strong {
        text-align: right;
        font-weight: 900;
    }

    .metric-list.compact {
        grid-template-columns: repeat(5, minmax(160px, 1fr));
    }

    .metric-list.compact div {
        display: block;
        border-right: 1px solid #e5e7eb;
        border-bottom: 0;
    }

    .metric-list.compact div:last-child {
        border-right: 0;
    }

    .metric-list.compact strong {
        display: block;
        margin-top: 6px;
        text-align: left;
    }

    .highlight-row {
        background: #eff6ff;
    }

    @media (max-width: 1100px) {
        .summary-grid,
        .metric-list.compact {
            grid-template-columns: repeat(2, minmax(160px, 1fr));
        }

        .ir-sections-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .ir-report-header,
        .month-card-header {
            align-items: stretch;
            flex-direction: column;
        }

        .month-card-total {
            text-align: left;
        }

        .summary-grid,
        .metric-list.compact {
            grid-template-columns: 1fr;
        }

        .ir-report-filter {
            min-width: 0;
        }
    }
</style>

@endsection
