import ApexCharts from 'apexcharts';

// Data is injected by the Blade view as window.__dashboardData.
const D = window.__dashboardData || {};

const KANIT = 'Kanit, sans-serif';
const GRID = '#f1f5f9';
const AXIS = '#94a3b8';

const baseChart = {
    fontFamily: KANIT,
    toolbar: { show: false },
    zoom: { enabled: false },
    animations: { easing: 'easeinout', speed: 600 },
};

function mount(id, options) {
    const el = document.querySelector(id);
    if (!el) return;
    const chart = new ApexCharts(el, options);
    chart.render();
    return chart;
}

// ---------------------------------------------------------------
// Booking Trend — area, last 30 days
// ---------------------------------------------------------------
if (D.trend) {
    mount('#chart-trend', {
        chart: { ...baseChart, type: 'area', height: 220, sparkline: { enabled: false } },
        series: [{ name: 'การจอง', data: D.trend.counts }],
        colors: ['#f97316'],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0, stops: [0, 100] },
        },
        dataLabels: { enabled: false },
        grid: { borderColor: GRID, strokeDashArray: 0, xaxis: { lines: { show: false } } },
        xaxis: {
            categories: D.trend.labels,
            tickAmount: 6,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: AXIS, fontSize: '10px' } },
        },
        yaxis: { labels: { style: { colors: AXIS, fontSize: '10px' } } },
        tooltip: { x: { show: true }, y: { formatter: (v) => `${v} รายการ` } },
    });
}

// ---------------------------------------------------------------
// Cancellation Analysis — donut
// ---------------------------------------------------------------
if (D.cancel) {
    mount('#chart-cancel', {
        chart: { ...baseChart, type: 'donut', height: 300 },
        series: D.cancel.series,
        labels: D.cancel.labels,
        colors: D.cancel.colors,
        stroke: { width: 2, colors: ['#fff'] },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontSize: '13px',
            labels: { colors: '#475569' },
            markers: { width: 10, height: 10, radius: 12 },
            itemMargin: { horizontal: 8, vertical: 3 },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '64%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '12px',
                            color: '#94a3b8',
                            formatter: () => '100%',
                        },
                        value: { fontSize: '22px', fontWeight: 700, color: '#1f2937' },
                    },
                },
            },
        },
        tooltip: { y: { formatter: (v) => `${v} รายการ` } },
    });
}

// ---------------------------------------------------------------
// Monthly Booking Volume — bars (last month highlighted)
// ---------------------------------------------------------------
if (D.monthly) {
    const barColors = D.monthly.labels.map((_, i) =>
        i === D.monthly.labels.length - 1 ? '#f97316' : '#1e293b'
    );
    mount('#chart-monthly', {
        chart: { ...baseChart, type: 'bar', height: 260 },
        series: [{ name: 'การจอง', data: D.monthly.counts }],
        colors: barColors,
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, distributed: true } },
        legend: { show: false },
        dataLabels: { enabled: false },
        grid: { borderColor: GRID, xaxis: { lines: { show: false } } },
        xaxis: {
            categories: D.monthly.labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: AXIS, fontSize: '11px' } },
        },
        yaxis: { labels: { style: { colors: AXIS, fontSize: '10px' } } },
        tooltip: { y: { formatter: (v) => `${v} รายการ` } },
    });
}

// ---------------------------------------------------------------
// Peak Booking Hours — horizontal bars, colour by intensity
// ---------------------------------------------------------------
if (D.peak) {
    const peakColors = D.peak.values.map((v) =>
        v >= 70 ? '#f97316' : v >= 45 ? '#fbbf24' : '#cbd5e1'
    );
    mount('#chart-peak', {
        chart: { ...baseChart, type: 'bar', height: 340 },
        series: [{ name: 'Utilization', data: D.peak.values }],
        colors: peakColors,
        plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 3, distributed: true } },
        legend: { show: false },
        dataLabels: {
            enabled: true,
            formatter: (v) => `${v}%`,
            offsetX: 24,
            style: { colors: ['#64748b'], fontSize: '10px', fontWeight: 600 },
        },
        grid: { borderColor: GRID, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        xaxis: {
            categories: D.peak.labels,
            max: 100,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: AXIS, fontSize: '10px' }, formatter: (v) => `${v}%` },
        },
        yaxis: { labels: { style: { colors: AXIS, fontSize: '10px' } } },
        tooltip: { y: { formatter: (v) => `${v}%` } },
    });
}
