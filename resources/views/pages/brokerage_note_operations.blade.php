@extends('layouts.app')

@section('title','Operacoes da nota')

@section('content')

@php
    $money = function ($value) {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    };

    $plainMoney = function ($value) {
        return number_format((float) $value, 2, ',', '.');
    };

    $irNormal = $note->irrf ?? 0;
    $irDayTrade = $note->irrf_daytrade_proj ?? $note->irrf_daytrade ?? 0;
    $liquidValue = $note->net_total ?? $note->net_result ?? 0;
    $amountToPay = $liquidValue < 0 ? abs($liquidValue) : 0;
    $amountToReceive = $liquidValue > 0 ? $liquidValue : 0;
@endphp

<div class="note-operations-page">

    <fieldset class="operation-step">
        <legend>Passo 1 de 3: Dados da nota</legend>

        <div class="step-grid step-grid-three">
            <div class="readonly-field">
                <label>Data Pregao</label>
                <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($note->trade_date)->format('d/m/Y') }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Numero nota</label>
                <input type="text" class="form-control" value="{{ $note->note_number }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Corretora</label>
                <select class="form-select" disabled>
                    <option>{{ strtoupper($note->broker) }}</option>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset class="operation-step">
        <legend>Passo 2 de 3: Negocios</legend>

        <div class="business-layout">
            <div class="business-form">
                <div class="readonly-field">
                    <label>C/V</label>
                    <select class="form-select" disabled>
                        <option>Compra</option>
                    </select>
                </div>

                <div class="readonly-field">
                    <label>Contrato/Mercadoria</label>
                    <input type="text" class="form-control" value="Informe o contrato" disabled>
                </div>

                <div class="readonly-field">
                    <label>Quantidade</label>
                    <input type="text" class="form-control" disabled>
                </div>

                <div class="readonly-field">
                    <label>Preco</label>
                    <input type="text" class="form-control" disabled>
                </div>

                <div class="readonly-field">
                    <label>Tipo Negocio</label>
                    <select class="form-select" disabled>
                        <option>Normal</option>
                    </select>
                </div>

                <button type="button" class="btn btn-light" disabled>Confirmar</button>
            </div>

            <div class="movement-summary">
                <h6>Resumo da movimentacao do dia</h6>

                <div class="soft-table">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Compras</th>
                                <th>Vendas</th>
                                <th>Liquido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $item)
                                <tr>
                                    <td>{{ $item['asset'] }}</td>
                                    <td>{{ $item['buys'] }}</td>
                                    <td>{{ $item['sells'] }}</td>
                                    <td>{{ $item['net'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Sem operacoes nesta nota.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="soft-table operations-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Seq</th>
                        <th>C/V</th>
                        <th>Contrato</th>
                        <th>Vencimento</th>
                        <th>Quantidade</th>
                        <th>Preco</th>
                        <th>Qtd. normal</th>
                        <th>Qtd. day trade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($note->trades as $trade)
                        @php
                            $isDayTrade = strtolower($trade->trade_type ?? '') === 'daytrade';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $trade->side === 'buy' ? 'C' : 'V' }}</td>
                            <td>{{ $trade->asset }}</td>
                            <td>{{ \Carbon\Carbon::parse($trade->trade_date)->format('d/m/Y') }}</td>
                            <td>{{ $trade->quantity }}</td>
                            <td>{{ $money($trade->price) }}</td>
                            <td>{{ $isDayTrade ? 0 : $trade->quantity }}</td>
                            <td>{{ $isDayTrade ? $trade->quantity : 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted">Nenhuma operacao encontrada para esta nota.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </fieldset>

    <fieldset class="operation-step">
        <legend>Passo 3 de 3: Taxas</legend>

        <div class="step-grid step-grid-four">
            <div class="readonly-field">
                <label>Ir Retido Normal</label>
                <input type="text" class="form-control" value="{{ $plainMoney($irNormal) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Ir Retido DayTrade</label>
                <input type="text" class="form-control" value="{{ $plainMoney($irDayTrade) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Taxa operacional</label>
                <input type="text" class="form-control" value="{{ $plainMoney($note->operational_fee ?? 0) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Taxa Registro</label>
                <input type="text" class="form-control" value="{{ $plainMoney($note->bmf_registration_fee ?? 0) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Taxa BMF</label>
                <input type="text" class="form-control" value="{{ $plainMoney($note->bmf_fees ?? 0) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Outros custos</label>
                <input type="text" class="form-control" value="{{ $plainMoney($note->total_costs ?? 0) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>Valor Iss</label>
                <input type="text" class="form-control" value="0,00" disabled>
            </div>

            <div class="readonly-field">
                <label>Ajuste de Posicao</label>
                <input type="text" class="form-control" value="{{ $plainMoney($note->daytrade_adjustment ?? 0) }}" disabled>
            </div>
        </div>

        <div class="tax-checks">
            <label>
                <input type="checkbox" {{ $irDayTrade > 0 ? 'checked' : '' }} disabled>
                <span>Cobrar IRRF day trade?</span>
            </label>

            <label>
                <input type="checkbox" {{ $irNormal > 0 ? 'checked' : '' }} disabled>
                <span>Cobrar IRRF normal?</span>
            </label>
        </div>
    </fieldset>

    <fieldset class="operation-step">
        <legend>Valor Liquido</legend>

        <div class="step-grid step-grid-two">
            <div class="readonly-field">
                <label>A Pagar</label>
                <input type="text" class="form-control" value="{{ $plainMoney($amountToPay) }}" disabled>
            </div>

            <div class="readonly-field">
                <label>A Receber</label>
                <input type="text" class="form-control" value="{{ $plainMoney($amountToReceive) }}" disabled>
            </div>
        </div>
    </fieldset>

    <div class="operations-actions">
        <a href="{{ $backUrl }}" class="btn btn-light">Voltar</a>
        <a href="{{ $backUrl }}" class="btn btn-primary">Editar nota</a>
    </div>
</div>

<style>
    .note-operations-page {
        color: #111827;
    }

    .operation-step {
        margin-bottom: 28px;
        padding: 28px 12px 12px;
        border: 1px solid #dde1e8;
        border-radius: 8px;
        background: #fff;
    }

    .operation-step legend {
        float: none;
        width: auto;
        margin: -40px 0 16px;
        padding: 0 8px;
        background: #f4f6fb;
        color: #374151;
        font-size: 14px;
        font-weight: 700;
    }

    .step-grid {
        display: grid;
        gap: 22px;
    }

    .step-grid-three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .step-grid-four {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .step-grid-two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .readonly-field label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 800;
    }

    .readonly-field .form-control,
    .readonly-field .form-select {
        min-height: 40px;
        border: 1px solid #d8dde6;
        border-radius: 4px;
        background-color: #eef2f7;
        color: #6b7280;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
        font-size: 14px;
    }

    .business-layout {
        display: grid;
        grid-template-columns: 1fr minmax(360px, .95fr);
        gap: 26px;
        align-items: start;
        margin-bottom: 28px;
    }

    .business-form {
        display: grid;
        grid-template-columns: repeat(5, minmax(130px, 1fr)) 90px;
        gap: 16px;
        align-items: end;
    }

    .movement-summary h6 {
        margin: 0 0 8px;
        font-size: 13px;
        font-weight: 800;
    }

    .soft-table {
        overflow: hidden;
        border: 1px solid #dde1e8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 7px rgba(15, 23, 42, 0.04);
    }

    .soft-table .table {
        color: #374151;
    }

    .soft-table thead th {
        border-bottom: 0;
        background: #eaf2ff;
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
    }

    .soft-table tbody td {
        border-bottom: 0;
        padding: 12px;
        font-size: 14px;
    }

    .operations-table {
        border-radius: 14px;
    }

    .tax-checks {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    .tax-checks label {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #6b7280;
        font-size: 14px;
    }

    .tax-checks input {
        width: 18px;
        height: 18px;
        accent-color: #2563eb;
    }

    .operations-actions {
        display: flex;
        justify-content: flex-end;
        gap: 14px;
        margin-top: 26px;
    }

    .operations-actions .btn {
        min-width: 76px;
    }

    @media (max-width: 1180px) {
        .business-layout,
        .step-grid-four,
        .step-grid-three {
            grid-template-columns: 1fr 1fr;
        }

        .business-form {
            grid-template-columns: repeat(2, minmax(160px, 1fr));
        }
    }

    @media (max-width: 760px) {
        .business-layout,
        .business-form,
        .step-grid,
        .tax-checks {
            grid-template-columns: 1fr;
        }

        .operations-actions {
            justify-content: stretch;
        }

        .operations-actions .btn {
            flex: 1;
        }
    }
</style>

@endsection
