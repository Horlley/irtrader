@extends('layouts.app')

@section('title','Configuracoes')

@section('content')

@php
    $moneyInput = function ($value) {
        return number_format((float) $value, 2, ',', '.');
    };
@endphp

<div class="settings-page">
    <div class="settings-header">
        <div>
            <h2>Configuracoes fiscais</h2>
            <p>Informe saldos anteriores para que a apuracao mensal considere prejuizos e IRRF acumulados.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            Verifique os campos informados.
        </div>
    @endif

    <div class="settings-grid">
        <div class="settings-card">
            <div class="card-heading">
                <span>Configuracao unica por usuario</span>
                <strong>{{ $config ? 'Atualizando' : 'Nova configuracao' }}</strong>
            </div>

            <form method="POST" action="{{ route('settings.tax-config.save') }}" class="settings-form">
                @csrf

                <div class="field-row">
                    <label for="year">Ano fiscal</label>
                    <select id="year" name="year" class="form-select" onchange="window.location='{{ route('settings.index') }}?year=' + this.value">
                        @foreach($years as $itemYear)
                            <option value="{{ $itemYear }}" {{ (int) $year === (int) $itemYear ? 'selected' : '' }}>
                                {{ $itemYear }}
                            </option>
                        @endforeach
                    </select>
                    @error('year')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field-row">
                    <label for="initial_loss_daytrade">Prejuizo acumulado Day Trade</label>
                    <div class="money-field">
                        <span>R$</span>
                        <input
                            id="initial_loss_daytrade"
                            type="text"
                            name="initial_loss_daytrade"
                            class="form-control"
                            value="{{ old('initial_loss_daytrade', $moneyInput($config->initial_loss_daytrade ?? 0)) }}"
                            placeholder="0,00"
                        >
                    </div>
                    <small class="field-help">Use valor positivo; o sistema trata como prejuizo a compensar.</small>
                </div>

                <div class="field-row">
                    <label for="initial_irrf_daytrade">IRRF Day Trade acumulado</label>
                    <div class="money-field">
                        <span>R$</span>
                        <input
                            id="initial_irrf_daytrade"
                            type="text"
                            name="initial_irrf_daytrade"
                            class="form-control"
                            value="{{ old('initial_irrf_daytrade', $moneyInput($config->initial_irrf_daytrade ?? 0)) }}"
                            placeholder="0,00"
                        >
                    </div>
                    <small class="field-help">Valor de IRRF anterior disponivel para abater imposto futuro.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Salvar configuracao
                    </button>
                </div>
            </form>

            @if($config)
                <form method="POST" action="{{ route('settings.tax-config.destroy', ['year' => $year]) }}" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-link text-danger" onclick="return confirm('Remover configuracao fiscal deste ano?')">
                        Limpar configuracao deste ano
                    </button>
                </form>
            @endif
        </div>

        <div class="settings-card info-card">
            <div class="card-heading">
                <span>Como isso afeta a apuracao</span>
                <strong>Saldo inicial</strong>
            </div>

            <div class="info-list">
                <div>
                    <span>Usuario</span>
                    <strong>#{{ auth()->id() ?? 1 }}</strong>
                </div>

                <div>
                    <span>Ano selecionado</span>
                    <strong>{{ $year }}</strong>
                </div>

                <div>
                    <span>Prejuizo inicial</span>
                    <strong>R$ {{ $moneyInput($config->initial_loss_daytrade ?? 0) }}</strong>
                </div>

                <div>
                    <span>IRRF inicial</span>
                    <strong>R$ {{ $moneyInput($config->initial_irrf_daytrade ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-page {
        color: #111827;
    }

    .settings-header {
        margin-bottom: 22px;
    }

    .settings-header h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
    }

    .settings-header p {
        max-width: 760px;
        margin: 6px 0 0;
        color: #64748b;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: minmax(360px, 1.1fr) minmax(280px, .7fr);
        gap: 18px;
    }

    .settings-card {
        padding: 20px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }

    .card-heading {
        margin-bottom: 20px;
    }

    .card-heading span {
        display: block;
        margin-bottom: 6px;
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
    }

    .card-heading strong {
        font-size: 20px;
        font-weight: 900;
    }

    .settings-form {
        display: grid;
        gap: 18px;
    }

    .field-row label {
        display: block;
        margin-bottom: 8px;
        color: #374151;
        font-size: 14px;
        font-weight: 800;
    }

    .field-row .form-select,
    .field-row .form-control {
        min-height: 46px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        font-weight: 700;
    }

    .field-row .form-select:focus,
    .field-row .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .money-field {
        display: grid;
        grid-template-columns: 58px 1fr;
        align-items: center;
    }

    .money-field span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        border: 1px solid #bfdbfe;
        border-right: 0;
        border-radius: 6px 0 0 6px;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 900;
    }

    .money-field .form-control {
        border-radius: 0 6px 6px 0;
    }

    .field-help {
        display: block;
        margin-top: 6px;
        color: #64748b;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 4px;
    }

    .delete-form {
        margin-top: 14px;
        text-align: right;
    }

    .info-card {
        background: #eff6ff;
    }

    .info-list {
        display: grid;
        gap: 12px;
    }

    .info-list div {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #bfdbfe;
    }

    .info-list div:last-child {
        border-bottom: 0;
    }

    .info-list span {
        color: #64748b;
        font-weight: 700;
    }

    .info-list strong {
        text-align: right;
        font-weight: 900;
    }

    @media (max-width: 900px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endsection

@push('scripts')
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: '{{ session('success') }}',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
