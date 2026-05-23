function initMonthlyChart(months, revenues, expenses) {
    new Chart(document.getElementById('chart-monthly'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'CA encaissé (€)',
                    data: revenues,
                    backgroundColor: 'rgba(255,255,255,0.85)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Dépenses (€)',
                    data: expenses,
                    backgroundColor: 'rgba(255,255,255,0.3)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y.toLocaleString('fr-FR') + ' €',
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,.12)' },
                    ticks: {
                        color: 'rgba(255,255,255,.65)',
                        font: { size: 11 },
                        callback: v => v.toLocaleString('fr-FR') + ' €',
                    },
                    border: { color: 'transparent' },
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,.65)', font: { size: 11 } },
                    border: { color: 'transparent' },
                },
            },
        },
    });
}

function initCategoryChart(labels, data) {
    const canvas = document.getElementById('chart-categories');
    if (!canvas) return;
    const colors = ['#2563eb','#7c3aed','#db2777','#ea580c','#ca8a04','#16a34a','#0891b2','#64748b','#9333ea','#e11d48','#0284c7'];
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right', labels: { font: { size: 11 }, padding: 8, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €',
                    },
                },
            },
            cutout: '60%',
        },
    });
}
