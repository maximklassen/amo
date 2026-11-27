import { Bar } from 'react-chartjs-2';
import { BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip);

const dateFormat = new Intl.DateTimeFormat('de-DE');
const numberFormat = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 1 });

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

export default function ConsumptionChart({ points, unit, label }) {
    if (points.length === 0) {
        return <p className="chart__empty">Noch zu wenige Zählerstände für ein Diagramm.</p>;
    }

    const data = {
        labels: points.map((p) => p.year),
        datasets: [
            {
                data: points.map((p) => p.consumption),
                backgroundColor: css('--color-primary-light'),
                hoverBackgroundColor: css('--color-primary'),
                borderRadius: 4,
                borderSkipped: 'bottom',
                maxBarThickness: 48,
            },
        ],
    };

    const options = {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                displayColors: false,
                callbacks: {
                    title: (items) => {
                        const p = points[items[0].dataIndex];
                        return `${dateFormat.format(new Date(p.from))} – ${dateFormat.format(new Date(p.to))}`;
                    },
                    label: (item) => `${numberFormat.format(item.parsed.y)} ${unit}`,
                },
            },
        },
        scales: {
            x: { grid: { display: false } },
            y: {
                beginAtZero: true,
                grid: { color: css('--color-border') },
                ticks: { callback: (value) => numberFormat.format(value) },
            },
        },
    };

    return (
        <figure className="chart">
            <Bar data={data} options={options} aria-label={`${label}: Verbrauch je Jahr in ${unit}`} role="img" />
        </figure>
    );
}
