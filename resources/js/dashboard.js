document.addEventListener("DOMContentLoaded", function () {

    loadStats();
    loadChart();

});

function loadStats() {

    Core.get("/api/dashboard")
        .then(r => {

            document.getElementById("profitMonth").innerText = "R$ " + r.profit_month;
            document.getElementById("taxDue").innerText = "R$ " + r.tax_due;
            document.getElementById("lossCarry").innerText = "R$ " + r.loss_carry;
            document.getElementById("darfPending").innerText = "R$ " + r.darf_pending;

        });

}

function loadChart() {

    const canvas = document.getElementById("profitChart");

    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    new Chart(ctx, {

        type: "line",

        data: {
            labels: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun"],
            datasets: [{
                label: "Lucro mensal",
                data: [1200, 800, 1500, 900, 2000, 1700],
                borderColor: "#2563eb",
                backgroundColor: "rgba(37,99,235,0.1)",
                fill: true
            }]
        }

    });

}