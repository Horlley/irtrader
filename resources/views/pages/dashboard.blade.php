@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="grid">

    <div class="card">

        <div class="card-title">
            Lucro no mês
        </div>

        <div class="card-value" id="profitMonth">
            R$ 0,00
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            Imposto devido
        </div>

        <div class="card-value" id="taxDue">
            R$ 0,00
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            Prejuízo acumulado
        </div>

        <div class="card-value" id="lossCarry">
            R$ 0,00
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            DARF pendente
        </div>

        <div class="card-value" id="darfPending">
            R$ 0,00
        </div>

    </div>

</div>


<div class="chart-card">

<canvas id="profitChart" style="height:300px"></canvas>

</div>

<div class="grid">

    <div class="card">
        <div class="card-title">Dólar</div>
        <div class="card-value" id="marketDolar">R$ 0,00</div>
    </div>

    <div class="card">
        <div class="card-title">Índice</div>
        <div class="card-value" id="marketIndice">R$ 0,00</div>
    </div>

    <div class="card">
        <div class="card-title">Outros</div>
        <div class="card-value" id="marketOutros">R$ 0,00</div>
    </div>

</div>

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/dashboard.js') }}"></script>

@endpush