@extends('layouts.app')

@section('title','Notas corretagem')

@section('content')

@php
    $months = [
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

    $inputMoney = function ($value) {
        return number_format((float) $value, 2, ',', '.');
    };

    $baseFilters = request()->except('month');
@endphp

<div class="brokerage-notes-page">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="brokerage-page-header">
        <div>
            <h2>Notas corretagem</h2>
            <p>Consulte notas importadas, custos e resultado por periodo.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('tax.brokerage-notes') }}" class="notes-filter" id="brokerageFilters">
        <div class="filter-field filter-year">
            <label>Ano</label>
            <select name="year" class="form-select">
                @foreach($years as $itemYear)
                    <option value="{{ $itemYear }}" {{ (string) $year === (string) $itemYear ? 'selected' : '' }}>
                        {{ $itemYear }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field filter-number">
            <label>Numero</label>
            <input type="text" name="number" class="form-control" value="{{ request('number') }}" placeholder="Procurar por numero">
        </div>

        <div class="filter-field">
            <label>Corretoras</label>
            <select name="broker" class="form-select">
                <option value="">Todas</option>
                @foreach($brokers as $broker)
                    <option value="{{ $broker }}" {{ request('broker') === $broker ? 'selected' : '' }}>
                        {{ strtoupper($broker) }}
                    </option>
                @endforeach
            </select>
        </div>        

        <div class="filter-field filter-period">
            <label>Periodo</label>
            <input type="text" name="period" class="form-control" value="{{ request('period') }}" placeholder="Procurar por periodo">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('tax.brokerage-notes', ['year' => $year]) }}" class="btn btn-light" id="clearBrokerageFilters">Limpar</a>
        </div>
    </form>

    <div class="month-tabs">
        @foreach($months as $monthNumber => $monthName)
            @php
                $isActive = $month === $monthNumber;
                $monthUrl = route('tax.brokerage-notes', array_merge($baseFilters, ['year' => $year, 'month' => $monthNumber]));
            @endphp

            <a href="{{ $monthUrl }}" class="month-filter {{ $isActive ? 'active' : '' }}">
                {{ $monthName }}
            </a>
        @endforeach

        <a href="{{ route('tax.brokerage-notes', array_merge($baseFilters, ['year' => $year])) }}" class="month-filter all-months {{ !$month ? 'active' : '' }}">
            Todos os meses
        </a>
    </div>

    <div id="brokerageLoading" class="brokerage-loading">
        Carregando notas de corretagem...
    </div>

    <div id="notesContent" class="d-none">

    <div class="brokerage-summary">
        <div class="summary-card">
            <span>Notas</span>
            <strong>{{ $summary['notes_count'] ?? 0 }}</strong>
        </div>

        <div class="summary-card">
            <span>Taxa registro</span>
            <strong>{{ $money($summary['registration_fee'] ?? 0) }}</strong>
        </div>

        <div class="summary-card">
            <span>Taxa BM&F</span>
            <strong>{{ $money($summary['bmf_fees'] ?? 0) }}</strong>
        </div>

        <div class="summary-card">
            <span>IR Day Trade</span>
            <strong>{{ $money($summary['ir_day_trade'] ?? 0) }}</strong>
        </div>

        <div class="summary-card">
            <span>Valor Liquido</span>
            @php
                $summaryNet = $summary['net_total'] ?? 0;
            @endphp
            <strong class="{{ $summaryNet < 0 ? 'text-danger' : ($summaryNet > 0 ? 'text-success' : '') }}">
                {{ $money($summaryNet) }}
            </strong>
        </div>
    </div>

    @if($notes->isEmpty())
        <div class="empty-state">
            Nenhuma nota encontrada para os filtros selecionados.
        </div>
    @else
        <div class="notes-grid">
            @foreach($notes as $note)
                @php
                    $tradeAssets = $note->trades->pluck('asset')->filter()->unique()->values();
                    $tradeMarkets = $note->trades->pluck('market')->filter()->unique()->values();
                    $isFuture = $tradeAssets->contains(function ($asset) {
                        $asset = strtoupper($asset);
                        return strpos($asset, 'WIN') !== false || strpos($asset, 'WDO') !== false;
                    });
                    $noteType = $isFuture || $tradeMarkets->contains('dolar') || $tradeMarkets->contains('indice') ? 'FUTUROS' : 'OPERACOES';
                    $irNormal = $note->irrf ?? 0;
                    $irDayTrade = $note->irrf_daytrade_proj ?? $note->irrf_daytrade ?? 0;
                    $liquidValue = $note->net_total ?? $note->net_result ?? 0;
                    $liquidMark = $liquidValue < 0 ? 'D' : 'C';
                    $monthForImport = \Carbon\Carbon::parse($note->trade_date)->format('m');
                @endphp

                <article class="note-card">
                    <div class="note-card-header">
                        <strong>{{ $noteType }}</strong>
                        <span>#{{ $note->note_number }}</span>
                    </div>

                    <div class="note-card-meta">
                        <div class="note-link-icon">↗</div>
                        <div>
                            <div>{{ \Carbon\Carbon::parse($note->trade_date)->format('d/m/Y') }}</div>
                            <strong>{{ strtoupper($note->broker) }}</strong>
                        </div>
                        <button type="button" class="edit-button" data-bs-toggle="modal" data-bs-target="#noteEditModal{{ $note->id }}">
                            Editar
                        </button>
                    </div>

                    <div class="note-lines">
                        <div>
                            <span>Corretagem</span>
                            <strong>{{ $money($note->operational_fee ?? 0) }}</strong>
                        </div>
                        <div>
                            <span>Outros custos</span>
                            <strong>{{ $money($note->total_costs ?? 0) }}</strong>
                        </div>
                        <div>
                            <span>Taxa registro</span>
                            <strong>{{ $money($note->bmf_registration_fee ?? 0) }}</strong>
                        </div>
                        <div>
                            <span>Taxa BM&F</span>
                            <strong>{{ $money($note->bmf_fees ?? 0) }}</strong>
                        </div>
                        <div>
                            <span>ISS</span>
                            <strong>{{ $money(0) }}</strong>
                        </div>
                        <div>
                            <span>IR Normal</span>
                            <strong>{{ $money($irNormal) }}</strong>
                        </div>
                        <div>
                            <span>IR Day Trade</span>
                            <strong>
                                {{ $money($irDayTrade) }}
                                <small class="debit-mark">D</small>
                            </strong>
                        </div>
                    </div>

                    <div class="note-total">
                        <span>Valor Liquido</span>
                        <strong class="{{ $liquidValue < 0 ? 'text-danger' : '' }}">
                            {{ $money(abs($liquidValue)) }}
                            <small class="debit-mark">{{ $liquidMark }}</small>
                        </strong>
                    </div>
                </article>

                <div class="modal fade brokerage-edit-modal" id="noteEditModal{{ $note->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">#{{ $note->note_number }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>

                            <form id="noteUpdateForm{{ $note->id }}" method="POST" action="{{ route('tax.brokerage-notes.update', $note->id) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="year" value="{{ request('year', $year) }}">
                                <input type="hidden" name="month" value="{{ request('month') }}">
                                <input type="hidden" name="number" value="{{ request('number') }}">
                                <input type="hidden" name="broker" value="{{ request('broker') }}">
                                <input type="hidden" name="asset" value="{{ request('asset') }}">
                                <input type="hidden" name="origin" value="{{ request('origin') }}">
                                <input type="hidden" name="period" value="{{ request('period') }}">
                            </form>

                            <div class="modal-body">
                                <div class="modal-note-summary">
                                    <div class="note-link-icon">#</div>
                                    <strong>{{ strtoupper($note->broker) }}</strong>
                                    <a href="{{ route('tax.brokerage-notes.operations', ['import' => $note->id, 'back' => request()->fullUrl()]) }}" class="modal-outline-button">
                                        Ver operacoes
                                    </a>
                                </div>

                                <div class="edit-field">
                                    <label>Corretagem</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="operational_fee" class="form-control" value="{{ $inputMoney($note->operational_fee ?? 0) }}">
                                </div>

                                <div class="edit-field">
                                    <label>Outros custos</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="total_costs" class="form-control" value="{{ $inputMoney($note->total_costs ?? 0) }}">
                                </div>

                                <div class="edit-field">
                                    <label>Taxa registro</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="bmf_registration_fee" class="form-control" value="{{ $inputMoney($note->bmf_registration_fee ?? 0) }}">
                                </div>

                                <div class="edit-field">
                                    <label>Taxa BM&F</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="bmf_fees" class="form-control" value="{{ $inputMoney($note->bmf_fees ?? 0) }}">
                                </div>

                                <div class="edit-field">
                                    <label>ISS</label>
                                    <input type="text" class="form-control" value="0,00">
                                </div>

                                <div class="edit-field">
                                    <label>IR Normal</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="irrf" class="form-control" value="{{ $inputMoney($irNormal) }}">
                                </div>

                                <div class="edit-field">
                                    <label>IR Day Trade</label>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="irrf_daytrade_proj" class="form-control" value="{{ $inputMoney($irDayTrade) }}">
                                </div>

                                <label class="edit-check">
                                    <input type="checkbox">
                                    <span>Debitar ISS</span>
                                </label>

                                <label class="edit-check">
                                    <input type="checkbox" {{ $irNormal > 0 ? 'checked' : '' }}>
                                    <span>Debitar IRR Normal</span>
                                </label>

                                <label class="edit-check">
                                    <input type="checkbox" {{ $irDayTrade > 0 ? 'checked' : '' }}>
                                    <span>Debitar IRR Day Trade</span>
                                </label>

                                <div class="modal-liquid-total">
                                    <span>Valor Liquido</span>
                                    <input form="noteUpdateForm{{ $note->id }}" type="text" name="net_total" class="form-control" value="{{ $inputMoney($liquidValue) }}">
                                </div>
                            </div>

                            <div class="modal-footer">
                                <form method="POST" action="{{ route('imports.destroy', $note->id) }}" onsubmit="return confirm('Excluir esta nota de corretagem?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link text-danger">Deletar</button>
                                </form>

                                <button type="submit" form="noteUpdateForm{{ $note->id }}" class="btn btn-primary">
                                    Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    </div>

</div>

<style>
    .brokerage-notes-page {
        color: #111827;
    }

    .brokerage-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 24px;
        margin-bottom: 20px;
    }

    .brokerage-page-header h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
    }

    .brokerage-page-header p {
        margin: 6px 0 0;
        color: #64748b;
    }

    .notes-filter {
        display: grid;
        grid-template-columns: 110px minmax(210px, 1fr) minmax(170px, .8fr) minmax(190px, 1fr) auto;
        gap: 14px;
        align-items: end;
        padding: 18px;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
        margin-bottom: 8px;
    }

    .notes-filter {
        border-bottom: 1px solid #dde1e8;
    }

    .filter-field label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 700;
    }

    .filter-field .form-control,
    .filter-field .form-select {
        min-height: 44px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        font-size: 14px;
    }

    .filter-field .form-control:focus,
    .filter-field .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .filter-actions {
        display: flex;
        gap: 8px;
    }

    .month-tabs {
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 28px 0 24px;
        overflow-x: auto;
        white-space: nowrap;
    }

    .month-tabs a {
        color: #1f2937;
        text-decoration: none;
        padding-bottom: 11px;
        border-bottom: 2px solid transparent;
        font-size: 15px;
    }

    .month-tabs a.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .month-tabs .all-months {
        margin-left: auto;
        color: #1f2937;
    }

    .brokerage-loading {
        padding: 28px;
        border: 1px dashed #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 800;
        text-align: center;
        margin-bottom: 20px;
    }

    .brokerage-summary {
        display: grid;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .summary-card {
        min-height: 86px;
        padding: 16px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        background: #eff6ff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }

    .summary-card span {
        display: block;
        margin-bottom: 8px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .summary-card strong {
        display: block;
        color: #111827;
        font-size: 21px;
        font-weight: 800;
        line-height: 1.2;
        word-break: break-word;
    }

    .notes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 260px));
        gap: 14px;
    }

    .note-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }

    .note-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        background: #dbeafe;
        font-size: 14px;
    }

    .note-card-header span {
        color: #6b7280;
    }

    .note-card-meta {
        display: grid;
        grid-template-columns: 42px 1fr 72px;
        gap: 10px;
        align-items: center;
        padding: 12px;
        background: #eff6ff;
        font-size: 14px;
    }

    .note-link-icon {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #dbeafe;
        color: #2563eb;
        font-weight: 700;
    }

    .edit-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        border: 1px solid #2563eb;
        border-radius: 4px;
        background: #fff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .edit-button:hover,
    .modal-outline-button:hover {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .note-lines {
        padding: 12px 14px 4px;
    }

    .note-lines div,
    .note-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 15px;
    }

    .note-lines strong,
    .note-total strong {
        white-space: nowrap;
    }

    .note-total {
        margin: 12px 14px 16px;
        padding-top: 16px;
        border-top: 2px dashed #e5e7eb;
        font-weight: 700;
    }

    .debit-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 18px;
        margin-left: 4px;
        border-radius: 4px;
        background: #9ca3af;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
    }

    .brokerage-edit-modal .modal-dialog {
        max-width: 525px;
    }

    .brokerage-edit-modal .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 18px 55px rgba(15, 23, 42, 0.24);
    }

    .brokerage-edit-modal .modal-header {
        border-bottom: 0;
        padding: 24px 30px 18px;
    }

    .brokerage-edit-modal .modal-title {
        font-size: 18px;
        font-weight: 800;
    }

    .brokerage-edit-modal .modal-body {
        padding: 0 30px 10px;
    }

    .modal-note-summary {
        display: grid;
        grid-template-columns: 42px 1fr 128px;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding: 12px;
        border-radius: 6px;
        background: #eff6ff;
    }

    .modal-outline-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border: 1px solid #2563eb;
        border-radius: 4px;
        color: #2563eb;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    .edit-field {
        display: grid;
        grid-template-columns: 1fr 204px;
        align-items: center;
        gap: 16px;
        margin-bottom: 12px;
    }

    .edit-field label {
        margin: 0;
        font-size: 16px;
    }

    .edit-field .form-control,
    .modal-liquid-total .form-control {
        min-height: 44px;
        border: 1px solid #d8dde6;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.08);
    }

    .edit-check {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding: 11px 12px;
        border-radius: 6px;
        background: #eef2f7;
        font-size: 14px;
        cursor: pointer;
    }

    .edit-check input {
        width: 18px;
        height: 18px;
        accent-color: #2563eb;
    }

    .modal-liquid-total {
        display: grid;
        grid-template-columns: 1fr 204px;
        align-items: center;
        gap: 16px;
        margin-top: 6px;
        padding-top: 16px;
        border-top: 2px dashed #e5e7eb;
        font-weight: 800;
    }

    .brokerage-edit-modal .modal-footer {
        gap: 14px;
        border-top: 0;
        padding: 14px 24px 24px;
    }

    .brokerage-edit-modal .btn-primary {
        min-width: 74px;
        border: none;
    }

    .empty-state {
        padding: 34px;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        background: #fff;
        color: #64748b;
        text-align: center;
    }

    @media (max-width: 1180px) {
        .notes-filter {
            grid-template-columns: repeat(2, minmax(160px, 1fr));
        }

        .brokerage-summary {
            grid-template-columns: repeat(3, minmax(150px, 1fr));
        }
    }

    @media (max-width: 760px) {
        .brokerage-page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .notes-filter {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            justify-content: stretch;
        }

        .filter-actions .btn {
            flex: 1;
        }

        .month-tabs {
            gap: 20px;
        }

        .month-tabs .all-months {
            margin-left: 0;
        }

        .notes-grid {
            grid-template-columns: 1fr;
        }

        .brokerage-summary {
            grid-template-columns: 1fr;
        }

        .brokerage-edit-modal .modal-body {
            padding: 0 18px 8px;
        }

        .modal-note-summary,
        .edit-field,
        .modal-liquid-total {
            grid-template-columns: 1fr;
        }

        .modal-outline-button {
            width: 100%;
        }
    }
</style>

@endsection

@push('scripts')
<script>
    (function () {
        const pageUrl = "{{ route('tax.brokerage-notes') }}";
        const form = document.getElementById('brokerageFilters');
        const loading = document.getElementById('brokerageLoading');
        const content = document.getElementById('notesContent');
        let debounceTimer = null;

        function activeMonth() {
            const active = document.querySelector('.month-tabs .month-filter.active');

            if (!active || active.classList.contains('all-months')) {
                return '';
            }

            const url = new URL(active.href);
            return url.searchParams.get('month') || '';
        }

        function buildUrl(month) {
            const params = new URLSearchParams(new FormData(form));

            if (month) {
                params.set('month', month);
            } else {
                params.delete('month');
            }

            Array.from(params.keys()).forEach((key) => {
                if (!params.get(key)) {
                    params.delete(key);
                }
            });

            const query = params.toString();

            return query ? `${pageUrl}?${query}` : pageUrl;
        }

        function setLoading(isLoading) {
            loading.classList.toggle('d-none', !isLoading);
            content.classList.toggle('d-none', isLoading);
        }

        function swapFromHtml(html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextContent = doc.querySelector('#notesContent');
            const nextTabs = doc.querySelector('.month-tabs');

            if (nextTabs) {
                document.querySelector('.month-tabs').innerHTML = nextTabs.innerHTML;
            }

            if (nextContent) {
                content.innerHTML = nextContent.innerHTML;
            }
        }

        function loadBrokerageNotes(url, pushState = true) {
            setLoading(true);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then((response) => response.text())
                .then((html) => {
                    swapFromHtml(html);

                    if (pushState) {
                        window.history.pushState({}, '', url);
                    }

                    setLoading(false);
                })
                .catch(() => {
                    loading.textContent = 'Nao foi possivel carregar as notas.';
                    content.classList.remove('d-none');
                });
        }

        function submitFilters() {
            loadBrokerageNotes(buildUrl(activeMonth()));
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitFilters();
        });

        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', submitFilters);
        });

        form.querySelectorAll('input[type="text"]').forEach((input) => {
            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitFilters, 450);
            });
        });

        document.addEventListener('click', function (event) {
            const monthLink = event.target.closest('.month-filter');
            const clearLink = event.target.closest('#clearBrokerageFilters');

            if (monthLink) {
                event.preventDefault();
                loadBrokerageNotes(monthLink.href);
                return;
            }

            if (clearLink) {
                event.preventDefault();
                form.reset();
                loadBrokerageNotes(clearLink.href);
            }
        });

        window.addEventListener('popstate', function () {
            loadBrokerageNotes(window.location.href, false);
        });

        document.addEventListener('DOMContentLoaded', function () {
            loadBrokerageNotes(window.location.href, false);
        });
    })();
</script>
@endpush
