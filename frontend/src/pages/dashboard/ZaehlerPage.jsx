import { useEffect, useState } from 'react';
import { addMeterReading, getMyMeters } from '../../api/api.js';
import DashboardPageHeader from './components/DashboardPageHeader.jsx';
import ConsumptionChart from './components/ConsumptionChart.jsx';

const TYPES = [
    { type: 'strom', title: 'Strom', icon: '⚡', unit: 'kWh' },
    { type: 'wasser', title: 'Wasser', icon: '💧', unit: 'm³' },
];

const dateFormat = new Intl.DateTimeFormat('de-DE');
const numberFormat = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 });

function toConsumption(readings) {
    return readings.slice(1).map((reading, i) => ({
        from: readings[i].read_at,
        to: reading.read_at,
        consumption: reading.value - readings[i].value,
    }));
}

function ReadingForm({ meter, onSaved }) {
    const [value, setValue] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    const last = meter.readings.at(-1);

    async function handleSubmit(event) {
        event.preventDefault();
        const number = Number(value.replace(',', '.'));

        if (value.trim() === '' || !Number.isFinite(number) || number < 0) {
            setError('Bitte einen gültigen Zählerstand eingeben.');
            return;
        }
        if (last && number < last.value) {
            setError(`Der Stand darf nicht kleiner als ${numberFormat.format(last.value)} sein.`);
            return;
        }

        setSaving(true);
        setError('');
        try {
            onSaved(await addMeterReading(meter.id, number));
            setValue('');
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    }

    const inputId = `reading-${meter.id}`;

    return (
        <form className="reading-form" onSubmit={handleSubmit} noValidate>
            <label htmlFor={inputId}>Neuer Zählerstand ({meter.unit})</label>
            <div className="reading-form__row">
                <input
                    id={inputId}
                    className="input"
                    type="text"
                    inputMode="decimal"
                    placeholder={last ? numberFormat.format(last.value) : '0'}
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    aria-invalid={error !== ''}
                    aria-describedby={`${inputId}-hint`}
                />
                <button className="btn btn--primary btn--sm" type="submit" disabled={saving}>
                    {saving ? 'Speichern …' : 'Speichern'}
                </button>
            </div>
            <p id={`${inputId}-hint`} className={error ? 'reading-form__error' : 'reading-form__hint'}>
                {error ||
                    (last
                        ? `Letzter Stand: ${numberFormat.format(last.value)} ${meter.unit} am ${dateFormat.format(new Date(last.read_at))}`
                        : 'Noch kein Stand erfasst.')}
            </p>
        </form>
    );
}

function MeterSection({ config, meter, onSaved }) {
    return (
        <section className="meter">
            <h3 className="meter__title">
                <span aria-hidden="true">{config.icon}</span> {config.title}
                {meter && <span className="meter__number">Zähler-Nr. {meter.number}</span>}
            </h3>
            {meter ? (
                <>
                    <ConsumptionChart
                        points={toConsumption(meter.readings)}
                        unit={meter.unit ?? config.unit}
                        label={config.title}
                    />
                    <ReadingForm meter={{ ...meter, unit: meter.unit ?? config.unit }} onSaved={onSaved} />
                </>
            ) : (
                <p className="meter__empty">Für deine Parzelle ist kein {config.title.toLowerCase()}zähler eingetragen.</p>
            )}
        </section>
    );
}

export default function ZaehlerPage() {
    const [meters, setMeters] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        getMyMeters()
            .then(setMeters)
            .catch((err) => setError(err.message))
            .finally(() => setLoading(false));
    }, []);

    function handleSaved(updated) {
        setMeters((current) => current.map((m) => (m.id === updated.id ? updated : m)));
    }

    return (
        <>
            <DashboardPageHeader title="Zähler" subtitle="Wasser- und Stromzählerstände erfassen." />
            {loading && <p>Lade Zählerstände …</p>}
            {error && <p className="reading-form__error">{error}</p>}
            {!loading && !error && (
                <div className="meters">
                    {TYPES.map((config) => (
                        <MeterSection
                            key={config.type}
                            config={config}
                            meter={meters.find((m) => m.type === config.type)}
                            onSaved={handleSaved}
                        />
                    ))}
                </div>
            )}
        </>
    );
}
