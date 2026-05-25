function initMonthlyChart(allMonths, allRevenues, allExpenses, currentMonth) {
    const canvas = document.getElementById('chart-monthly');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Build gradient fill (blue → transparent)
    function makeGradient() {
        const h = canvas.offsetHeight || 180;
        const g = ctx.createLinearGradient(0, 0, 0, h);
        g.addColorStop(0, 'rgba(37,99,235,0.22)');
        g.addColorStop(1, 'rgba(37,99,235,0)');
        return g;
    }

    // Slice helpers — only show months up to current month
    const idx6m    = Math.max(0, currentMonth - 6);
    const labels6m = allMonths.slice(idx6m, currentMonth);
    const data6m   = allRevenues.slice(idx6m, currentMonth);
    const labels1a = allMonths.slice(0, currentMonth);
    const data1a   = allRevenues.slice(0, currentMonth);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels6m,
            datasets: [{
                data: data6m,
                fill: true,
                backgroundColor: makeGradient(),
                borderColor: '#2563eb',
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#fff',
                tension: 0.38,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 260 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c1a17',
                    titleColor: 'rgba(255,255,255,.6)',
                    bodyColor: '#fff',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('fr-FR') + ' €',
                    },
                },
            },
            scales: {
                y: {
                    display: false,
                    beginAtZero: true,
                    grace: '10%',
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 11 },
                        maxRotation: 0,
                    },
                    border: { display: false },
                },
            },
            layout: {
                padding: { top: 8, bottom: 0, left: 0, right: 4 },
            },
        },
    });

    // Rebuild gradient after resize (canvas height changes)
    window.addEventListener('resize', () => {
        chart.data.datasets[0].backgroundColor = makeGradient();
        chart.update('none');
    });

    // Period tab switching
    const periodLabel = document.getElementById('period-label');
    document.querySelectorAll('.period-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.period-tab').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');

            const period = btn.dataset.period;
            let labels, data, label;

            if (period === '6m') {
                labels = labels6m;
                data   = data6m;
                label  = '6 derniers mois';
            } else {
                labels = labels1a;
                data   = data1a;
                label  = period === 'max' ? 'Année complète' : 'Cette année';
            }

            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.data.datasets[0].backgroundColor = makeGradient();
            chart.update('active');
            if (periodLabel) periodLabel.textContent = label;
        });
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
