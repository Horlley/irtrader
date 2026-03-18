document.addEventListener("DOMContentLoaded", function () {

    loadStats();
    loadChart();

});

/* =========================
   CARDS DO DASHBOARD
========================= */

function loadStats() {

    fetch("/api/dashboard")
        .then(res => res.json())
        .then(r => {

            if (!r) return;

            setValue("profitMonth", r.profit_month);
            setValue("taxDue", r.tax_due);
            setValue("lossCarry", r.loss_carry);
            setValue("darfPending", r.darf_pending);

            // 🔥 MERCADOS
            if (r.markets) {
                setValue("marketDolar", r.markets.dolar);
                setValue("marketIndice", r.markets.indice);
                setValue("marketOutros", r.markets.outros);
            }

        })
        .catch(e => {
            console.error("Erro ao carregar stats", e);
        });

}

function setValue(id, value) {

    const el = document.getElementById(id);

    if (!el) return;

    value = Number(value) || 0;

    el.innerText = "R$ " + formatNumber(value);

}

/* =========================
   GRÁFICO
========================= */

let profitChart = null;

function loadChart() {

    fetch("/api/dashboard/chart")
        .then(res => res.json())
        .then(r => {

            if (!r || !r.labels || !r.data) return;

            const canvas = document.getElementById("profitChart");

            if (!canvas) return;

            const ctx = canvas.getContext("2d");

            if (profitChart) {
                profitChart.destroy();
            }

            profitChart = new Chart(ctx, {

                type: "line",

                data: {
                    labels: r.labels,
                    datasets: [{
                        label: "Lucro mensal",
                        data: r.data.map(v => Number(v) || 0),
                        borderColor: "#2563eb",
                        backgroundColor: "rgba(37,99,235,0.12)",
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: "#2563eb"
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return "R$ " + formatNumber(context.parsed.y);
                                }
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return "R$ " + formatNumber(value);
                                }
                            },
                            grid: {
                                color: "rgba(0,0,0,0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }

            });

        })
        .catch(e => {
            console.error("Erro ao carregar gráfico", e);
        });

}

/* =========================
   FORMATADOR
========================= */

function formatNumber(value) {

    value = Number(value) || 0;

    return value.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

}