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

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/dashboard.js') }}"></script>

@endpush