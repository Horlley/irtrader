document.addEventListener("DOMContentLoaded", function () {

    loadStats();
    loadChart();

});

/* =========================
   CARDS DO DASHBOARD
========================= */

function loadStats() {

    Core.get("/api/dashboard")
        .then(r => {

            if (!r) return;

            setValue("profitMonth", r.profit_month);
            setValue("taxDue", r.tax_due);
            setValue("lossCarry", r.loss_carry);
            setValue("darfPending", r.darf_pending);

        })
        .catch(e => {
            console.error("Erro ao carregar stats", e);
        });

}

function setValue(id, value) {

    const el = document.getElementById(id);

    if (!el) return;

    el.innerText = "R$ " + formatNumber(value);

}

/* =========================
   GRÁFICO
========================= */

let profitChart = null;

function loadChart() {

    Core.get("/api/dashboard/chart")
        .then(r => {

            if (!r || !r.labels) return;

            const canvas = document.getElementById("profitChart");

            if (!canvas) return;

            const ctx = canvas.getContext("2d");

            /* destruir gráfico anterior */

            if (profitChart) {
                profitChart.destroy();
            }

            profitChart = new Chart(ctx, {

                type: "line",

                data: {

                    labels: r.labels,

                    datasets: [{
                        label: "Lucro mensal",
                        data: r.data,

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

                        legend: {
                            display: false
                        },

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

                            ticks: {
                                callback: function (value) {
                                    return "R$ " + value;
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

    if (!value) return "0";

    return Number(value).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

}